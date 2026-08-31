<?php

namespace App\Services;

use App\Mail\DailyFinalHarianReportMail;
use App\Models\Cabang;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualan;
use App\Models\PesananPenjualanItem;
use App\Models\ShiftKasir;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class DailyFinalEmailReportService
{
    public function sendForDate(string $date, ?array $cabangIds = null): array
    {
        $targetDate = Carbon::parse($date, config('app.timezone'))->toDateString();

        $query = Cabang::query()
            ->where('status', true)
            ->where('tutup_kasir_email_enabled', true);

        if (!empty($cabangIds)) {
            $query->whereIn('id', array_map('intval', $cabangIds));
        }

        $cabangs = $query->get(['id', 'nama', 'tutup_kasir_email_recipients']);

        $sent = 0;
        $skipped = 0;
        foreach ($cabangs as $cabang) {
            $recipients = $this->normalizeEmailRecipients((array) $cabang->tutup_kasir_email_recipients);
            if (empty($recipients)) {
                $skipped++;
                continue;
            }

            $report = $this->buildReport((int) $cabang->id, (string) $cabang->nama, $targetDate);
            Mail::to($recipients)->queue(new DailyFinalHarianReportMail($report));
            $sent++;
        }

        return [
            'date' => $targetDate,
            'sent' => $sent,
            'skipped' => $skipped,
            'total_cabang' => (int) $cabangs->count(),
        ];
    }

    private function buildReport(int $cabangId, string $cabangName, string $targetDate): array
    {
        $orders = PesananPenjualan::query()
            ->with(['kantongOrder:id,pesanan_penjualan_id,nomor_ko'])
            ->where('cabang_id', $cabangId)
            ->whereDate('created_at', $targetDate)
            ->get(['id', 'kasir_user_id', 'customer_name', 'total', 'balance']);

        $orderIds = $orders->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $items = empty($orderIds)
            ? collect()
            : PesananPenjualanItem::query()
                ->whereIn('pesanan_penjualan_id', $orderIds)
                ->where('is_void', false)
                ->get(['pesanan_penjualan_id', 'paket_id', 'qty', 'harga', 'diskon', 'subtotal']);

        $totalBrutoItem = (float) $items->sum(fn ($row) => ((float) $row->qty * (float) $row->harga));
        $totalDiskonItem = (float) $items->sum('diskon');
        $totalSubtotalItem = (float) $items->sum('subtotal');
        $totalPenjualan = (float) $orders->sum('total');
        $diskonOrder = max($totalSubtotalItem - $totalPenjualan, 0);
        $totalDiskon = $totalDiskonItem + $diskonOrder;

        $payments = PembayaranPenjualan::query()
            ->whereDate('tanggal_bayar', $targetDate)
            ->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            })
            ->get(['metode_pembayaran_id', 'nominal', 'kasir_user_id']);

        $paymentByOrder = PembayaranPenjualan::query()
            ->join('metode_pembayaran as mp', 'mp.id', '=', 'pembayaran_penjualan.metode_pembayaran_id')
            ->whereDate('pembayaran_penjualan.tanggal_bayar', $targetDate)
            ->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            })
            ->selectRaw('pembayaran_penjualan.pesanan_penjualan_id, mp.kode, mp.nama, SUM(pembayaran_penjualan.nominal) as total')
            ->groupBy('pembayaran_penjualan.pesanan_penjualan_id', 'mp.kode', 'mp.nama')
            ->get()
            ->groupBy('pesanan_penjualan_id');

        $paymentByMethod = PembayaranPenjualan::query()
            ->join('metode_pembayaran as mp', 'mp.id', '=', 'pembayaran_penjualan.metode_pembayaran_id')
            ->whereDate('pembayaran_penjualan.tanggal_bayar', $targetDate)
            ->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            })
            ->selectRaw('
                mp.kode,
                mp.nama,
                COUNT(*) as jumlah_transaksi,
                COALESCE(SUM(CASE WHEN pembayaran_penjualan.nominal > 0 THEN pembayaran_penjualan.nominal ELSE 0 END), 0) as total_kotor,
                COALESCE(ABS(SUM(CASE WHEN pembayaran_penjualan.nominal < 0 THEN pembayaran_penjualan.nominal ELSE 0 END)), 0) as total_void,
                COALESCE(SUM(pembayaran_penjualan.nominal), 0) as total
            ')
            ->groupBy('mp.kode', 'mp.nama')
            ->orderBy('mp.nama')
            ->get()
            ->map(fn ($row) => [
                'kode' => (string) $row->kode,
                'nama' => (string) $row->nama,
                'jumlah_transaksi' => (int) $row->jumlah_transaksi,
                'total_kotor' => (float) $row->total_kotor,
                'total_void' => (float) $row->total_void,
                'total' => (float) $row->total,
            ])
            ->values()
            ->all();

        $totalPembayaranKotor = (float) collect($paymentByMethod)->sum('total_kotor');
        $totalPembayaranVoid = (float) collect($paymentByMethod)->sum('total_void');
        $totalPembayaranBersih = (float) collect($paymentByMethod)->sum('total');
        $totalVoidOrder = (float) PenjualanVoidLog::query()
            ->whereIn('tipe_void', ['FULL', 'PARTIAL'])
            ->whereDate('void_effective_date', $targetDate)
            ->whereHas('order', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            })
            ->sum('nominal_void');

        $paketSummary = empty($orderIds)
            ? collect()
            : PesananPenjualanItem::query()
                ->join('paket as p', 'p.id', '=', 'pesanan_penjualan_item.paket_id')
                ->whereIn('pesanan_penjualan_item.pesanan_penjualan_id', $orderIds)
                ->where('pesanan_penjualan_item.is_void', false)
                ->whereNotNull('pesanan_penjualan_item.paket_id')
                ->selectRaw('p.kode, p.nama, SUM(pesanan_penjualan_item.qty) as qty, SUM(pesanan_penjualan_item.qty * pesanan_penjualan_item.harga) as bruto, SUM(pesanan_penjualan_item.diskon) as diskon, SUM(pesanan_penjualan_item.subtotal) as neto')
                ->groupBy('p.kode', 'p.nama')
                ->orderByDesc('qty')
                ->get()
                ->map(fn ($row) => [
                    'kode' => (string) $row->kode,
                    'nama' => (string) $row->nama,
                    'qty' => (float) $row->qty,
                    'bruto' => (float) $row->bruto,
                    'diskon' => (float) $row->diskon,
                    'neto' => (float) $row->neto,
                ])
                ->values();

        $kasirIds = $orders->pluck('kasir_user_id')
            ->merge($payments->pluck('kasir_user_id'))
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values();

        $kasirNames = User::query()
            ->whereIn('id', $kasirIds->all())
            ->pluck('name', 'id');

        $ordersByKasir = $orders->groupBy('kasir_user_id');
        $paymentsByKasir = $payments->groupBy('kasir_user_id');
        $kasirSummary = $kasirIds->map(function ($kasirId) use ($kasirNames, $ordersByKasir, $paymentsByKasir) {
            $orderRows = $ordersByKasir->get($kasirId, collect());
            $paymentRows = $paymentsByKasir->get($kasirId, collect());

            return [
                'nama' => (string) ($kasirNames->get($kasirId) ?? ('User #' . $kasirId)),
                'jumlah_transaksi' => (int) $orderRows->count(),
                'total_penjualan' => (float) $orderRows->sum('total'),
                'total_pembayaran' => (float) $paymentRows->sum('nominal'),
            ];
        })->sortByDesc('total_pembayaran')->values()->all();

        $itemsByOrder = $items->groupBy('pesanan_penjualan_id');
        $paketNameMap = collect();
        $produkNameMap = collect();
        if ($items->isNotEmpty()) {
            $paketIds = $items->pluck('paket_id')->filter()->unique()->map(fn ($id) => (int) $id)->all();
            $produkIds = $items->pluck('produk_id')->filter()->unique()->map(fn ($id) => (int) $id)->all();
            if (!empty($paketIds)) {
                $paketNameMap = \App\Models\Paket::query()->whereIn('id', $paketIds)->pluck('nama', 'id');
            }
            if (!empty($produkIds)) {
                $produkNameMap = \App\Models\Produk::query()->whereIn('id', $produkIds)->pluck('nama', 'id');
            }
        }

        $kasirDetailRows = $orders->map(function ($order) use ($kasirNames, $itemsByOrder, $paymentByOrder, $targetDate, $paketNameMap, $produkNameMap) {
            $orderItems = $itemsByOrder->get($order->id, collect());
            $itemLabels = $orderItems->map(function ($item) use ($paketNameMap, $produkNameMap) {
                if (!empty($item->paket_id)) {
                    $namaPaket = $paketNameMap->get((int) $item->paket_id) ?? ('Paket #' . $item->paket_id);
                    return 'PAKET: ' . $namaPaket . ' x' . rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.');
                }
                $namaProduk = $produkNameMap->get((int) $item->produk_id) ?? ('Produk #' . $item->produk_id);
                return 'ITEM: ' . $namaProduk . ' x' . rtrim(rtrim(number_format((float) $item->qty, 2, '.', ''), '0'), '.');
            })->values()->all();

            $paymentRows = collect($paymentByOrder->get($order->id, collect()));
            $metodePembayaran = $paymentRows->map(function ($row) {
                $label = trim(((string) $row->kode) . ' - ' . ((string) $row->nama), ' -');
                return $label . ' (Rp ' . number_format((float) $row->total, 0, ',', '.') . ')';
            })->values()->all();

            $itemDiskon = (float) $orderItems->sum('diskon');
            $subtotalItems = (float) $orderItems->sum('subtotal');
            $orderDiscount = max($subtotalItems - (float) $order->total, 0);
            $totalDiskonOrder = $itemDiskon + $orderDiscount;

            return [
                'tanggal' => Carbon::parse($targetDate)->format('d-m-Y'),
                'kasir' => (string) ($kasirNames->get((int) $order->kasir_user_id) ?? ('User #' . $order->kasir_user_id)),
                'no_ko' => (string) ($order->kantongOrder?->nomor_ko ?? '-'),
                'customer' => (string) ($order->customer_name ?? '-'),
                'item_ringkas' => empty($itemLabels) ? '-' : implode('; ', $itemLabels),
                'metode_pembayaran' => empty($metodePembayaran) ? '-' : implode('; ', $metodePembayaran),
                'total_bayar_masuk' => (float) $paymentRows->sum('total'),
                'total_diskon' => $totalDiskonOrder,
            ];
        })->values()->all();

        $kasirGrouped = collect($kasirDetailRows)
            ->groupBy('kasir')
            ->map(function ($rows, $kasirName) {
                return [
                    'kasir' => (string) $kasirName,
                    'rows' => $rows->values()->all(),
                    'subtotal' => [
                        'jumlah_transaksi' => (int) $rows->count(),
                        'total_bayar_masuk' => (float) $rows->sum('total_bayar_masuk'),
                        'total_diskon' => (float) $rows->sum('total_diskon'),
                    ],
                ];
            })
            ->values()
            ->all();

        $kasirGrandTotal = [
            'jumlah_transaksi' => (int) collect($kasirDetailRows)->count(),
            'total_bayar_masuk' => (float) collect($kasirDetailRows)->sum('total_bayar_masuk'),
            'total_diskon' => (float) collect($kasirDetailRows)->sum('total_diskon'),
        ];

        $shiftClosed = ShiftKasir::query()
            ->where('cabang_id', $cabangId)
            ->whereDate('ditutup_pada', $targetDate)
            ->where('status', 'CLOSED')
            ->count();

        return [
            'cabang_id' => $cabangId,
            'cabang_name' => $cabangName,
            'report_date' => $targetDate,
            'report_date_label' => Carbon::parse($targetDate)->format('d-m-Y'),
            'generated_at' => now()->format('d-m-Y H:i'),
            'timezone' => (string) config('app.timezone'),
            'summary' => [
                'jumlah_transaksi' => (int) $orders->count(),
                'total_item_terjual' => (float) $items->sum('qty'),
                'total_paket_terjual' => (float) $paketSummary->sum('qty'),
                'pendapatan_bersih' => $totalPembayaranBersih,
                'total_pembayaran_kotor' => $totalPembayaranKotor,
                'total_pembayaran_void' => $totalPembayaranVoid,
                'total_void_order' => $totalVoidOrder,
                'total_diskon' => $totalDiskon,
                'total_sisa' => (float) $orders->sum('balance'),
                'shift_closed' => (int) $shiftClosed,
            ],
            'sales_summary' => [
                'total_sebelum_diskon' => $totalBrutoItem,
                'total_setelah_diskon' => $totalPenjualan,
            ],
            'payment_by_method' => $paymentByMethod,
            'paket_summary' => $paketSummary->all(),
            'discount_summary' => [
                'diskon_item' => $totalDiskonItem,
                'diskon_order' => $diskonOrder,
                'total_diskon' => $totalDiskon,
            ],
            'kasir_summary' => $kasirSummary,
            'kasir_detail_rows' => $kasirDetailRows,
            'kasir_grouped' => $kasirGrouped,
            'kasir_grand_total' => $kasirGrandTotal,
        ];
    }

    private function normalizeEmailRecipients(array $emails): array
    {
        return collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}

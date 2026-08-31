<?php

namespace App\Services;

use App\Mail\TutupKasirLaporanHarianMail;
use App\Models\MetodePembayaran;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualan;
use App\Models\ShiftKasir;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ShiftKasirEmailReportService
{
    public function sendDailyReport(ShiftKasir $closedShift): array
    {
        $closedShift->loadMissing(['cabang:id,nama,tutup_kasir_email_enabled,tutup_kasir_email_recipients', 'user:id,name,username']);
        $cabang = $closedShift->cabang;

        if (!$cabang || !(bool) $cabang->tutup_kasir_email_enabled) {
            return ['sent' => false, 'reason' => 'disabled'];
        }

        // Sanitize recipients
        $recipients = [];
        foreach ((array) $cabang->tutup_kasir_email_recipients as $email) {
            $email = $this->cleanString($email);
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = strtolower(trim($email));
            }
        }
        $recipients = array_unique(array_filter($recipients));

        if (empty($recipients)) {
            return ['sent' => false, 'reason' => 'recipients_empty'];
        }

        $reportDate = ($closedShift->dibuka_pada ?: now())
            ->copy()
            ->timezone(config('app.timezone'))
            ->toDateString();

        $shiftId = (int) $closedShift->id;
        $orderRows = $shiftId <= 0
            ? collect()
            : PesananPenjualan::query()
                ->where('shift_kasir_id', $shiftId)
                ->get(['id', 'total', 'balance']);

        $orderIds = $orderRows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $totalVoidOrder = empty($orderIds)
            ? 0.0
            : (float) PenjualanVoidLog::query()
                ->whereIn('pesanan_penjualan_id', $orderIds)
                ->whereIn('tipe_void', ['FULL', 'PARTIAL'])
                ->sum('nominal_void');

        $paymentByMethod = PembayaranPenjualan::query()
            ->join('metode_pembayaran as mp', 'mp.id', '=', 'pembayaran_penjualan.metode_pembayaran_id')
            ->where('pembayaran_penjualan.shift_kasir_id', $shiftId)
            ->selectRaw('
                mp.kode,
                mp.nama,
                COALESCE(SUM(CASE WHEN pembayaran_penjualan.nominal > 0 THEN pembayaran_penjualan.nominal ELSE 0 END), 0) as total_kotor,
                COALESCE(ABS(SUM(CASE WHEN pembayaran_penjualan.nominal < 0 THEN pembayaran_penjualan.nominal ELSE 0 END)), 0) as total_void,
                COALESCE(SUM(pembayaran_penjualan.nominal), 0) as total
            ')
            ->groupBy('mp.kode', 'mp.nama')
            ->orderBy('mp.nama')
            ->get()
            ->map(function ($row) {
                return [
                    'kode' => $this->cleanString($row->kode ?? ''),
                    'nama' => $this->cleanString($row->nama ?? ''),
                    'total_kotor' => (float) $row->total_kotor,
                    'total_void' => (float) $row->total_void,
                    'total' => (float) $row->total,
                ];
            })
            ->values();

        $totalPembayaranKotor = (float) $paymentByMethod->sum('total_kotor');
        $totalPembayaranVoid = (float) $paymentByMethod->sum('total_void');
        $pendapatanBersih = (float) $paymentByMethod->sum('total');

        // Build shift detail
        $shiftDetail = $this->buildShiftDetail($closedShift);
        $detailReport = $this->buildKasirDetailReport($closedShift, $reportDate);

        // Build report - only use safe JSON-compatible types
        $report = [
            'cabang_name' => $this->cleanString($cabang->nama ?? '-'),
            'report_date' => $reportDate,
            'report_date_label' => Carbon::parse($reportDate)->format('d-m-Y'),
            'closed_at' => now()->format('d-m-Y H:i'),
            'closed_by' => $this->cleanString($closedShift->user?->name ?? ('User #' . $closedShift->user_id)),
            'cashiers' => [$this->cleanString($closedShift->user?->name ?? ('User #' . $closedShift->user_id))],
            'summary' => [
                'jumlah_shift' => 1,
                'jumlah_transaksi' => (int) $orderRows->count(),
                'total_void_order' => (float) $totalVoidOrder,
                'total_pembayaran_kotor' => (float) $totalPembayaranKotor,
                'total_pembayaran_void' => (float) $totalPembayaranVoid,
                'pendapatan_bersih' => (float) $pendapatanBersih,
                'total_pembayaran' => (float) $pendapatanBersih,
                'total_sisa' => (float) $orderRows->sum('balance'),
            ],
            'payment_by_method' => json_decode(json_encode($paymentByMethod->all()), true),
            'shifts' => $shiftDetail,
            'detail_report' => $detailReport,
        ];

        // Final guard: sanitize every string recursively so queued payload can be JSON-encoded safely.
        $report = $this->sanitizeUtf8Recursive($report);

        // Generate PDF filename only (not the binary)
        $pdfFilename = 'laporan-tutup-kasir-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $this->cleanString($cabang->nama ?? 'cabang')) . '-' . str_replace('-', '', $reportDate) . '.pdf';

        // Validate report array for JSON encoding
        json_encode($report, JSON_THROW_ON_ERROR);

        // Send email synchronously for debugging
        try {
            $mailable = new TutupKasirLaporanHarianMail($report, $pdfFilename);
            Mail::to($recipients)->send($mailable);
        } catch (\Throwable $e) {
            // Log the actual error
            report($e);
            throw $e; // Re-throw to see the real error
        }

        return [
            'sent' => true,
            'reason' => 'sent',
            'recipients_count' => count($recipients),
        ];
    }

    private function buildShiftDetail(ShiftKasir $shift): array
    {
        $cashMethodId = MetodePembayaran::query()->where('kode', 'CASH')->value('id');

        $kasTunai = 0;
        if ($cashMethodId) {
            $kasTunai = (float) DB::table('pembayaran_penjualan')
                ->where('shift_kasir_id', $shift->id)
                ->where('metode_pembayaran_id', $cashMethodId)
                ->sum('nominal');
        }

        // Handle detail_pecahan - it might be stored as JSON string
        $detailPecahanRaw = $shift->detail_pecahan;
        $detailPecahan = [];

        if ($detailPecahanRaw !== null) {
            if (is_string($detailPecahanRaw)) {
                $decoded = json_decode($detailPecahanRaw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $value) {
                        if (is_numeric($value)) {
                            $detailPecahan[(string) $key] = (int) $value;
                        }
                    }
                }
            } elseif (is_array($detailPecahanRaw)) {
                foreach ($detailPecahanRaw as $key => $value) {
                    if (is_numeric($value)) {
                        $detailPecahan[(string) $key] = (int) $value;
                    }
                }
            }
        }

        $userName = $shift->user?->name;
        $userId = $shift->user_id ?? 0;

        return [[
            'id' => (int) $shift->id,
            'kasir' => $this->cleanString($userName ?? ('User #' . $userId)),
            'dibuka_pada' => $shift->dibuka_pada ? $this->cleanString($shift->dibuka_pada->format('d-m-Y H:i')) : '-',
            'ditutup_pada' => $shift->ditutup_pada ? $this->cleanString($shift->ditutup_pada->format('d-m-Y H:i')) : '-',
            'modal_awal' => (float) ($shift->modal_awal ?? 0),
            'kas_tunai' => (float) $kasTunai,
            'kas_expected' => (float) ($shift->kas_expected ?? 0),
            'kas_fisik' => (float) ($shift->kas_fisik ?? 0),
            'selisih' => (float) ($shift->selisih ?? 0),
            'detail_pecahan' => $detailPecahan,
        ]];
    }

    /**
     * Build dataset that mirrors Laporan Kasir Detail (including package items)
     * for one cashier (the one closing shift) and one report date.
     */
    private function buildKasirDetailReport(ShiftKasir $closedShift, string $reportDate): array
    {
        $cabangId = (int) $closedShift->cabang_id;
        $kasirId = (int) $closedShift->user_id;

        $payments = PembayaranPenjualan::query()
            ->with([
                'kasir:id,name,username',
                'metodePembayaran:id,nama,kode',
                'pesananPenjualan:id,nomor_so,customer_name,customer_phone,created_at,cabang_id,total',
                'pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
                'pesananPenjualan.items:id,pesanan_penjualan_id,produk_id,paket_id,qty,harga,diskon,subtotal,is_void',
                'pesananPenjualan.items.produk:id,nama,kode',
                'pesananPenjualan.items.paket:id,nama,kode',
            ])
            ->where('nominal', '>', 0)
            ->whereDate('tanggal_bayar', $reportDate)
            ->where('shift_kasir_id', (int) $closedShift->id)
            ->where('kasir_user_id', $kasirId)
            ->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            })
            ->orderBy('tanggal_bayar')
            ->get();

        $orderIds = $payments
            ->pluck('pesanan_penjualan_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $historyPaymentByOrder = collect();
        if (!empty($orderIds)) {
            $historyPaymentByOrder = PembayaranPenjualan::query()
                ->whereIn('pesanan_penjualan_id', $orderIds)
                ->where('nominal', '>', 0)
                ->whereDate('tanggal_bayar', '<', $reportDate)
                ->get(['pesanan_penjualan_id', 'tipe', 'nominal'])
                ->groupBy('pesanan_penjualan_id');
        }

        $metodeColumns = MetodePembayaran::query()
            ->whereIn('id', $payments->pluck('metode_pembayaran_id')->filter()->unique()->values()->all())
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        $metodeIds = $metodeColumns->pluck('id')->map(fn ($id) => (int) $id)->all();

        $orderRows = $payments
            ->groupBy('pesanan_penjualan_id')
            ->map(function ($orderPaymentRows) use ($reportDate, $metodeIds, $historyPaymentByOrder) {
                $first = $orderPaymentRows->first();
                $order = $first->pesananPenjualan;
                $orderId = (int) ($order->id ?? 0);
                $items = collect($order?->items ?? [])
                    ->where('is_void', false)
                    ->values()
                    ->map(function ($item) {
                        $qty = (float) $item->qty;
                        $harga = (float) $item->harga;
                        $diskon = (float) $item->diskon;

                        return [
                            'kode' => $item->produk?->kode ?? $item->paket?->kode ?? '-',
                            'nama' => $item->produk?->nama ?? $item->paket?->nama ?? '-',
                            'qty' => $qty,
                            'harga' => $harga,
                            'diskon' => $diskon,
                            'item_total' => $qty * $harga,
                        ];
                    });

                $todayDp = (float) $orderPaymentRows->where('tipe', 'DP')->sum('nominal');
                $todayLunas = (float) $orderPaymentRows->where('tipe', '!=', 'DP')->sum('nominal');
                $historyRows = collect($historyPaymentByOrder->get($orderId, collect()));
                $historyDp = (float) $historyRows->where('tipe', 'DP')->sum('nominal');
                $historyLunas = (float) $historyRows->where('tipe', '!=', 'DP')->sum('nominal');

                $paymentsByMethod = array_fill_keys($metodeIds, 0.0);
                foreach ($orderPaymentRows as $pay) {
                    $metodeKey = (int) ($pay->metode_pembayaran_id ?? 0);
                    if (!isset($paymentsByMethod[$metodeKey])) {
                        $paymentsByMethod[$metodeKey] = 0.0;
                    }
                    $paymentsByMethod[$metodeKey] += (float) $pay->nominal;
                }

                return [
                    'nomor_ko' => $order?->kantongOrder?->nomor_ko ?? '-',
                    'customer_name' => $order?->customer_name ?? '-',
                    'customer_member' => $order?->customer_phone ?? '-',
                    'order_date' => $order?->created_at,
                    'jam_bayar' => $first?->tanggal_bayar?->format('H:i') ?? '-',
                    'items' => $items,
                    'total_tagihan_order' => (float) ($order?->total ?? 0),
                    'history_dp' => $historyDp,
                    'history_lunas' => $historyLunas,
                    'today_dp' => $todayDp,
                    'today_lunas' => $todayLunas,
                    'payments_by_method' => $paymentsByMethod,
                ];
            })
            ->sortBy('order_date')
            ->values();

        $tableRows = [];
        foreach ($orderRows as $orderRow) {
            $items = $orderRow['items'];
            if ($items->isEmpty()) {
                $items = collect([[
                    'kode' => '-',
                    'nama' => '-',
                    'qty' => 0,
                    'harga' => 0,
                    'diskon' => 0,
                    'item_total' => 0,
                ]]);
            }

            foreach ($items->values() as $index => $item) {
                $isFirst = $index === 0;
                $tableRows[] = [
                    'jam' => $isFirst ? $orderRow['jam_bayar'] : '',
                    'ko' => $isFirst ? $orderRow['nomor_ko'] : '',
                    'member' => $isFirst ? $orderRow['customer_member'] : '',
                    'nama_customer' => $isFirst ? $orderRow['customer_name'] : '',
                    'kode' => $item['kode'],
                    'jenis' => $item['nama'],
                    'qty' => (float) $item['qty'],
                    'harga' => (float) $item['harga'],
                    'disc' => (float) ($item['diskon'] ?? 0),
                    'item_total' => (float) ($item['item_total'] ?? 0),
                    'total' => $isFirst ? (float) $orderRow['total_tagihan_order'] : '',
                    'order_lalu_dp' => $isFirst ? (float) $orderRow['history_dp'] : '',
                    'order_lalu_lunas' => $isFirst ? (float) $orderRow['history_lunas'] : '',
                    'order_hari_ini_dp' => $isFirst ? (float) $orderRow['today_dp'] : '',
                    'order_hari_ini_lunas' => $isFirst ? (float) $orderRow['today_lunas'] : '',
                    'pembayaran' => $isFirst ? $orderRow['payments_by_method'] : null,
                ];
            }
        }

        $totals = [
            'order_lalu_dp' => 0.0,
            'order_lalu_lunas' => 0.0,
            'order_hari_ini_dp' => 0.0,
            'order_hari_ini_lunas' => 0.0,
            'metode' => array_fill_keys($metodeIds, 0.0),
            'omzet_penjualan' => (float) $payments->sum('nominal'),
            'setoran' => (float) ($closedShift->kas_fisik ?? 0),
            'selisih' => 0.0,
            'total_internal' => 0.0,
            'total_prive' => 0.0,
            'total_voucher' => 0.0,
        ];

        foreach ($orderRows as $orderRow) {
            $totals['order_lalu_dp'] += (float) $orderRow['history_dp'];
            $totals['order_lalu_lunas'] += (float) $orderRow['history_lunas'];
            $totals['order_hari_ini_dp'] += (float) $orderRow['today_dp'];
            $totals['order_hari_ini_lunas'] += (float) $orderRow['today_lunas'];
            foreach ($orderRow['payments_by_method'] as $metodeId => $nominal) {
                $metodeKey = (int) $metodeId;
                $totals['metode'][$metodeKey] = (float) ($totals['metode'][$metodeKey] ?? 0) + (float) $nominal;
            }
        }

        $totalsByCode = $metodeColumns->mapWithKeys(function ($method) {
            return [(int) $method->id => strtoupper(trim((string) $method->kode))];
        });
        $sumByCodes = function (array $codes) use ($totalsByCode, $totals): float {
            return (float) collect($totals['metode'])->reduce(function ($carry, $nominal, $metodeId) use ($totalsByCode, $codes) {
                $kode = (string) ($totalsByCode->get((int) $metodeId) ?? '');
                if (in_array($kode, $codes, true)) {
                    return (float) $carry + (float) $nominal;
                }

                return (float) $carry;
            }, 0.0);
        };
        $cashTransaksiHariIni = $sumByCodes(['CASH', 'TUNAI']);
        $totals['selisih'] = $totals['setoran'] - $cashTransaksiHariIni;
        $totals['total_internal'] = $sumByCodes(['INT+PRIV', 'INT_PRIV', 'INTERNAL', 'INTPRIV']);
        $totals['total_prive'] = $sumByCodes(['PRIVE', 'PRIV', 'WASTE']);
        $totals['total_voucher'] = $sumByCodes(['VOUCHER']);

        return [
            'report_date' => $reportDate,
            'kasir_label' => strtolower((string) ($closedShift->user?->username ?? $closedShift->user?->name ?? ('kasir-' . $kasirId))),
            'metode_columns' => $metodeColumns->map(fn ($m) => [
                'id' => (int) $m->id,
                'nama' => $this->cleanString((string) $m->nama),
                'kode' => $this->cleanString((string) $m->kode),
            ])->values()->all(),
            'table_rows' => $tableRows,
            'totals' => $totals,
        ];
    }

    /**
     * Clean string to ensure valid UTF-8 for JSON encoding
     */
    private function cleanString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        // Force valid UTF-8: drop invalid byte sequences that can break queue payload JSON encoding.
        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            } else {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }

        // Remove control characters except newlines and tabs
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /**
     * Recursively sanitize arrays/objects so every string value is valid UTF-8.
     */
    private function sanitizeUtf8Recursive(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $safeKey = is_string($key) ? $this->cleanString($key) : $key;
                $result[$safeKey] = $this->sanitizeUtf8Recursive($item);
            }
            return $result;
        }

        if (is_object($value)) {
            return $this->sanitizeUtf8Recursive((array) $value);
        }

        if (is_string($value)) {
            return $this->cleanString($value);
        }

        return $value;
    }

}

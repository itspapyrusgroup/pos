<?php

namespace App\Http\Controllers;

use App\Models\MetodePembayaran;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanVoidLog;
use App\Models\PesananPenjualan;
use App\Models\PesananPenjualanItem;
use App\Models\ShiftKasir;
use App\Models\User;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanKasirController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'kasir_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $kasirId = isset($validated['kasir_user_id']) ? (int) $validated['kasir_user_id'] : null;

        $baseQuery = PesananPenjualan::query()
            ->whereNotNull('kasir_user_id')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
        $this->applyCabangScope($baseQuery);
        if ($cabangId) {
            $baseQuery->where('cabang_id', $cabangId);
        }

        $kasirIds = (clone $baseQuery)
            ->distinct()
            ->pluck('kasir_user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $kasirList = User::query()
            ->whereIn('id', $kasirIds)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        if ($kasirId) {
            $baseQuery->where('kasir_user_id', $kasirId);
        }

        $orderRows = (clone $baseQuery)->get(['id', 'kasir_user_id', 'total', 'balance', 'diskon_otomatis']);
        $orderIds = $orderRows->pluck('id')->map(fn ($id) => (int) $id)->all();

        $voidByKasir = PenjualanVoidLog::query()
            ->from('penjualan_void_logs as pvl')
            ->join('pesanan_penjualan as pz', 'pz.id', '=', 'pvl.pesanan_penjualan_id')
            ->whereNotNull('pz.kasir_user_id')
            ->whereIn('pvl.tipe_void', ['FULL', 'PARTIAL'])
            ->whereDate('pvl.void_effective_date', '>=', $dateFrom)
            ->whereDate('pvl.void_effective_date', '<=', $dateTo);
        $allowedCabangIds = $this->accessibleCabangIds();
        if (!empty($allowedCabangIds)) {
            $voidByKasir->whereIn('pz.cabang_id', $allowedCabangIds);
        }
        if ($cabangId) {
            $voidByKasir->where('pz.cabang_id', $cabangId);
        }
        if ($kasirId) {
            $voidByKasir->where('pz.kasir_user_id', $kasirId);
        }
        $voidByKasir = $voidByKasir
            ->selectRaw('pz.kasir_user_id, SUM(pvl.nominal_void) as total_void')
            ->groupBy('pz.kasir_user_id')
            ->pluck('total_void', 'pz.kasir_user_id');

        $discountBaseByOrder = collect();
        if (!empty($orderIds)) {
            $discountBaseByOrder = PesananPenjualanItem::query()
                ->selectRaw('pesanan_penjualan_id, SUM(diskon) as total_diskon_item, SUM(subtotal) as total_subtotal_item')
                ->whereIn('pesanan_penjualan_id', $orderIds)
                ->where('is_void', false)
                ->groupBy('pesanan_penjualan_id')
                ->get()
                ->keyBy('pesanan_penjualan_id');
        }

        $salesGrouped = $orderRows
            ->groupBy('kasir_user_id')
            ->map(function ($rows, $kasirUserId) use ($voidByKasir, $discountBaseByOrder) {
                $totalPenjualanNet = (float) $rows->sum('total');
                $totalSisa = (float) $rows->sum('balance');
                $totalVoid = (float) ($voidByKasir[(int) $kasirUserId] ?? 0);
                $totalDiskon = (float) $rows->sum(function ($row) use ($discountBaseByOrder) {
                    $base = $discountBaseByOrder->get($row->id);
                    $diskonItem = (float) ($base->total_diskon_item ?? 0);
                    $diskonOtomatis = (float) ($row->diskon_otomatis ?? 0);

                    return $diskonItem + $diskonOtomatis;
                });

                return (object) [
                    'kasir_user_id' => (int) $kasirUserId,
                    'jumlah_transaksi' => (int) $rows->count(),
                    'total_penjualan' => $totalPenjualanNet,
                    'total_void' => $totalVoid,
                    'total_diskon' => $totalDiskon,
                    'total_penjualan_kotor' => $totalPenjualanNet + $totalVoid + $totalDiskon,
                    'total_sisa' => $totalSisa,
                ];
            });

        $paymentQuery = PembayaranPenjualan::query()
            ->whereNotNull('kasir_user_id')
            ->where('nominal', '>', 0)
            ->whereDate('tanggal_bayar', '>=', $dateFrom)
            ->whereDate('tanggal_bayar', '<=', $dateTo);

        $paymentQuery->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
            $this->applyCabangScope($q);
            if ($cabangId) {
                $q->where('cabang_id', $cabangId);
            }
        });

        if ($kasirId) {
            $paymentQuery->where('kasir_user_id', $kasirId);
        }

        $paymentGrouped = (clone $paymentQuery)
            ->selectRaw('
                kasir_user_id,
                COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total_pembayaran_kotor
            ')
            ->groupBy('kasir_user_id')
            ->get()
            ->keyBy('kasir_user_id');

        $rekapKasir = collect($salesGrouped->keys()->merge($paymentGrouped->keys())->merge($voidByKasir->keys())->unique()->values())
            ->map(function ($id) use ($salesGrouped, $paymentGrouped, $voidByKasir) {
                $sales = $salesGrouped->get($id);
                $pay = $paymentGrouped->get($id);
                $totalVoid = (float) ($sales->total_void ?? $voidByKasir[(int) $id] ?? 0);
                $totalPembayaranKotor = (float) ($pay->total_pembayaran_kotor ?? 0);
                return [
                    'kasir_user_id' => (int) $id,
                    'jumlah_transaksi' => (float) ($sales->jumlah_transaksi ?? 0),
                    'total_penjualan_kotor' => (float) (($sales->total_penjualan ?? 0) + $totalVoid + ($sales->total_diskon ?? 0)),
                    'total_void' => $totalVoid,
                    'total_diskon' => (float) ($sales->total_diskon ?? 0),
                    'total_penjualan' => (float) ($sales->total_penjualan ?? 0),
                    'total_pembayaran_kotor' => $totalPembayaranKotor,
                    'total_pembayaran_void' => $totalVoid,
                    'total_pembayaran' => $totalPembayaranKotor,
                    'total_sisa' => (float) ($sales->total_sisa ?? 0),
                ];
            })
            ->sortByDesc('total_penjualan')
            ->values();

        $usersMap = User::query()
            ->whereIn('id', $rekapKasir->pluck('kasir_user_id')->all())
            ->get(['id', 'name', 'username'])
            ->keyBy('id');

        $transactions = (clone $baseQuery)
            ->with([
                'kasir:id,name,username',
                'cabang:id,nama',
                'kantongOrder:id,pesanan_penjualan_id,nomor_ko',
            ])
            ->withSum(['voidLogs as void_total_order' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereIn('tipe_void', ['FULL', 'PARTIAL']);
                $q->whereDate('void_effective_date', '>=', $dateFrom)
                    ->whereDate('void_effective_date', '<=', $dateTo);
            }], 'nominal_void')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $shiftQuery = ShiftKasir::query()
            ->with(['user:id,name,username', 'cabang:id,nama'])
            ->whereDate('dibuka_pada', '>=', $dateFrom)
            ->whereDate('dibuka_pada', '<=', $dateTo);
        $this->applyCabangScope($shiftQuery);
        if ($cabangId) {
            $shiftQuery->where('cabang_id', $cabangId);
        }
        if ($kasirId) {
            $shiftQuery->where('user_id', $kasirId);
        }

        $cashByShift = PembayaranPenjualan::query()
            ->selectRaw('shift_kasir_id, SUM(nominal) as total_tunai')
            ->whereNotNull('shift_kasir_id')
            ->whereHas('metodePembayaran', function ($q) {
                $q->where('kode', 'CASH');
            })
            ->groupBy('shift_kasir_id')
            ->pluck('total_tunai', 'shift_kasir_id');

        $shiftRows = $shiftQuery->latest('id')->get()->map(function (ShiftKasir $shift) use ($cashByShift) {
            $pendapatanTunai = (float) ($cashByShift[$shift->id] ?? 0);
            $setoranFisik = $shift->status === 'CLOSED'
                ? (float) ($shift->kas_fisik ?? 0)
                : 0.0;

            return [
                'id' => $shift->id,
                'status' => $shift->status,
                'kasir' => $shift->user,
                'cabang' => $shift->cabang,
                'dibuka_pada' => $shift->dibuka_pada,
                'ditutup_pada' => $shift->ditutup_pada,
                'pendapatan_tunai' => $pendapatanTunai,
                'setoran_fisik' => $setoranFisik,
                'selisih_setoran' => $setoranFisik - $pendapatanTunai,
                'kas_expected' => (float) ($shift->kas_expected ?? 0),
                'selisih_shift' => (float) ($shift->selisih ?? 0),
            ];
        });

        $totalVoid = $voidByKasir->sum(fn ($value) => (float) $value);
        $totalPenjualanNet = (float) $orderRows->sum('total');
        $totalDiskon = (float) $orderRows->sum(function ($row) use ($discountBaseByOrder) {
            $base = $discountBaseByOrder->get($row->id);
            $diskonItem = (float) ($base->total_diskon_item ?? 0);
            $diskonOtomatis = (float) ($row->diskon_otomatis ?? 0);

            return $diskonItem + $diskonOtomatis;
        });

        if ($request->boolean('export_xlsx')) {
            $rowsXlsx = $rekapKasir->map(function (array $row) use ($usersMap) {
                $kasir = $usersMap->get($row['kasir_user_id']);
                return [
                    $kasir?->name ?? 'User #' . $row['kasir_user_id'],
                    (float) $row['jumlah_transaksi'],
                    (float) $row['total_penjualan_kotor'],
                    (float) $row['total_void'],
                    (float) $row['total_diskon'],
                    (float) $row['total_penjualan'],
                    (float) ($row['total_pembayaran_kotor'] ?? 0),
                    (float) ($row['total_pembayaran_void'] ?? 0),
                    (float) $row['total_pembayaran'],
                    (float) $row['total_sisa'],
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-kasir-' . now()->format('Ymd-His') . '.xlsx',
                ['Kasir', 'Jumlah Transaksi', 'Penjualan Kotor', 'Void', 'Diskon', 'Penjualan Bersih', 'Kas Masuk Kotor', 'Void/Refund Kas', 'Kas Bersih', 'Total Sisa'],
                $rowsXlsx,
                'Kasir'
            );
        }

        return view('pages.pos.laporan-kasir', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'kasirList' => $kasirList,
            'usersMap' => $usersMap,
            'rekapKasir' => $rekapKasir,
            'transactions' => $transactions,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'kasir_user_id' => $kasirId,
            ],
            'summary' => [
                'jumlah_transaksi' => (clone $baseQuery)->count(),
                'total_penjualan_kotor' => $totalPenjualanNet + $totalVoid + $totalDiskon,
                'total_void' => $totalVoid,
                'total_diskon' => $totalDiskon,
                'total_penjualan' => $totalPenjualanNet,
                'total_pembayaran_kotor' => (float) (clone $paymentQuery)
                    ->selectRaw('COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total')
                    ->value('total'),
                'total_pembayaran_void' => $totalVoid,
                'total_pembayaran' => (float) (clone $paymentQuery)
                    ->selectRaw('COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total')
                    ->value('total'),
                'total_sisa' => (float) $orderRows->sum('balance'),
            ],
            'shiftRows' => $shiftRows,
        ]);
    }

    public function detail(Request $request)
    {
        $validated = $request->validate([
            'report_date' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'kasir_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $reportDate = $validated['report_date'] ?? now()->toDateString();
        $cabangId = $this->resolveCabangFilter($request);
        $kasirId = isset($validated['kasir_user_id']) ? (int) $validated['kasir_user_id'] : null;

        $paymentQuery = PembayaranPenjualan::query()
            ->with([
                'kasir:id,name,username',
                'metodePembayaran:id,nama,kode',
                'pesananPenjualan:id,nomor_so,customer_name,customer_phone,created_at,cabang_id,total,status_pembayaran',
                'pesananPenjualan.cabang:id,nama',
                'pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
                'pesananPenjualan.items:id,pesanan_penjualan_id,produk_id,paket_id,qty,harga,diskon,subtotal,is_void',
                'pesananPenjualan.items.produk:id,nama,kode',
                'pesananPenjualan.items.paket:id,nama,kode',
            ])
            ->where('nominal', '>', 0)
            ->whereDate('tanggal_bayar', $reportDate)
            ->whereHas('pesananPenjualan', function ($q) {
                // Exclude payments from voided orders
                $q->whereNotIn('status_pembayaran', ['VOID', 'CANCELLED']);
            });

        $paymentQuery->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
            $this->applyCabangScope($q);
            if ($cabangId) {
                $q->where('cabang_id', $cabangId);
            }
        });

        if ($kasirId) {
            $paymentQuery->where('kasir_user_id', $kasirId);
        }

        $payments = $paymentQuery
            ->orderBy('kasir_user_id')
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

        $shiftSetoranByKasir = ShiftKasir::query()
            ->whereDate('dibuka_pada', $reportDate)
            ->where('status', 'CLOSED');
        $this->applyCabangScope($shiftSetoranByKasir);
        if ($cabangId) {
            $shiftSetoranByKasir->where('cabang_id', $cabangId);
        }
        if ($kasirId) {
            $shiftSetoranByKasir->where('user_id', $kasirId);
        }
        $shiftSetoranByKasir = $shiftSetoranByKasir
            ->selectRaw('user_id, COALESCE(SUM(kas_fisik), 0) as total_setoran')
            ->groupBy('user_id')
            ->pluck('total_setoran', 'user_id');

        $kasirList = User::query()
            ->whereIn('id', $payments->pluck('kasir_user_id')->filter()->unique()->values()->all())
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        $metodeColumns = MetodePembayaran::query()
            ->whereIn('id', $payments->pluck('metode_pembayaran_id')->filter()->unique()->values()->all())
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);
        $metodeIds = $metodeColumns->pluck('id')->map(fn ($id) => (int) $id)->all();

        $kasirGroups = $payments
            ->groupBy(fn ($row) => (int) ($row->kasir_user_id ?? 0))
            ->map(function ($rows, $kasirUserId) use ($reportDate, $metodeIds, $metodeColumns, $historyPaymentByOrder, $shiftSetoranByKasir) {
                $orderRows = $rows
                    ->groupBy('pesanan_penjualan_id')
                    ->map(function ($orderPaymentRows) use ($reportDate, $metodeIds, $historyPaymentByOrder) {
                        $first = $orderPaymentRows->first();
                        $order = $first->pesananPenjualan;
                        $orderId = (int) ($order->id ?? 0);
                        $orderDate = $order?->created_at?->toDateString();
                        $isOrderToday = $orderDate === $reportDate;
                        $items = collect($order?->items ?? [])
                            ->where('is_void', false)
                            ->values()
                            ->map(function ($item) {
                                $qty = (float) $item->qty;
                                $harga = (float) $item->harga;
                                $diskon = (float) $item->diskon;

                                return [
                                    'jenis' => $item->paket_id ? 'PAKET' : 'ITEM',
                                    'kode' => $item->produk?->kode ?? $item->paket?->kode ?? '-',
                                    'nama' => $item->produk?->nama ?? $item->paket?->nama ?? '-',
                                    'qty' => $qty,
                                    'harga' => $harga,
                                    'diskon' => $diskon,
                                    // @Item mengikuti format PSnaps: qty x harga sebelum diskon
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
                            'order_id' => (int) ($order->id ?? 0),
                            'nomor_so' => $order?->nomor_so ?? '-',
                            'nomor_ko' => $order?->kantongOrder?->nomor_ko ?? '-',
                            'customer_name' => $order?->customer_name ?? '-',
                            'customer_member' => $order?->customer_phone ?? '-',
                            'order_date' => $order?->created_at,
                            'jam_bayar' => $first?->tanggal_bayar?->format('H:i') ?? '-',
                            'is_order_today' => $isOrderToday,
                            'items' => $items,
                            'total_tagihan_order' => (float) ($order?->total ?? 0),
                            'history_dp' => $historyDp,
                            'history_lunas' => $historyLunas,
                            'today_dp' => $todayDp,
                            'today_lunas' => $todayLunas,
                            'payments_by_method' => $paymentsByMethod,
                            'total_pembayaran_hari_ini' => (float) $orderPaymentRows->sum('nominal'),
                        ];
                    })
                    ->sortBy('order_date')
                    ->values();

                $tableRows = [];
                foreach ($orderRows as $orderRow) {
                    $items = $orderRow['items'];
                    if ($items->isEmpty()) {
                        $items = collect([[
                            'jenis' => '-',
                            'kode' => '-',
                            'nama' => '-',
                            'qty' => 0,
                            'harga' => 0,
                            'diskon' => 0,
                            'item_total' => 0,
                            'subtotal' => 0,
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
                            'item_total' => (float) ($item['item_total'] ?? (($item['qty'] ?? 0) * ($item['harga'] ?? 0))),
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
                    'omzet_penjualan' => 0.0,
                    'setoran' => 0.0,
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

                $totals['omzet_penjualan'] = (float) $rows->sum('nominal');
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
                $totals['setoran'] = (float) ($shiftSetoranByKasir[(int) $kasirUserId] ?? 0);
                $totals['selisih'] = $totals['setoran'] - $cashTransaksiHariIni;
                $totals['total_internal'] = $sumByCodes(['INT+PRIV', 'INT_PRIV', 'INTERNAL', 'INTPRIV']);
                $totals['total_prive'] = $sumByCodes(['PRIVE', 'PRIV', 'WASTE']);
                $totals['total_voucher'] = $sumByCodes(['VOUCHER']);

                return [
                    'kasir_user_id' => (int) $kasirUserId,
                    'kasir' => $rows->first()?->kasir,
                    'jumlah_pembayaran' => (int) $rows->count(),
                    'total_pembayaran' => (float) $rows->sum('nominal'),
                    'table_rows' => $tableRows,
                    'totals' => $totals,
                ];
            })
            ->sortByDesc('total_pembayaran')
            ->values();

        return view('pages.pos.laporan-kasir-detail', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'kasirList' => $kasirList,
            'filters' => [
                'report_date' => $reportDate,
                'cabang_id' => $cabangId,
                'kasir_user_id' => $kasirId,
            ],
            'metodeColumns' => $metodeColumns,
            'kasirGroups' => $kasirGroups,
            'summary' => [
                'jumlah_kasir' => (int) $kasirGroups->count(),
                'jumlah_pembayaran' => (int) $payments->count(),
                'total_pembayaran' => (float) $payments->sum('nominal'),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\CabangSalesMode;
use App\Models\BookingStudio;
use App\Models\DiskonOtomatis;
use App\Models\KantongOrder;
use App\Models\KartuStok;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
use App\Models\PembayaranPenjualan;
use App\Models\PenjualanRequestLog;
use App\Models\PenjualanVoidLog;
use App\Models\PenjualanVoidOtp;
use App\Models\PesananPenjualan;
use App\Models\PesananPenjualanItem;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\ShiftKasir;
use App\Models\TemplateHargaItem;
use App\Models\User;
use App\Models\VoucherPromosi;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TransaksiPenjualanController extends Controller
{
    private array $allowMinusStockCabangCache = [];

    public function index()
    {
        $cabangDefaultId = $this->activeCabangId();
        $salesModeCabang = CabangSalesMode::query()
            ->with('salesMode')
            ->where('cabang_id', $cabangDefaultId)
            ->where('status', true)
            ->get();

        $activeCabang = Cabang::query()->find($cabangDefaultId);
        $cabangTersedia = $this->accessibleCabangQuery()->get(['id', 'nama']);
        $userAktif = \App\Models\User::query()
            ->with([
                'role:id,nama',
                'cabang:id,nama',
                'karyawan:id,user_id,divisi_id,jabatan_id',
                'karyawan.divisi:id,nama',
                'karyawan.jabatan:id,nama,level',
            ])
            ->where('status', true)
            ->when($cabangDefaultId > 0, function ($query) use ($cabangDefaultId) {
                $query->whereHas('cabang', function ($inner) use ($cabangDefaultId) {
                    $inner->where('cabang.id', $cabangDefaultId);
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        $studioUsers = $userAktif->filter(function ($user) {
            $divisiNama = strtoupper(trim((string) ($user->karyawan?->divisi?->nama ?? '')));
            return $divisiNama === 'STUDIO';
        })->values();

        $csCandidates = $userAktif->values();
        $spvCandidates = $userAktif->values();
        $fotograferCandidates = $studioUsers;
        $staleOpenShift = null;
        if ($cabangDefaultId && Auth::id()) {
            $staleOpenShift = ShiftKasir::query()
                ->where('cabang_id', $cabangDefaultId)
                ->where('user_id', (int) Auth::id())
                ->where('status', 'OPEN')
                ->whereDate('dibuka_pada', '<', now()->toDateString())
                ->latest('id')
                ->first();
        }

        return view('pages.pos.transaksi-penjualan', [
            'metodePembayaran' => MetodePembayaran::query()
                ->where('status', true)
                ->whereHas('cabang', function ($q) use ($cabangDefaultId) {
                    $q->where('cabang.id', $cabangDefaultId);
                })
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama']),
            'cabangDefaultId' => $cabangDefaultId,
            'activeCabang' => $activeCabang,
            'cabangTersedia' => $cabangTersedia,
            'csCandidates' => $csCandidates,
            'spvCandidates' => $spvCandidates,
            'fotograferCandidates' => $fotograferCandidates,
            'canTransaksiBackdate' => (bool) auth()->user()?->hasPermission('pos.transaksi.backdate'),
            'staleOpenShiftDate' => $staleOpenShift?->dibuka_pada?->format('d-m-Y'),
            'salesModesCabang' => $salesModeCabang->map(function ($item) {
                return [
                    'sales_mode_id' => $item->sales_mode_id,
                    'template_harga_id' => $item->template_harga_id,
                    'nama' => $item->salesMode?->nama ?? 'Sales Mode',
                ];
            })->values(),
        ]);
    }

    public function riwayat(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $today = now()->toDateString();
        $canBackdate = (bool) auth()->user()?->hasPermission('pos.riwayat.backdate');
        $dateFromInput = $validated['date_from'] ?? null;
        $dateToInput = $validated['date_to'] ?? null;

        if (!$canBackdate) {
            $hasBackdateRequest = ($dateFromInput && $dateFromInput < $today)
                || ($dateToInput && $dateToInput < $today);

            if ($hasBackdateRequest) {
                return redirect()
                    ->route('riwayat-penjualan')
                    ->with('error', 'Anda tidak memiliki izin untuk melihat riwayat tanggal sebelumnya.');
            }
        }

        $dateFrom = $canBackdate ? ($dateFromInput ?: $today) : $today;
        $dateTo = $canBackdate ? ($dateToInput ?: $today) : $today;

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $activeCabangId = $this->activeCabangId();
        $query = PesananPenjualan::query()
            ->with(['pelanggan', 'kantongOrder'])
            ->withSum(['voidLogs as void_total_order' => function ($q) {
                $q->whereIn('tipe_void', ['FULL', 'PARTIAL']);
            }], 'nominal_void')
            ->latest('id');
        $this->applyCabangScope($query);
        if ($activeCabangId) {
            $query->where('cabang_id', $activeCabangId);
        }

        $query->whereDate('created_at', '>=', $dateFrom);
        $query->whereDate('created_at', '<=', $dateTo);
        if ($request->filled('customer')) {
            $keyword = $request->input('customer');
            $query->where(function ($q) use ($keyword) {
                $q->where('customer_name', 'like', '%' . $keyword . '%')
                    ->orWhere('customer_phone', 'like', '%' . $keyword . '%')
                    ->orWhereHas('pelanggan', function ($inner) use ($keyword) {
                        $inner->where('nama', 'like', '%' . $keyword . '%')
                            ->orWhere('no_hp', 'like', '%' . $keyword . '%');
                    });
            });
        }
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $query->whereHas('kantongOrder', function ($q) use ($keyword) {
                $q->where('nomor_ko', 'like', '%' . $keyword . '%');
            });
        }
        if ($request->filled('status_pembayaran')) {
            $query->where('status_pembayaran', $request->input('status_pembayaran'));
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('pages.pos.riwayat-penjualan', [
            'orders' => $orders,
            'activeCabang' => Cabang::query()->find($activeCabangId),
            'cabangTersedia' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'cabangDefaultId' => $activeCabangId,
            'canBackdate' => $canBackdate,
            'filterDateFrom' => $dateFrom,
            'filterDateTo' => $dateTo,
        ]);
    }

    public function laporan(Request $request)
    {
        $cabangId = $this->resolveCabangFilter($request);

        $query = PesananPenjualan::query()
            ->with(['pelanggan', 'kantongOrder', 'cabang'])
            ->latest('created_at');
        $this->applyCabangScope($query);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $query->whereHas('kantongOrder', function ($q) use ($keyword) {
                $q->where('nomor_ko', 'like', '%' . $keyword . '%');
            });
        }

        $summaryQuery = clone $query;

        $orders = $query
            ->withSum(['voidLogs as void_total_order' => function ($q) use ($request) {
                $q->whereIn('tipe_void', ['FULL', 'PARTIAL']);
                if ($request->filled('date_from')) {
                    $q->whereDate('void_effective_date', '>=', $request->input('date_from'));
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('void_effective_date', '<=', $request->input('date_to'));
                }
            }], 'nominal_void')
            ->withSum(['pembayaran as paid_total_period' => function ($q) use ($request) {
                if ($request->filled('date_from')) {
                    $q->whereDate('tanggal_bayar', '>=', $request->input('date_from'));
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('tanggal_bayar', '<=', $request->input('date_to'));
                }
            }], 'nominal')
            ->paginate(15)
            ->withQueryString();

        $paymentQuery = PembayaranPenjualan::query();
        $allowedCabangIds = $this->accessibleCabangIds();

        if (!empty($allowedCabangIds)) {
            $paymentQuery->whereHas('pesananPenjualan', function ($q) use ($allowedCabangIds) {
                $q->whereIn('cabang_id', $allowedCabangIds);
            });
        }
        if ($request->filled('date_from')) {
            $paymentQuery->whereDate('tanggal_bayar', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $paymentQuery->whereDate('tanggal_bayar', '<=', $request->input('date_to'));
        }
        if ($cabangId) {
            $paymentQuery->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $paymentQuery->whereHas('pesananPenjualan.kantongOrder', function ($q) use ($keyword) {
                $q->where('nomor_ko', 'like', '%' . $keyword . '%');
            });
        }

        $paymentSummaryQuery = clone $paymentQuery;

        $paymentDaily = (clone $paymentQuery)
            ->selectRaw('
                DATE(tanggal_bayar) as tanggal,
                COALESCE(SUM(CASE WHEN nominal > 0 THEN 1 ELSE 0 END), 0) as jumlah_pembayaran,
                COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total_pendapatan_kotor,
                COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total_pendapatan
            ')
            ->groupBy(DB::raw('DATE(tanggal_bayar)'))
            ->orderByDesc('tanggal')
            ->get();

        $voidQuery = PenjualanVoidLog::query()
            ->whereIn('tipe_void', ['FULL', 'PARTIAL']);

        if (!empty($allowedCabangIds)) {
            $voidQuery->whereHas('order', function ($q) use ($allowedCabangIds) {
                $q->whereIn('cabang_id', $allowedCabangIds);
            });
        }
        if ($request->filled('date_from')) {
            $voidQuery->whereDate('void_effective_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $voidQuery->whereDate('void_effective_date', '<=', $request->input('date_to'));
        }
        if ($cabangId) {
            $voidQuery->whereHas('order', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $voidQuery->whereHas('order.kantongOrder', function ($q) use ($keyword) {
                $q->where('nomor_ko', 'like', '%' . $keyword . '%');
            });
        }

        $voidSummaryQuery = clone $voidQuery;

        $voidDaily = (clone $voidQuery)
            ->selectRaw('void_effective_date as tanggal, COUNT(*) as jumlah_void, SUM(nominal_void) as total_void')
            ->groupBy('void_effective_date')
            ->orderByDesc('void_effective_date')
            ->get();

        $voidByTanggal = $voidDaily->keyBy(function ($row) {
            return (string) $row->tanggal;
        });

        $paymentDaily = $paymentDaily->map(function ($row) use ($voidByTanggal) {
            $tanggal = (string) $row->tanggal;
            $voidNominal = (float) ($voidByTanggal->get($tanggal)->total_void ?? 0);
            $kotor = (float) ($row->total_pendapatan_kotor ?? 0);

            $row->total_void_pembayaran = $voidNominal;
            $row->total_pendapatan = $kotor;

            return $row;
        });

        $summaryOrders = (clone $summaryQuery)
            ->withSum(['voidLogs as void_total_order' => function ($q) use ($request) {
                $q->whereIn('tipe_void', ['FULL', 'PARTIAL']);
                if ($request->filled('date_from')) {
                    $q->whereDate('void_effective_date', '>=', $request->input('date_from'));
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('void_effective_date', '<=', $request->input('date_to'));
                }
            }], 'nominal_void')
            ->get(['id', 'total', 'balance']);

        // Gross penjualan: total bersih order + total void yang efektif pada periode filter
        $grossTotal = (float) $summaryOrders->sum(function ($order) {
            return (float) $order->total + (float) ($order->void_total_order ?? 0);
        });

        // Void utama untuk laporan: wajib berdasarkan void_effective_date
        $voidTotal = (float) (clone $voidSummaryQuery)->sum('nominal_void');

        // Net penjualan laporan = gross penjualan - pengurangan void berdasarkan void_effective_date
        $netTotal = $grossTotal - $voidTotal;

        $paidGrossTotal = (float) (clone $paymentSummaryQuery)
            ->selectRaw('COALESCE(SUM(CASE WHEN nominal > 0 THEN nominal ELSE 0 END), 0) as total')
            ->value('total');

        $paidVoidTotal = $voidTotal;
        $paidNetTotal = $paidGrossTotal - $paidVoidTotal;
        $grossPenjualanFinal = $netTotal;
        $netPenjualanPembayaran = $paidGrossTotal;
        $sisaPiutangFinal = max($grossPenjualanFinal - $netPenjualanPembayaran, 0);

        if ($request->boolean('export_xlsx')) {
            $exportOrders = (clone $summaryQuery)
                ->with(['pelanggan', 'kantongOrder', 'cabang'])
                ->withSum(['voidLogs as void_total_order' => function ($q) use ($request) {
                    $q->whereIn('tipe_void', ['FULL', 'PARTIAL']);
                    if ($request->filled('date_from')) {
                        $q->whereDate('void_effective_date', '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('void_effective_date', '<=', $request->input('date_to'));
                    }
                }], 'nominal_void')
                ->withSum(['pembayaran as paid_total_period' => function ($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('tanggal_bayar', '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('tanggal_bayar', '<=', $request->input('date_to'));
                    }
                }], 'nominal')
                ->withSum(['pembayaran as paid_gross_period' => function ($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('tanggal_bayar', '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('tanggal_bayar', '<=', $request->input('date_to'));
                    }
                    $q->where('nominal', '>', 0);
                }], 'nominal')
                ->withSum(['pembayaran as paid_void_period' => function ($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('tanggal_bayar', '>=', $request->input('date_from'));
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('tanggal_bayar', '<=', $request->input('date_to'));
                    }
                    $q->where('nominal', '<', 0);
                }], 'nominal')
                ->orderByDesc('created_at')
                ->get();

            $rowsXlsx = $exportOrders->map(function ($order) {
                return [
                    $order->created_at?->format('Y-m-d H:i'),
                    $order->cabang?->nama ?? '-',
                    $order->nomor_so,
                    $order->kantongOrder?->nomor_ko ?? '-',
                    $order->pelanggan?->nama ?? '-',
                    (float) $order->total + (float) ($order->void_total_order ?? 0),
                    (float) ($order->void_total_order ?? 0),
                    (float) $order->total,
                    (float) ($order->paid_gross_period ?? 0),
                    abs((float) ($order->paid_void_period ?? 0)),
                    (float) ($order->paid_total_period ?? 0),
                    (float) $order->balance,
                    (string) $order->status_pembayaran,
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-penjualan-' . now()->format('Ymd-His') . '.xlsx',
                ['Tanggal', 'Cabang', 'No SO', 'No KO', 'Customer', 'Total Kotor', 'Void', 'Total Bersih', 'Terbayar Kotor Periode', 'Void Pembayaran Periode', 'Terbayar Bersih Periode', 'Sisa', 'Status'],
                $rowsXlsx,
                'Penjualan'
            );
        }

        return view('pages.pos.laporan-penjualan', [
            'orders' => $orders,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'gross_total' => $grossPenjualanFinal,
                'void_total' => $voidTotal,
                'void_effective_total' => $voidTotal,
                'total' => $netPenjualanPembayaran,
                'paid_total' => $paidNetTotal,
                'paid_gross_total' => $paidGrossTotal,
                'paid_void_total' => $paidVoidTotal,
                'balance' => $sisaPiutangFinal,
            ],
            'paymentDaily' => $paymentDaily,
            'voidDaily' => $voidDaily,
        ]);
    }

    public function laporanPaket(Request $request)
    {
        $cabangId = $this->resolveCabangFilter($request);
        $allowedCabangIds = $this->accessibleCabangIds();
        $groupBy = (string) $request->input('group_by', 'ko');
        if (!in_array($groupBy, ['ko', 'paket_kode', 'paket_nama'], true)) {
            $groupBy = 'ko';
        }

        $baseQuery = PesananPenjualanItem::query()
            ->where('is_void', false)
            ->whereNotNull('paket_id');

        if (!empty($allowedCabangIds)) {
            $baseQuery->whereHas('pesananPenjualan', function ($q) use ($allowedCabangIds) {
                $q->whereIn('cabang_id', $allowedCabangIds);
            });
        }

        if ($request->filled('date_from')) {
            $baseQuery->whereHas('pesananPenjualan', function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->input('date_from'));
            });
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereHas('pesananPenjualan', function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->input('date_to'));
            });
        }
        if ($cabangId) {
            $baseQuery->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $baseQuery->whereHas('pesananPenjualan.kantongOrder', function ($q) use ($keyword) {
                $q->where('nomor_ko', 'like', '%' . $keyword . '%');
            });
        }

        $summaryQuery = clone $baseQuery;
        if ($groupBy === 'ko') {
            $rows = (clone $baseQuery)
                ->with([
                    'paket',
                    'pesananPenjualan.pelanggan',
                    'pesananPenjualan.cabang',
                    'pesananPenjualan.kantongOrder',
                    'pesananPenjualan.kasir:id,name',
                ])
                ->latest('id')
                ->paginate(15)
                ->withQueryString();
        } else {
            $groupedQuery = PesananPenjualanItem::query()
                ->from('pesanan_penjualan_item as ppi')
                ->join('paket as pk', 'pk.id', '=', 'ppi.paket_id')
                ->join('pesanan_penjualan as pz', 'pz.id', '=', 'ppi.pesanan_penjualan_id')
                ->leftJoin('kantong_order as ko', 'ko.pesanan_penjualan_id', '=', 'pz.id')
                ->where('ppi.is_void', false)
                ->whereNotNull('ppi.paket_id');

            if (!empty($allowedCabangIds)) {
                $groupedQuery->whereIn('pz.cabang_id', $allowedCabangIds);
            }
            if ($request->filled('date_from')) {
                $groupedQuery->whereDate('pz.created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $groupedQuery->whereDate('pz.created_at', '<=', $request->input('date_to'));
            }
            if ($cabangId) {
                $groupedQuery->where('pz.cabang_id', $cabangId);
            }
            if ($request->filled('no_ko')) {
                $keyword = trim((string) $request->input('no_ko'));
                $groupedQuery->where('ko.nomor_ko', 'like', '%' . $keyword . '%');
            }

            if ($groupBy === 'paket_kode') {
                $groupedQuery
                    ->selectRaw('
                        pk.kode as group_key,
                        pk.nama as paket_nama,
                        COUNT(*) as item_count,
                        COALESCE(SUM(ppi.qty), 0) as total_qty,
                        COALESCE(SUM(ppi.diskon), 0) as total_diskon,
                        COALESCE(SUM(ppi.subtotal), 0) as total_subtotal,
                        COUNT(DISTINCT pz.id) as total_ko
                    ')
                    ->groupBy('pk.kode', 'pk.nama')
                    ->orderBy('pk.kode');
            } else {
                $groupedQuery
                    ->selectRaw('
                        pk.nama as group_key,
                        COUNT(DISTINCT pk.kode) as kode_count,
                        COUNT(*) as item_count,
                        COALESCE(SUM(ppi.qty), 0) as total_qty,
                        COALESCE(SUM(ppi.diskon), 0) as total_diskon,
                        COALESCE(SUM(ppi.subtotal), 0) as total_subtotal,
                        COUNT(DISTINCT pz.id) as total_ko
                    ')
                    ->groupBy('pk.nama')
                    ->orderBy('pk.nama');
            }

            $rows = $groupedQuery->paginate(15)->withQueryString();
        }

        if ($request->boolean('export_xlsx')) {
            if ($groupBy === 'ko') {
                $exportRows = (clone $summaryQuery)
                    ->with([
                        'paket',
                        'pesananPenjualan.pelanggan',
                        'pesananPenjualan.cabang',
                        'pesananPenjualan.kantongOrder',
                        'pesananPenjualan.kasir:id,name',
                    ])
                    ->orderByDesc('id')
                    ->get();

                $rowsXlsx = $exportRows->map(function ($row) {
                    return [
                        $row->pesananPenjualan?->created_at?->format('Y-m-d H:i') ?? '-',
                        $row->pesananPenjualan?->cabang?->nama ?? '-',
                        $row->pesananPenjualan?->nomor_so ?? '-',
                        $row->pesananPenjualan?->kantongOrder?->nomor_ko ?? '-',
                        $row->paket?->kode ?? '-',
                        $row->paket?->nama ?? '-',
                        (float) $row->qty,
                        (float) $row->harga,
                        (float) $row->diskon,
                        (float) $row->subtotal,
                        $row->pesananPenjualan?->pelanggan?->nama ?? '-',
                        $row->pesananPenjualan?->kasir?->name ?? '-',
                    ];
                })->all();

                return app(XlsxExportService::class)->download(
                    'laporan-penjualan-paket-' . now()->format('Ymd-His') . '.xlsx',
                    ['Tanggal', 'Cabang', 'No SO', 'No KO', 'Kode Paket', 'Nama Paket', 'Qty', 'Harga', 'Diskon', 'Subtotal', 'Customer', 'Kasir'],
                    $rowsXlsx,
                    'Penjualan Paket'
                );
            }

            $exportGroupedQuery = PesananPenjualanItem::query()
                ->from('pesanan_penjualan_item as ppi')
                ->join('paket as pk', 'pk.id', '=', 'ppi.paket_id')
                ->join('pesanan_penjualan as pz', 'pz.id', '=', 'ppi.pesanan_penjualan_id')
                ->leftJoin('kantong_order as ko', 'ko.pesanan_penjualan_id', '=', 'pz.id')
                ->where('ppi.is_void', false)
                ->whereNotNull('ppi.paket_id');

            if (!empty($allowedCabangIds)) {
                $exportGroupedQuery->whereIn('pz.cabang_id', $allowedCabangIds);
            }
            if ($request->filled('date_from')) {
                $exportGroupedQuery->whereDate('pz.created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $exportGroupedQuery->whereDate('pz.created_at', '<=', $request->input('date_to'));
            }
            if ($cabangId) {
                $exportGroupedQuery->where('pz.cabang_id', $cabangId);
            }
            if ($request->filled('no_ko')) {
                $keyword = trim((string) $request->input('no_ko'));
                $exportGroupedQuery->where('ko.nomor_ko', 'like', '%' . $keyword . '%');
            }

            if ($groupBy === 'paket_kode') {
                $exportGrouped = $exportGroupedQuery
                    ->selectRaw('
                        pk.kode as group_key,
                        pk.nama as paket_nama,
                        COUNT(*) as item_count,
                        COALESCE(SUM(ppi.qty), 0) as total_qty,
                        COALESCE(SUM(ppi.diskon), 0) as total_diskon,
                        COALESCE(SUM(ppi.subtotal), 0) as total_subtotal,
                        COUNT(DISTINCT pz.id) as total_ko
                    ')
                    ->groupBy('pk.kode', 'pk.nama')
                    ->orderBy('pk.kode')
                    ->get();

                $rowsXlsx = $exportGrouped->map(function ($row) {
                    return [
                        (string) ($row->group_key ?? '-'),
                        (string) ($row->paket_nama ?? '-'),
                        (float) ($row->total_qty ?? 0),
                        (float) ($row->total_diskon ?? 0),
                        (float) ($row->total_subtotal ?? 0),
                        (float) ($row->item_count ?? 0),
                        (float) ($row->total_ko ?? 0),
                    ];
                })->all();

                return app(XlsxExportService::class)->download(
                    'laporan-penjualan-paket-by-kode-' . now()->format('Ymd-His') . '.xlsx',
                    ['Kode Paket', 'Nama Paket', 'Total Qty', 'Total Diskon', 'Total Subtotal', 'Jumlah Item', 'Jumlah KO'],
                    $rowsXlsx,
                    'Penjualan Paket By Kode'
                );
            }

            $exportGrouped = $exportGroupedQuery
                ->selectRaw('
                    pk.nama as group_key,
                    COUNT(DISTINCT pk.kode) as kode_count,
                    COUNT(*) as item_count,
                    COALESCE(SUM(ppi.qty), 0) as total_qty,
                    COALESCE(SUM(ppi.diskon), 0) as total_diskon,
                    COALESCE(SUM(ppi.subtotal), 0) as total_subtotal,
                    COUNT(DISTINCT pz.id) as total_ko
                ')
                ->groupBy('pk.nama')
                ->orderBy('pk.nama')
                ->get();

            $rowsXlsx = $exportGrouped->map(function ($row) {
                return [
                    (string) ($row->group_key ?? '-'),
                    (float) ($row->kode_count ?? 0),
                    (float) ($row->total_qty ?? 0),
                    (float) ($row->total_diskon ?? 0),
                    (float) ($row->total_subtotal ?? 0),
                    (float) ($row->item_count ?? 0),
                    (float) ($row->total_ko ?? 0),
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-penjualan-paket-by-nama-' . now()->format('Ymd-His') . '.xlsx',
                ['Nama Paket', 'Jumlah Kode', 'Total Qty', 'Total Diskon', 'Total Subtotal', 'Jumlah Item', 'Jumlah KO'],
                $rowsXlsx,
                'Penjualan Paket By Nama'
            );
        }

        $koCountQuery = KantongOrder::query()
            ->whereHas('pesananPenjualan', function ($q) use ($allowedCabangIds, $request, $cabangId) {
                if (!empty($allowedCabangIds)) {
                    $q->whereIn('cabang_id', $allowedCabangIds);
                }
                if ($request->filled('date_from')) {
                    $q->whereDate('created_at', '>=', $request->input('date_from'));
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('created_at', '<=', $request->input('date_to'));
                }
                if ($cabangId) {
                    $q->where('cabang_id', $cabangId);
                }
            });
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $koCountQuery->where('nomor_ko', 'like', '%' . $keyword . '%');
        }
        $koCount = (clone $koCountQuery)->distinct('nomor_ko')->count('nomor_ko');

        return view('pages.pos.laporan-penjualan-paket', [
            'rows' => $rows,
            'groupBy' => $groupBy,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'ko_count' => $koCount,
                'qty' => (float) (clone $summaryQuery)->sum('qty'),
                'subtotal' => (float) (clone $summaryQuery)->sum('subtotal'),
            ],
        ]);
    }

    public function laporanBarangJasa(Request $request)
    {
        $cabangId = $this->resolveCabangFilter($request);
        $allowedCabangIds = $this->accessibleCabangIds();

        $query = PesananPenjualanItem::query()
            ->with([
                'produk',
                'pesananPenjualan.pelanggan',
                'pesananPenjualan.cabang',
                'pesananPenjualan.kantongOrder',
            ])
            ->where('is_void', false)
            ->whereNull('paket_id')
            ->whereNotNull('produk_id')
            ->latest('id');

        if (!empty($allowedCabangIds)) {
            $query->whereHas('pesananPenjualan', function ($q) use ($allowedCabangIds) {
                $q->whereIn('cabang_id', $allowedCabangIds);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereHas('pesananPenjualan', function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->input('date_from'));
            });
        }
        if ($request->filled('date_to')) {
            $query->whereHas('pesananPenjualan', function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->input('date_to'));
            });
        }
        if ($cabangId) {
            $query->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $query->whereHas('pesananPenjualan.kantongOrder', function ($q) use ($keyword) {
                $q->where('nomor_ko', 'like', '%' . $keyword . '%');
            });
        }
        if ($request->filled('produk')) {
            $keyword = trim((string) $request->input('produk'));
            $query->whereHas('produk', function ($q) use ($keyword) {
                $q->where('nama', 'like', '%' . $keyword . '%')
                    ->orWhere('kode', 'like', '%' . $keyword . '%');
            });
        }

        $summaryQuery = clone $query;
        $rows = $query->paginate(15)->withQueryString();

        if ($request->boolean('export_xlsx')) {
            $exportRows = (clone $summaryQuery)->orderByDesc('id')->get();
            $rowsXlsx = $exportRows->map(function ($row) {
                return [
                    $row->pesananPenjualan?->created_at?->format('Y-m-d H:i') ?? '-',
                    $row->pesananPenjualan?->cabang?->nama ?? '-',
                    $row->pesananPenjualan?->nomor_so ?? '-',
                    $row->pesananPenjualan?->kantongOrder?->nomor_ko ?? '-',
                    $row->produk?->kode ?? '-',
                    $row->produk?->nama ?? '-',
                    (float) $row->qty,
                    (float) $row->harga,
                    (float) $row->diskon,
                    (float) $row->subtotal,
                    $row->pesananPenjualan?->pelanggan?->nama ?? '-',
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-penjualan-barang-jasa-' . now()->format('Ymd-His') . '.xlsx',
                ['Tanggal', 'Cabang', 'No SO', 'No KO', 'Kode Produk', 'Nama Produk', 'Qty', 'Harga', 'Diskon', 'Subtotal', 'Customer'],
                $rowsXlsx,
                'Penjualan BJ'
            );
        }

        return view('pages.pos.laporan-penjualan-barang-jasa', [
            'rows' => $rows,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
                'qty' => (float) (clone $summaryQuery)->sum('qty'),
                'subtotal' => (float) (clone $summaryQuery)->sum('subtotal'),
            ],
        ]);
    }

    public function laporanBooking(Request $request)
    {
        $cabangId = $this->resolveCabangFilter($request);

        $query = BookingStudio::query()
            ->with([
                'pelanggan:id,nama',
                'cabang:id,nama',
                'pesananPenjualan:id,nomor_so,pelanggan_id,cabang_id',
                'pesananPenjualan.pelanggan:id,nama',
                'pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
                'pesananPenjualan.items:id,pesanan_penjualan_id,produk_id,paket_id',
                'pesananPenjualan.items.produk:id,nama',
                'pesananPenjualan.items.paket:id,nama',
            ])
            ->latest('tanggal_booking');
        $this->applyCabangScope($query);

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_booking', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_booking', '<=', $request->input('date_to'));
        }
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }
        if ($request->filled('no_ko')) {
            $keyword = trim((string) $request->input('no_ko'));
            $query->whereHas('pesananPenjualan.kantongOrder', function ($q) use ($keyword) {
                $q->where('nomor_ko', 'like', '%' . $keyword . '%');
            });
        }

        $summaryQuery = clone $query;
        $rows = $query->paginate(15)->withQueryString();

        if ($request->boolean('export_xlsx')) {
            $exportRows = (clone $summaryQuery)->orderByDesc('tanggal_booking')->get();
            $rowsXlsx = $exportRows->map(function ($row) {
                $items = $row->pesananPenjualan?->items ?? collect();
                $namaItems = $items
                    ->map(fn($item) => $item->produk?->nama ?? $item->paket?->nama)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    $row->pesananPenjualan?->kantongOrder?->nomor_ko ?? '-',
                    $row->pelanggan?->nama ?? $row->pesananPenjualan?->pelanggan?->nama ?? '-',
                    $namaItems !== '' ? $namaItems : '-',
                    $row->tanggal_booking?->format('Y-m-d'),
                    $row->tanggal_booking?->format('H:i'),
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-booking-' . now()->format('Ymd-His') . '.xlsx',
                ['No KO', 'Nama', 'Paket / Produk', 'Tanggal Booking', 'Jam Booking'],
                $rowsXlsx,
                'Booking'
            );
        }

        return view('pages.pos.laporan-booking', [
            'rows' => $rows,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'summary' => [
                'count' => (clone $summaryQuery)->count(),
            ],
        ]);
    }

    public function riwayatDetail(PesananPenjualan $pesananPenjualan)
    {
        $this->ensureCabangAccessible((int) $pesananPenjualan->cabang_id);

        $pesananPenjualan->load([
            'pelanggan',
            'cabang',
            'kantongOrder',
            'kasir:id,name',
            'cs1:id,name',
            'cs2:id,name',
            'spv:id,name',
            'fotografer:id,name',
            'items.produk',
            'items.paket',
            'items.kasir:id,name',
            'items.voidLog:id,tipe_void',
            'pembayaran.metodePembayaran',
            'paymentMethodLogs.fromMethod:id,nama',
            'paymentMethodLogs.toMethod:id,nama',
            'paymentMethodLogs.correctedBy:id,name',
            'paymentMethodLogs.authorizedBy:id,name',
            'voidLogs.voidedBy:id,name',
            'voidLogs.authorizedBy:id,name',
        ]);

        return view('pages.pos.riwayat-penjualan-detail', [
            'order' => $pesananPenjualan,
            'metodePembayaran' => MetodePembayaran::query()
                ->where('status', true)
                ->whereHas('cabang', function ($q) use ($pesananPenjualan) {
                    $q->where('cabang.id', (int) $pesananPenjualan->cabang_id);
                })
                ->orderBy('nama')
                ->get(['id', 'nama']),
        ]);
    }

    public function struk(PesananPenjualan $pesananPenjualan)
    {
        $this->ensureCabangAccessible((int) $pesananPenjualan->cabang_id);

        $pesananPenjualan->load([
            'pelanggan',
            'cabang',
            'kantongOrder',
            'kasir',
            'items.produk',
            'items.paket',
            'items.kasir:id,name',
            'pembayaran.metodePembayaran',
        ]);

        return view('pages.pos.struk-penjualan', [
            'order' => $pesananPenjualan,
            'website' => 'www.papyrusphoto.com',
            'isReprint' => false,
        ]);
    }

    public function reprintStruk(PesananPenjualan $pesananPenjualan)
    {
        $this->ensureCabangAccessible((int) $pesananPenjualan->cabang_id);

        $pesananPenjualan->load([
            'pelanggan',
            'cabang',
            'kantongOrder',
            'kasir',
            'items.produk',
            'items.paket',
            'items.kasir:id,name',
            'pembayaran.metodePembayaran',
        ]);

        return view('pages.pos.struk-penjualan', [
            'order' => $pesananPenjualan,
            'website' => 'www.papyrusphoto.com',
            'isReprint' => true,
        ]);
    }

    public function cekKo(Request $request)
    {
        $request->validate([
            'no_ko' => ['required', 'string', 'max:30'],
        ]);

        $noKo = trim((string) $request->query('no_ko'));
        if ($noKo === '') {
            return response()->json([
                'exists' => false,
                'can_add' => false,
            ]);
        }

        $activeCabangId = (int) ($this->activeCabangId() ?? 0);

        $ko = KantongOrder::query()
            ->with([
                'pesananPenjualan.pelanggan',
                'pesananPenjualan.kasir:id,name',
                'pesananPenjualan.cs1:id,name',
                'pesananPenjualan.cs:id,name',
                'pesananPenjualan.cs2:id,name',
                'pesananPenjualan.spv:id,name',
                'pesananPenjualan.fotografer:id,name',
                'pesananPenjualan.items.produk',
                'pesananPenjualan.items.paket',
            ])
            ->where('nomor_ko', $noKo)
            ->when($activeCabangId > 0, fn($q) => $q->where('cabang_id', $activeCabangId));

        $ko = $ko
            ->first();

        if (!$ko || !$ko->pesananPenjualan) {
            return response()->json([
                'exists' => false,
                'can_add' => false,
            ]);
        }

        $order = $ko->pesananPenjualan;
        $balance = (float) $order->balance;
        $isReusable = in_array((string) $order->status_pembayaran, ['CANCELLED', 'VOID'], true);
        $canAdd = !$isReusable;

        if ($isReusable) {
            return response()->json([
                'exists' => false,
                'can_add' => true,
                'can_edit_existing_items' => false,
                'reusable' => true,
                'message' => 'KO lama berstatus ' . $order->status_pembayaran . '. Nomor KO bisa dipakai ulang untuk transaksi baru.',
            ]);
        }

        return response()->json([
            'exists' => true,
            'can_add' => $canAdd,
            'can_edit_existing_items' => $canAdd ? $this->canEditExistingItems($order) : false,
            'message' => $canAdd
                ? 'KO ditemukan. Transaksi akan ditambahkan ke order ini.'
                : 'KO ditemukan tapi order tidak dapat diproses.',
            'order' => [
                'id' => $order->id,
                'nomor_ko' => $ko->nomor_ko,
                'tanggal_selesai' => $ko->tanggal_selesai?->format('Y-m-d'),
                'nomor_so' => $order->nomor_so,
                'status_pembayaran' => $order->status_pembayaran,
                'total' => (float) $order->total,
                'diskon_otomatis' => (float) ($order->diskon_otomatis ?? 0),
                'paid_total' => (float) $order->paid_total,
                'balance' => (float) $order->balance,
                'sales_mode_id' => $order->sales_mode_id,
                'kasir' => $order->kasir ? [
                    'id' => (int) $order->kasir->id,
                    'name' => $order->kasir->name,
                ] : null,
                'cs' => $order->cs ? [
                    'id' => (int) $order->cs->id,
                    'name' => $order->cs->name,
                ] : null,
                'cs1' => $order->cs1 ? [
                    'id' => (int) $order->cs1->id,
                    'name' => $order->cs1->name,
                ] : null,
                'cs2' => $order->cs2 ? [
                    'id' => (int) $order->cs2->id,
                    'name' => $order->cs2->name,
                ] : null,
                'spv' => $order->spv ? [
                    'id' => (int) $order->spv->id,
                    'name' => $order->spv->name,
                ] : null,
                'fotografer' => $order->fotografer ? [
                    'id' => (int) $order->fotografer->id,
                    'name' => $order->fotografer->name,
                ] : null,
                'pelanggan' => [
                    'nama' => $order->customer_name ?? $order->pelanggan?->nama,
                    'no_hp' => $order->customer_phone ?? $order->pelanggan?->no_hp,
                    'alamat' => $order->customer_address ?? $order->pelanggan?->alamat,
                ],
                'items' => $order->items->where('is_void', false)->values()->map(function ($item) {
                    return [
                        'id' => (int) $item->id,
                        'jenis_item' => $item->paket_id ? 'PAKET' : 'PRODUK',
                        'produk_id' => $item->produk_id ? (int) $item->produk_id : null,
                        'paket_id' => $item->paket_id ? (int) $item->paket_id : null,
                        'kode' => $item->produk?->kode ?? $item->paket?->kode ?? null,
                        'nama' => $item->produk?->nama ?? $item->paket?->nama ?? '-',
                        'harga' => (float) $item->harga,
                        'diskon' => (float) $item->diskon,
                        'qty' => (float) $item->qty,
                        'subtotal' => (float) $item->subtotal,
                    ];
                })->values(),
            ],
        ]);
    }

    public function promosiTersedia(Request $request)
    {
        $validated = $request->validate([
            'cabang_id' => ['required', 'exists:cabang,id'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tanggal' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.jenis_item' => ['required_with:items', 'in:PRODUK,PAKET'],
            'items.*.paket_id' => ['nullable', 'exists:paket,id'],
            'items.*.qty' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.harga' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.diskon' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cabangId = (int) $validated['cabang_id'];
        $this->ensureCabangAccessible($cabangId);
        $subtotal = (float) $validated['subtotal'];
        $tanggal = now();
        if (isset($validated['tanggal'])) {
            $tanggalInput = trim((string) $validated['tanggal']);
            $parsed = Carbon::parse($tanggalInput);
            if (strlen($tanggalInput) <= 10) {
                $parsed->setTimeFrom(now());
            }
            $tanggal = $parsed;
        }
        $hariKe = (int) $tanggal->dayOfWeekIso;
        $itemPayload = $this->buildItemPayload($validated['items'] ?? []);

        $voucher = VoucherPromosi::query()
            ->with('cabangs:id')
            ->where('status', true)
            ->whereDate('aktif_mulai', '<=', $tanggal->toDateString())
            ->whereDate('aktif_sampai', '>=', $tanggal->toDateString())
            ->where('minimum_pembelian', '<=', $subtotal)
            ->where(function ($q) use ($cabangId) {
                $q->whereHas('cabangs', function ($inner) use ($cabangId) {
                    $inner->where('cabang.id', $cabangId);
                })->orWhere(function ($inner) use ($cabangId) {
                    $inner->whereDoesntHave('cabangs')
                        ->where(function ($legacy) use ($cabangId) {
                            $legacy->whereNull('cabang_id')->orWhere('cabang_id', $cabangId);
                        });
                });
            })
            ->where(function ($q) {
                $q->whereNull('kuota')->orWhereColumn('terpakai', '<', 'kuota');
            })
            ->get()
            ->filter(function ($item) use ($hariKe, $tanggal) {
                if (empty($item->hari_aktif) || !is_array($item->hari_aktif)) {
                    return $this->isPromoAktifPadaJam($item, $tanggal);
                }
                $hariSesuai = in_array($hariKe, array_map('intval', $item->hari_aktif), true);
                return $hariSesuai && $this->isPromoAktifPadaJam($item, $tanggal);
            })
            ->map(function ($item) use ($subtotal) {
                $diskon = $item->tipe_diskon === 'PERSEN'
                    ? ($subtotal * ((float) $item->nilai_diskon / 100))
                    : (float) $item->nilai_diskon;
                $diskon = min($diskon, $subtotal);

                return [
                    'kode' => $item->kode,
                    'nama' => $item->nama,
                    'sumber' => 'VOUCHER',
                    'tipe_diskon' => $item->tipe_diskon,
                    'nilai_diskon' => (float) $item->nilai_diskon,
                    'diskon_hitung' => $diskon,
                    'minimum_pembelian' => (float) $item->minimum_pembelian,
                ];
            })
            ->values();

        $diskonOtomatis = DiskonOtomatis::query()
            ->with(['cabangs:id', 'pakets:id'])
            ->where('status', true)
            ->whereDate('aktif_mulai', '<=', $tanggal->toDateString())
            ->whereDate('aktif_sampai', '>=', $tanggal->toDateString())
            ->where('minimum_pembelian', '<=', $subtotal)
            ->where(function ($q) use ($cabangId) {
                $q->whereHas('cabangs', function ($inner) use ($cabangId) {
                    $inner->where('cabang.id', $cabangId);
                })->orWhere(function ($inner) use ($cabangId) {
                    $inner->whereDoesntHave('cabangs')
                        ->where(function ($legacy) use ($cabangId) {
                            $legacy->whereNull('cabang_id')->orWhere('cabang_id', $cabangId);
                        });
                });
            })
            ->get()
            ->filter(function ($item) use ($hariKe, $tanggal) {
                if (empty($item->hari_aktif) || !is_array($item->hari_aktif)) {
                    return $this->isPromoAktifPadaJam($item, $tanggal);
                }
                $hariSesuai = in_array($hariKe, array_map('intval', $item->hari_aktif), true);
                return $hariSesuai && $this->isPromoAktifPadaJam($item, $tanggal);
            })
            ->map(function ($item) use ($subtotal, $itemPayload) {
                $eligiblePaketIds = $item->pakets->pluck('id')->map(fn($id) => (int) $id)->all();
                $subtotalAcuan = $this->resolvePromoEligibleSubtotal($itemPayload, $eligiblePaketIds, $subtotal);
                $diskon = $item->tipe_diskon === 'PERSEN'
                    ? ($subtotalAcuan * ((float) $item->nilai_diskon / 100))
                    : (float) $item->nilai_diskon;
                $diskon = min($diskon, $subtotalAcuan);

                return [
                    'kode' => 'AUTO-' . $item->id,
                    'nama' => $item->nama,
                    'sumber' => 'OTOMATIS',
                    'tipe_diskon' => $item->tipe_diskon,
                    'nilai_diskon' => (float) $item->nilai_diskon,
                    'diskon_hitung' => $diskon,
                    'minimum_pembelian' => (float) $item->minimum_pembelian,
                    'paket_ids' => $eligiblePaketIds,
                ];
            })
            ->filter(fn($item) => (float) ($item['diskon_hitung'] ?? 0) > 0)
            ->values();

        return response()->json($voucher->concat($diskonOtomatis)->values());
    }

    private function isPromoAktifPadaJam(object $promo, Carbon $tanggal): bool
    {
        if ((bool) ($promo->aktif_24_jam ?? false)) {
            return true;
        }

        $jamMulai = (string) ($promo->jam_mulai ?? '');
        $jamSampai = (string) ($promo->jam_sampai ?? '');
        if ($jamMulai === '' || $jamSampai === '') {
            return true;
        }

        $current = $tanggal->format('H:i:s');

        if ($jamMulai <= $jamSampai) {
            return $current >= $jamMulai && $current <= $jamSampai;
        }

        return $current >= $jamMulai || $current <= $jamSampai;
    }

    public function cariProduk(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $cabangId = (int) $request->query('cabang_id');
        $this->ensureCabangAccessible($cabangId);
        $salesModeId = (int) $request->query('sales_mode_id');
        $limit = 15;

        $templateHargaId = CabangSalesMode::query()
            ->where('cabang_id', $cabangId)
            ->where('sales_mode_id', $salesModeId)
            ->where('status', true)
            ->value('template_harga_id');

        $produkRows = Produk::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', '%' . $q . '%')
                        ->orWhere('kode', 'like', '%' . $q . '%');
                });
            })
            ->where('status', true)
            ->limit($limit)
            ->get(['id', 'kode', 'nama', 'harga_default']);

        $paketRows = \App\Models\Paket::query()
            ->with(['items.produk:id,kode,nama,harga_default'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($inner) use ($q) {
                    $inner->where('nama', 'like', '%' . $q . '%')
                        ->orWhere('kode', 'like', '%' . $q . '%');
                });
            })
            ->where('status', true)
            ->limit($limit)
            ->get(['id', 'kode', 'nama', 'harga_default']);

        $produkHargaMap = [];
        $paketHargaMap = [];
        if ($templateHargaId) {
            $produkIds = $produkRows->pluck('id')->all();
            $paketIds = $paketRows->pluck('id')->all();

            if (!empty($produkIds)) {
                $produkHargaMap = TemplateHargaItem::query()
                    ->where('template_harga_id', $templateHargaId)
                    ->where('jenis_item', 'PRODUK')
                    ->where('status', true)
                    ->whereIn('item_id', $produkIds)
                    ->pluck('harga', 'item_id')
                    ->all();
            }

            if (!empty($paketIds)) {
                $paketHargaMap = TemplateHargaItem::query()
                    ->where('template_harga_id', $templateHargaId)
                    ->where('jenis_item', 'PAKET')
                    ->where('status', true)
                    ->whereIn('item_id', $paketIds)
                    ->pluck('harga', 'item_id')
                    ->all();
            }
        }

        $produk = $produkRows->map(function ($item) use ($produkHargaMap) {
            $harga = $produkHargaMap[$item->id] ?? $item->harga_default;

            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'tipe' => 'PRODUK',
                'harga_default' => $harga,
            ];
        })->values();

        $paket = $paketRows->map(function ($item) use ($paketHargaMap) {
            $harga = $paketHargaMap[$item->id] ?? $item->harga_default;

            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'tipe' => 'PAKET',
                'harga_default' => $harga,
                'items' => $item->items->map(function ($pi) {
                    return [
                        'produk_id' => (int) $pi->produk_id,
                        'kode' => $pi->produk?->kode ?? '',
                        'nama' => $pi->produk?->nama ?? 'Produk',
                        'qty' => (float) $pi->qty,
                        'harga_default' => (float) ($pi->produk?->harga_default ?? 0),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($produk->concat($paket)->values());
    }

    public function simpan(Request $request)
    {
        $validated = $request->validate([
            'client_request_id' => ['required', 'string', 'max:100'],
            'cabang_id' => ['required', 'exists:cabang,id'],
            'sales_mode_id' => ['required', 'exists:sales_mode,id'],
            'tanggal' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
            'address' => ['nullable', 'string'],
            'order_note' => ['nullable', 'string'],
            'cs_user_id' => ['nullable', 'exists:users,id'],
            'cs1_user_id' => ['nullable', 'exists:users,id'],
            'cs2_user_id' => ['nullable', 'exists:users,id'],
            'spv_user_id' => ['nullable', 'exists:users,id'],
            'fotografer_user_id' => ['nullable', 'exists:users,id'],
            'is_booking' => ['nullable', 'boolean'],
            'booking_date' => ['nullable', 'date', 'required_if:is_booking,1'],
            'booking_time' => ['nullable', 'date_format:H:i', 'required_if:is_booking,1'],
            'tanggal_selesai' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.jenis_item' => ['required', 'in:PRODUK,PAKET'],
            'items.*.produk_id' => ['nullable', 'exists:produk,id'],
            'items.*.paket_id' => ['nullable', 'exists:paket,id'],
            'items.*.custom_paket_items' => ['nullable', 'array'],
            'items.*.custom_paket_items.*.produk_id' => ['nullable', 'exists:produk,id'],
            'items.*.custom_paket_items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.harga' => ['required', 'numeric', 'min:0'],
            'items.*.diskon' => ['nullable', 'integer', 'min:0'],
            'allow_minus_stock' => ['nullable', 'boolean'],
            'has_price_override' => ['nullable', 'boolean'],
            'authorizer_user_id' => ['nullable', 'exists:users,id'],
            'promo_kode' => ['nullable', 'string', 'max:30'],
            'promo_sumber' => ['nullable', 'in:VOUCHER,OTOMATIS'],
            'promo_diskon' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.metode_pembayaran_id' => ['required_with:payments', 'exists:metode_pembayaran,id'],
            'payments.*.nominal' => ['required_with:payments', 'numeric', 'min:0'],
            'payments.*.tipe' => ['required_with:payments', 'in:DP,FINAL,ADDON,VOID'],
            'no_ko' => ['nullable', 'string', 'max:30'],
            'existing_items' => ['nullable', 'array'],
            'existing_items.*.id' => ['required_with:existing_items', 'integer'],
            'existing_items.*.qty' => ['required_with:existing_items', 'integer', 'min:0'],
            'remove_otp' => ['nullable', 'string', 'max:10'],
            'remove_reason' => ['nullable', 'string', 'min:5'],
        ]);

        $today = now()->toDateString();
        $canBackdate = (bool) auth()->user()?->hasPermission('pos.transaksi.backdate');
        $transactionAt = Carbon::parse((string) $validated['tanggal'])->setTimeFrom(now());
        if (!$canBackdate && $transactionAt->toDateString() !== $today) {
            throw ValidationException::withMessages([
                'tanggal' => ['Anda tidak memiliki akses input transaksi backdate. Tanggal transaksi harus hari ini.'],
            ]);
        }

        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        $allowedMetodePembayaranIds = MetodePembayaran::query()
            ->where('status', true)
            ->whereHas('cabang', function ($q) use ($validated) {
                $q->where('cabang.id', (int) $validated['cabang_id']);
            })
            ->pluck('metode_pembayaran.id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (!empty($validated['payments'] ?? []) && empty($allowedMetodePembayaranIds)) {
            throw ValidationException::withMessages([
                'payments' => ['Belum ada metode pembayaran aktif untuk cabang ini. Silakan atur di menu Cabang.'],
            ]);
        }

        foreach (($validated['payments'] ?? []) as $idx => $payment) {
            $metodeId = (int) ($payment['metode_pembayaran_id'] ?? 0);
            if (!in_array($metodeId, $allowedMetodePembayaranIds, true)) {
                throw ValidationException::withMessages([
                    "payments.$idx.metode_pembayaran_id" => ['Metode pembayaran tidak tersedia untuk cabang ini.'],
                ]);
            }
        }

        foreach (($validated['items'] ?? []) as $idx => $item) {
            if (($item['jenis_item'] ?? null) === 'PRODUK' && empty($item['produk_id'])) {
                throw ValidationException::withMessages([
                    "items.$idx.produk_id" => ['Produk wajib dipilih untuk item jenis PRODUK.'],
                ]);
            }
            if (($item['jenis_item'] ?? null) === 'PAKET' && empty($item['paket_id'])) {
                throw ValidationException::withMessages([
                    "items.$idx.paket_id" => ['Paket wajib dipilih untuk item jenis PAKET.'],
                ]);
            }
        }

        $itemPayloadForValidation = $this->buildItemPayload($validated['items'] ?? []);
        [$itemPayloadForValidation, $promoDiskonTervalidasi] = $this->applyPromoToItemPayload(
            $itemPayloadForValidation,
            (string) ($validated['promo_sumber'] ?? ''),
            (string) ($validated['promo_kode'] ?? ''),
            $transactionAt,
            (int) $validated['cabang_id']
        );
        if (!empty($itemPayloadForValidation)) {
            $subtotalItem = (float) collect($itemPayloadForValidation)->sum('subtotal');
            $promoDiskon = min((float) $promoDiskonTervalidasi, $subtotalItem);
            $totalTransaksi = max($subtotalItem, 0);
            $pembayaranSekarang = (float) collect($validated['payments'] ?? [])->sum(function ($row) {
                return (float) ($row['nominal'] ?? 0);
            });
            $koInputForRule = trim((string) ($validated['no_ko'] ?? ''));
            $isKoExistingAppend = false;
            if ($koInputForRule !== '') {
                $existingKo = KantongOrder::query()
                    ->with('pesananPenjualan:id,status_pembayaran')
                    ->where('nomor_ko', $koInputForRule)
                    ->where('cabang_id', (int) $validated['cabang_id'])
                    ->first();
                if ($existingKo?->pesananPenjualan) {
                    $isKoExistingAppend = !in_array((string) $existingKo->pesananPenjualan->status_pembayaran, ['CANCELLED', 'VOID'], true);
                }
            }

            if ((bool) ($validated['is_booking'] ?? false)) {
                if ($pembayaranSekarang < 50000) {
                    throw ValidationException::withMessages([
                        'payments' => ['Untuk booking, DP minimal Rp 50.000.'],
                    ]);
                }
            } else {
                if (!$isKoExistingAppend && $pembayaranSekarang < ($totalTransaksi * 0.5)) {
                    throw ValidationException::withMessages([
                        'payments' => ['Harus bayar minimal 50% dari nilai penjualan.'],
                    ]);
                }
            }
        }

        // Pengecekan Otorisasi Perubahan Harga
        $hasPriceOverride = (bool) ($validated['has_price_override'] ?? false);
        if ($hasPriceOverride) {
            $currentUser = auth()->user();
            $hasOverridePerm = $currentUser && $currentUser->hasPermission('pos.transaksi.override_price');
            if (!$hasOverridePerm) {
                $authorizerId = (int) ($validated['authorizer_user_id'] ?? 0);
                if ($authorizerId <= 0) {
                    throw ValidationException::withMessages([
                        'price_override' => ['Perubahan harga memerlukan otorisasi dari SPV / Manager.'],
                    ]);
                }
                $authorizer = User::query()->find($authorizerId);
                if (!$authorizer || !$authorizer->status || !$authorizer->hasPermission('pos.transaksi.override_price')) {
                    throw ValidationException::withMessages([
                        'price_override' => ['User otorisator tidak valid atau tidak memiliki izin otorisasi harga.'],
                    ]);
                }
            }
        }

        // Pengecekan Stok Kosong / Kurang sebelum transaksi
        $allowMinusStock = (bool) ($validated['allow_minus_stock'] ?? false);
        if (!$allowMinusStock && !empty($itemPayloadForValidation)) {
            $requiredStockByProduct = [];
            foreach ($itemPayloadForValidation as $item) {
                if (($item['jenis_item'] ?? '') === 'PRODUK' && !empty($item['produk_id'])) {
                    $pid = (int) $item['produk_id'];
                    $requiredStockByProduct[$pid] = ($requiredStockByProduct[$pid] ?? 0) + (float) $item['qty'];
                } elseif (($item['jenis_item'] ?? '') === 'PAKET' && !empty($item['paket_id'])) {
                    if (!empty($item['custom_paket_items']) && is_array($item['custom_paket_items'])) {
                        foreach ($item['custom_paket_items'] as $cItem) {
                            $pid = (int) ($cItem['produk_id'] ?? 0);
                            if ($pid > 0) {
                                $requiredStockByProduct[$pid] = ($requiredStockByProduct[$pid] ?? 0) + ((float) ($cItem['qty'] ?? 1) * (float) $item['qty']);
                            }
                        }
                    } else {
                        $paket = \App\Models\Paket::query()->with('items')->find($item['paket_id']);
                        if ($paket) {
                            foreach ($paket->items as $paketItem) {
                                $pid = (int) $paketItem->produk_id;
                                $requiredStockByProduct[$pid] = ($requiredStockByProduct[$pid] ?? 0) + ((float) $paketItem->qty * (float) $item['qty']);
                            }
                        }
                    }
                }
            }

            if (!empty($requiredStockByProduct)) {
                $insufficientItems = [];
                $cabangId = (int) $validated['cabang_id'];
                foreach ($requiredStockByProduct as $productId => $qtyNeeded) {
                    $produk = Produk::query()->find($productId);
                    if ($produk && $produk->track_stok) {
                        $stok = StokCabang::query()->firstOrCreate(
                            ['produk_id' => $productId, 'cabang_id' => $cabangId],
                            ['qty' => 0, 'qty_on_order' => 0]
                        );
                        $stokTersedia = (float) $stok->qty - (float) $stok->qty_on_order;
                        if ($stokTersedia < $qtyNeeded) {
                            $insufficientItems[] = [
                                'produk_id' => (int) $produk->id,
                                'kode' => $produk->kode ?? '',
                                'nama' => $produk->nama,
                                'stok_tersedia' => $stokTersedia,
                                'qty_diminta' => $qtyNeeded,
                                'defisit' => max(0, $qtyNeeded - $stokTersedia),
                            ];
                        }
                    }
                }

                if (!empty($insufficientItems)) {
                    return response()->json([
                        'status' => 'INSUFFICIENT_STOCK',
                        'message' => 'Sebagian stok barang tidak mencukupi atau kosong di cabang ini.',
                        'insufficient_items' => $insufficientItems,
                    ], 422);
                }
            }
        }

        try {
            $result = DB::transaction(function () use ($validated, $transactionAt, $allowMinusStock) {
                $currentUserId = Auth::id();
                $requestLog = $this->reservePenjualanRequestLog(
                    (string) $validated['client_request_id'],
                    (int) $validated['cabang_id'],
                    $currentUserId ? (int) $currentUserId : null
                );
                $shiftKasir = $this->resolveOrCreateOpenShiftKasir((int) $validated['cabang_id'], (int) $currentUserId);
                $mapping = CabangSalesMode::query()
                    ->where('cabang_id', $validated['cabang_id'])
                    ->where('sales_mode_id', $validated['sales_mode_id'])
                    ->where('status', true)
                    ->first();

                if (!$mapping) {
                    throw ValidationException::withMessages([
                        'sales_mode_id' => ['Sales mode tidak aktif untuk cabang ini.'],
                    ]);
                }

                $templateHargaId = $mapping->template_harga_id;
                $itemPayload = $this->buildItemPayload($validated['items'] ?? []);
                [$itemPayload, $promoDiskonTervalidasi] = $this->applyPromoToItemPayload(
                    $itemPayload,
                    (string) ($validated['promo_sumber'] ?? ''),
                    (string) ($validated['promo_kode'] ?? ''),
                    $transactionAt,
                    (int) $validated['cabang_id']
                );
                $subtotalItem = (float) collect($itemPayload)->sum('subtotal');
                $promoDiskon = min((float) $promoDiskonTervalidasi, $subtotalItem);
                $totalTambahan = max($subtotalItem, 0);
                $paidTambahan = $this->createPayments($validated['payments'] ?? [], null, null, null, $transactionAt);
                $koInput = trim((string) ($validated['no_ko'] ?? ''));

                if ($koInput !== '') {
                    $ko = KantongOrder::query()
                        ->where('nomor_ko', $koInput)
                        ->where('cabang_id', $validated['cabang_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($ko && $ko->pesanan_penjualan_id) {
                        $pesanan = PesananPenjualan::query()->lockForUpdate()->findOrFail($ko->pesanan_penjualan_id);
                        if (in_array((string) $pesanan->status_pembayaran, ['CANCELLED', 'VOID'], true)) {
                            $this->archiveKoForReuse($ko, $transactionAt);
                        } else {
                            if (!empty($validated['tanggal_selesai'])) {
                                $ko->update([
                                    'tanggal_selesai' => $validated['tanggal_selesai'],
                                ]);
                            }

                            $hasExistingItemChanges = !empty($validated['existing_items'] ?? []);
                            if (empty($itemPayload) && !$hasExistingItemChanges && $paidTambahan <= 0) {
                                throw ValidationException::withMessages([
                                    'payments' => ['Untuk KO existing, tambahkan item, ubah/remove qty item existing, atau isi pembayaran untuk pelunasan.'],
                                ]);
                            }
                            $netPerubahanOrder = $this->calculateExistingOrderNetChange($pesanan, $validated);
                            $maxPembayaran = max((float) $pesanan->balance + $netPerubahanOrder, 0);
                            if ($paidTambahan > ($maxPembayaran + 0.00001)) {
                                throw ValidationException::withMessages([
                                    'payments' => ['Nominal pembayaran melebihi tagihan. Maksimal pembayaran saat ini Rp ' . number_format($maxPembayaran, 0, ',', '.') . '.'],
                                ]);
                            }

                            $removeOtp = $this->resolveRemoveOtpForExistingItems($pesanan, $validated);
                            $this->appendToExistingOrder($pesanan, $validated, $itemPayload, $paidTambahan, $promoDiskon, $currentUserId, (int) $shiftKasir->id, $transactionAt, $allowMinusStock, $validated['existing_items'] ?? [], $removeOtp);
                            $this->markPromoAsUsed($validated['promo_sumber'] ?? null, $validated['promo_kode'] ?? null);

                            $response = [
                                'message' => empty($itemPayload)
                                    ? ($hasExistingItemChanges
                                        ? 'Perubahan qty booking berhasil disimpan ke KO existing'
                                        : 'Pelunasan/pembayaran berhasil disimpan ke KO existing')
                                    : 'Transaksi tambahan berhasil disimpan ke KO existing',
                                'data' => $pesanan->fresh(),
                                'mode' => 'APPEND',
                            ];

                            $this->completePenjualanRequestLog($requestLog, $response, $transactionAt);

                            return $response;
                        }
                    }
                }

                if (empty($itemPayload)) {
                    throw ValidationException::withMessages([
                        'items' => ['Tambahkan minimal satu item untuk transaksi baru.'],
                    ]);
                }
                if ($paidTambahan > ($totalTambahan + 0.00001)) {
                    throw ValidationException::withMessages([
                        'payments' => ['Nominal pembayaran melebihi tagihan. Maksimal pembayaran saat ini Rp ' . number_format($totalTambahan, 0, ',', '.') . '.'],
                    ]);
                }

                $pesananBaru = $this->createNewOrder($validated, $templateHargaId, $itemPayload, $paidTambahan, $koInput, $promoDiskon, $currentUserId, (int) $shiftKasir->id, $transactionAt, $allowMinusStock);
                $this->markPromoAsUsed($validated['promo_sumber'] ?? null, $validated['promo_kode'] ?? null);

                $response = [
                    'message' => 'Transaksi berhasil disimpan',
                    'data' => $pesananBaru,
                    'mode' => 'CREATE',
                ];

                $this->completePenjualanRequestLog($requestLog, $response, $transactionAt);

                return $response;
            }, 3);
        } catch (QueryException $e) {
            if ($this->isDuplicatePenjualanRequestException($e)) {
                $duplicateResponse = $this->resolveDuplicatePenjualanResponse((string) $validated['client_request_id']);
                if ($duplicateResponse !== null) {
                    return response()->json($duplicateResponse);
                }

                throw ValidationException::withMessages([
                    'client_request_id' => ['Transaksi yang sama sedang diproses. Cek riwayat penjualan atau KO sebelum coba lagi.'],
                ]);
            }

            throw $e;
        }

        return response()->json($result);
    }

    private function reservePenjualanRequestLog(string $clientRequestId, int $cabangId, ?int $userId): PenjualanRequestLog
    {
        return PenjualanRequestLog::query()->create([
            'client_request_id' => $clientRequestId,
            'user_id' => $userId,
            'cabang_id' => $cabangId,
            'status' => 'PROCESSING',
        ]);
    }

    private function completePenjualanRequestLog(PenjualanRequestLog $requestLog, array $response, Carbon $completedAt): void
    {
        $requestLog->update([
            'pesanan_penjualan_id' => (int) ($response['data']->id ?? 0) ?: null,
            'status' => 'COMPLETED',
            'mode' => (string) ($response['mode'] ?? ''),
            'message' => (string) ($response['message'] ?? ''),
            'completed_at' => $completedAt,
            'updated_at' => $completedAt,
        ]);
    }

    private function isDuplicatePenjualanRequestException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        if (!in_array($sqlState, ['23000', '23505'], true) && $driverCode !== 1062) {
            return false;
        }

        return str_contains($message, 'penjualan_request_logs')
            || str_contains($message, 'client_request_id');
    }

    private function resolveDuplicatePenjualanResponse(string $clientRequestId): ?array
    {
        $requestLog = PenjualanRequestLog::query()
            ->with('pesananPenjualan')
            ->where('client_request_id', $clientRequestId)
            ->first();

        if (!$requestLog || $requestLog->status !== 'COMPLETED' || !$requestLog->pesananPenjualan) {
            return null;
        }

        return [
            'message' => $requestLog->message ?: 'Transaksi berhasil disimpan',
            'data' => $requestLog->pesananPenjualan->fresh(),
            'mode' => $requestLog->mode ?: 'CREATE',
        ];
    }

    private function appendToExistingOrder(PesananPenjualan $pesanan, array $validated, array $itemPayload, float $paidTambahan, float $promoDiskon, ?int $currentUserId, ?int $shiftKasirId, Carbon $transactionAt, bool $allowMinusStock = false, array $existingItemsRequest = [], ?PenjualanVoidOtp $removeOtp = null): void
    {
        if ((int) $pesanan->cabang_id !== (int) $validated['cabang_id']) {
            throw ValidationException::withMessages([
                'no_ko' => ['Cabang transaksi tidak sama dengan cabang KO.'],
            ]);
        }

        if ((int) $pesanan->sales_mode_id !== (int) $validated['sales_mode_id']) {
            throw ValidationException::withMessages([
                'sales_mode_id' => ['Sales mode harus sama dengan transaksi KO sebelumnya.'],
            ]);
        }

        $pelanggan = $this->upsertPelanggan($validated);
        if ($pelanggan && (int) $pesanan->pelanggan_id !== (int) $pelanggan->id) {
            $pesanan->pelanggan_id = $pelanggan->id;
        }

        $totalTambahan = 0;
        // Diskon_otomatis dari promo baru; jika tidak ada promo baru, pertahankan existing
        $diskonOtomatis = $promoDiskon > 0 ? max($promoDiskon, 0) : (float) $pesanan->diskon_otomatis;
        foreach ($itemPayload as $item) {
            $totalTambahan += (float) $item['subtotal'];
            $orderItem = PesananPenjualanItem::query()->create([
                'pesanan_penjualan_id' => $pesanan->id,
                'produk_id' => $item['produk_id'],
                'paket_id' => $item['paket_id'],
                'shift_kasir_id' => $shiftKasirId,
                'kasir_user_id' => $currentUserId,
                'qty' => $item['qty'],
                'harga' => $item['harga'],
                'diskon' => $item['diskon'],
                'subtotal' => $item['subtotal'],
                'created_at' => $transactionAt,
                'updated_at' => $transactionAt,
            ]);
            $this->forceSetTimestamps('pesanan_penjualan_item', (int) $orderItem->id, $transactionAt);

            $this->applyStockMutationForItem($item, (int) $validated['cabang_id'], (int) $pesanan->id, $transactionAt, 'DRAFT', $allowMinusStock); // Force DRAFT to move to on_order
        }

        if (!empty($existingItemsRequest) && !$this->canEditExistingItems($pesanan)) {
            throw ValidationException::withMessages([
                'existing_items' => ['Qty/remove item existing hanya bisa diubah untuk transaksi yang belum lunas.'],
            ]);
        }

        $removedExistingItems = [];
        foreach ($existingItemsRequest as $reqItem) {
            $existingItem = PesananPenjualanItem::query()
                ->where('pesanan_penjualan_id', $pesanan->id)
                ->where('id', $reqItem['id'])
                ->first();

            if ($existingItem && (float) $reqItem['qty'] !== (float) $existingItem->qty) {
                $qtyBaru = max(0, (float) $reqItem['qty']);
                $qtyLama = (float) $existingItem->qty;
                $diffQty = $qtyBaru - $qtyLama;

                $hargaSatuan = (float) $existingItem->harga;
                $diskonSatuan = (float) $existingItem->diskon / $qtyLama; // Asumsi diskon tersebar rata
                $diffSubtotal = ($diffQty * $hargaSatuan) - ($diffQty * $diskonSatuan);

                $totalTambahan += $diffSubtotal;

                $itemMutation = [
                    'jenis_item' => $existingItem->paket_id ? 'PAKET' : 'PRODUK',
                    'produk_id' => $existingItem->produk_id,
                    'paket_id' => $existingItem->paket_id,
                    'qty' => abs($diffQty),
                ];

                if ($diffQty > 0) {
                        $this->applyStockMutationForItem($itemMutation, (int) $validated['cabang_id'], (int) $pesanan->id, $transactionAt, 'DRAFT', $allowMinusStock);
                } else if ($diffQty < 0) {
                    if ($itemMutation['jenis_item'] === 'PRODUK' && $itemMutation['produk_id']) {
                        $produk = Produk::query()->find($itemMutation['produk_id']);
                        if ($produk && $produk->track_stok) {
                            $this->reverseStokOnOrder($produk->id, (int) $validated['cabang_id'], abs($diffQty), (int) $pesanan->id, $transactionAt);
                        }
                    } else if ($itemMutation['jenis_item'] === 'PAKET' && $itemMutation['paket_id']) {
                        $paket = \App\Models\Paket::query()->with('items')->find($itemMutation['paket_id']);
                        if ($paket) {
                            foreach ($paket->items as $paketItem) {
                                $produkBom = Produk::query()->find($paketItem->produk_id);
                                if ($produkBom && $produkBom->track_stok) {
                                    $qtyKembali = (float) $paketItem->qty * abs($diffQty);
                                    $this->reverseStokOnOrder($produkBom->id, (int) $validated['cabang_id'], $qtyKembali, (int) $pesanan->id, $transactionAt);
                                }
                            }
                        }
                    }
                }

                $newDiskon = (float) $existingItem->diskon + ($diffQty * $diskonSatuan);
                $newSubtotal = (float) $existingItem->subtotal + $diffSubtotal;
                $updatePayload = [
                    'qty' => $qtyBaru,
                    'diskon' => $newDiskon,
                    'subtotal' => $newSubtotal,
                    'updated_at' => $transactionAt,
                ];

                if ($qtyBaru <= 0) {
                    $removedExistingItems[] = [
                        'item_id' => (int) $existingItem->id,
                        'nominal' => max(0, (float) $existingItem->subtotal),
                        'label' => $existingItem->produk_id ? 'PRODUK' : 'PAKET',
                    ];

                    $updatePayload['qty'] = 0;
                    $updatePayload['harga'] = 0;
                    $updatePayload['diskon'] = 0;
                    $updatePayload['subtotal'] = 0;
                    $updatePayload['is_void'] = true;
                    $updatePayload['voided_at'] = $transactionAt;
                }

                $existingItem->update($updatePayload);
                $this->forceSetTimestamps('pesanan_penjualan_item', (int) $existingItem->id, $transactionAt, false);
            }
        }

        if (!empty($removedExistingItems)) {
            $removeNominal = (float) collect($removedExistingItems)->sum('nominal');
            $removedItemIds = collect($removedExistingItems)->pluck('item_id')->map(fn($id) => (int) $id)->values()->all();
            $removeReason = trim((string) ($validated['remove_reason'] ?? ''));
            $removeLog = PenjualanVoidLog::query()->create([
                'pesanan_penjualan_id' => $pesanan->id,
                'kantong_order_id' => $pesanan->kantongOrder?->id,
                'otp_id' => $removeOtp?->id,
                'tipe_void' => 'REMOVE',
                'tipe_transaksi' => 'CURRENT_DAY',
                'alasan' => $removeReason !== ''
                    ? $removeReason
                    : ('REMOVE item existing KO. Nilai item terhapus Rp ' . number_format($removeNominal, 0, ',', '.') . '.'),
                'nominal_void' => 0,
                'void_effective_date' => $transactionAt->toDateString(),
                'voided_at' => $transactionAt,
                'voided_by_user_id' => (int) ($currentUserId ?? Auth::id()),
                'authorized_by_user_id' => $removeOtp ? (int) $removeOtp->generated_by_user_id : null,
                'item_payload' => $removedItemIds,
            ]);

            PesananPenjualanItem::query()
                ->whereIn('id', $removedItemIds)
                ->update([
                    'void_log_id' => (int) $removeLog->id,
                    'updated_at' => $transactionAt,
                ]);

            if ($removeOtp) {
                $removeOtp->update([
                    'used_at' => $transactionAt,
                    'used_by_user_id' => (int) ($currentUserId ?? Auth::id()),
                ]);
            }
        }

        if ($paidTambahan > 0) {
            $this->createPayments($validated['payments'] ?? [], (int) $pesanan->id, $currentUserId, $shiftKasirId, $transactionAt);
        }

        $netPerubahanTotal = (float) $totalTambahan - $diskonOtomatis;
        $totalBaru = max((float) $pesanan->total + $netPerubahanTotal, 0);
        $paidBaru = (float) $pesanan->paid_total + $paidTambahan;
        $balanceBaru = max(0, $totalBaru - $paidBaru);

        $statusBaru = 'DRAFT';
        if ($paidBaru > 0 && $balanceBaru > 0) {
            $statusBaru = 'PARTIALLY_PAID';
        } elseif ($paidBaru >= $totalBaru && $totalBaru > 0) {
            $statusBaru = 'PAID';

            // Finish all on_order items for this fully paid order
            $allItems = PesananPenjualanItem::query()
                ->where('pesanan_penjualan_id', $pesanan->id)
                ->where('is_void', false)
                ->get();

            foreach ($allItems as $fItem) {
                if ($fItem->produk_id) {
                    $produk = Produk::query()->find($fItem->produk_id);
                    if ($produk && $produk->track_stok) {
                        $this->finishStokOnOrder(
                            $produk->id,
                            (int) $validated['cabang_id'],
                            (float) $fItem->qty,
                            (int) $pesanan->id,
                            $transactionAt,
                            $allowMinusStock
                        );
                    }
                } else if ($fItem->paket_id) {
                    $paket = \App\Models\Paket::query()->with('items')->find($fItem->paket_id);
                    if ($paket) {
                        foreach ($paket->items as $paketItem) {
                            $produkBom = Produk::query()->find($paketItem->produk_id);
                            if ($produkBom && $produkBom->track_stok) {
                                $qtySelesai = (float) $paketItem->qty * (float) $fItem->qty;
                                $this->finishStokOnOrder(
                                    $produkBom->id,
                                    (int) $validated['cabang_id'],
                                    $qtySelesai,
                                    (int) $pesanan->id,
                                    $transactionAt,
                                    $allowMinusStock
                                );
                            }
                        }
                    }
                }
            }
        }

        $catatanLama = trim((string) $pesanan->catatan);
        $catatanBaru = trim((string) ($validated['order_note'] ?? ''));
        $gabungCatatan = $catatanLama;
        if ($catatanBaru !== '') {
            $gabungCatatan = $catatanLama === '' ? $catatanBaru : ($catatanLama . PHP_EOL . $catatanBaru);
        }
        if ($promoDiskon > 0) {
            $promoInfo = 'Promo dipakai: ' . ($validated['promo_kode'] ?? '-') . ' (diskon Rp ' . number_format($promoDiskon, 0, ',', '.') . ')';
            $gabungCatatan = $gabungCatatan === '' ? $promoInfo : ($gabungCatatan . PHP_EOL . $promoInfo);
        }

        $pesanan->update([
            'kasir_user_id' => $pesanan->kasir_user_id ?: $currentUserId,
            'cs_user_id' => $validated['cs_user_id'] ?? $pesanan->cs_user_id,
            'cs1_user_id' => $validated['cs1_user_id'] ?? $pesanan->cs1_user_id,
            'cs2_user_id' => $validated['cs2_user_id'] ?? $pesanan->cs2_user_id,
            'spv_user_id' => $validated['spv_user_id'] ?? $pesanan->spv_user_id,
            'fotografer_user_id' => $validated['fotografer_user_id'] ?? $pesanan->fotografer_user_id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['phone'],
            'customer_address' => $validated['address'] ?? null,
            'total' => $totalBaru,
            'diskon_otomatis' => $diskonOtomatis,
            'paid_total' => $paidBaru,
            'balance' => $balanceBaru,
            'status_pembayaran' => $statusBaru,
            'catatan' => $gabungCatatan !== '' ? $gabungCatatan : null,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('pesanan_penjualan', (int) $pesanan->id, $transactionAt, false);
    }

    private function createNewOrder(array $validated, ?int $templateHargaId, array $itemPayload, float $paidAwal, string $koInput, float $promoDiskon, ?int $currentUserId, ?int $shiftKasirId, Carbon $transactionAt, bool $allowMinusStock = false): PesananPenjualan
    {
        // Validasi ini harus di awal supaya tidak ada data order/pembayaran
        // yang sempat ditulis lalu dibatalkan oleh rollback akibat KO bentrok.
        if ($koInput !== '') {
            $existingKoGlobal = KantongOrder::query()
                ->where('nomor_ko', $koInput)
                ->first();
            if ($existingKoGlobal && (int) $existingKoGlobal->cabang_id !== (int) $validated['cabang_id']) {
                throw ValidationException::withMessages([
                    'no_ko' => ['Nomor KO sudah dipakai di cabang lain. Silakan cek atau gunakan nomor KO lain.'],
                ]);
            }
        }

        $pelanggan = $this->upsertPelanggan($validated);
        $subtotal = (float) collect($itemPayload)->sum('subtotal');
        $diskonOtomatis = max($promoDiskon, 0);
        $total = max($subtotal - $diskonOtomatis, 0);

        $catatanOrder = trim((string) ($validated['order_note'] ?? ''));
        if ($diskonOtomatis > 0) {
            $promoInfo = 'Promo dipakai: ' . ($validated['promo_kode'] ?? '-') . ' (diskon Rp ' . number_format($diskonOtomatis, 0, ',', '.') . ')';
            $catatanOrder = $catatanOrder === '' ? $promoInfo : ($catatanOrder . PHP_EOL . $promoInfo);
        }

        $balanceCheck = max(0, $total - $paidAwal);
        $statusPembayaranAwal = 'DRAFT';
        if ($paidAwal > 0 && $balanceCheck > 0) {
            $statusPembayaranAwal = 'PARTIALLY_PAID';
        } elseif ($paidAwal >= $total && $total > 0) {
            $statusPembayaranAwal = 'PAID';
        }

        $pesanan = PesananPenjualan::query()->create([
            'nomor_so' => $this->generateNomorSo(),
            'pelanggan_id' => $pelanggan?->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['phone'],
            'customer_address' => $validated['address'] ?? null,
            'cabang_id' => $validated['cabang_id'],
            'sales_mode_id' => $validated['sales_mode_id'],
            'template_harga_id' => $templateHargaId,
            'shift_kasir_id' => $shiftKasirId,
            'kasir_user_id' => $currentUserId,
            'cs_user_id' => $validated['cs_user_id'] ?? null,
            'cs1_user_id' => $validated['cs1_user_id'] ?? null,
            'cs2_user_id' => $validated['cs2_user_id'] ?? null,
            'spv_user_id' => $validated['spv_user_id'] ?? null,
            'fotografer_user_id' => $validated['fotografer_user_id'] ?? null,
            'total' => $total,
            'diskon_otomatis' => $diskonOtomatis,
            'paid_total' => 0,
            'balance' => $total,
            'status_pembayaran' => 'DRAFT',
            'catatan' => $catatanOrder !== '' ? $catatanOrder : null,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('pesanan_penjualan', (int) $pesanan->id, $transactionAt);

        foreach ($itemPayload as $item) {
            $orderItem = PesananPenjualanItem::query()->create([
                'pesanan_penjualan_id' => $pesanan->id,
                'produk_id' => $item['produk_id'],
                'paket_id' => $item['paket_id'],
                'custom_paket_items' => !empty($item['custom_paket_items']) ? $item['custom_paket_items'] : null,
                'shift_kasir_id' => $shiftKasirId,
                'kasir_user_id' => $currentUserId,
                'qty' => $item['qty'],
                'harga' => $item['harga'],
                'diskon' => $item['diskon'],
                'subtotal' => $item['subtotal'],
                'created_at' => $transactionAt,
                'updated_at' => $transactionAt,
            ]);
            $this->forceSetTimestamps('pesanan_penjualan_item', (int) $orderItem->id, $transactionAt);

            $this->applyStockMutationForItem($item, (int) $validated['cabang_id'], (int) $pesanan->id, $transactionAt, $statusPembayaranAwal, $allowMinusStock);
        }

        if ($paidAwal > 0) {
            $this->createPayments($validated['payments'] ?? [], (int) $pesanan->id, $currentUserId, $shiftKasirId, $transactionAt);
        }

        $pesanan->update([
            'paid_total' => $paidAwal,
            'balance' => $balanceCheck,
            'status_pembayaran' => $statusPembayaranAwal,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('pesanan_penjualan', (int) $pesanan->id, $transactionAt, false);

        $ko = KantongOrder::query()->create([
            'nomor_ko' => $koInput !== '' ? $koInput : $this->generateNomorKo(),
            'pesanan_penjualan_id' => $pesanan->id,
            'cabang_id' => $validated['cabang_id'],
            'designer_id' => Auth::id(),
            'status' => 'CREATED',
            'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            'catatan' => $validated['order_note'] ?? null,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kantong_order', (int) $ko->id, $transactionAt);

        if ((bool) ($validated['is_booking'] ?? false)) {
            $this->createBookingFromOrder($pesanan, $validated, $paidAwal);
        }

        return $pesanan->fresh();
    }

    private function buildItemPayload(array $items): array
    {
        $payload = [];
        foreach ($items as $item) {
            $subtotal = ((float) $item['qty'] * (float) $item['harga']) - (float) ($item['diskon'] ?? 0);
            $payload[] = [
                'jenis_item' => $item['jenis_item'],
                'produk_id' => isset($item['produk_id']) ? (int) $item['produk_id'] : null,
                'paket_id' => isset($item['paket_id']) ? (int) $item['paket_id'] : null,
                'custom_paket_items' => !empty($item['custom_paket_items']) && is_array($item['custom_paket_items']) ? $item['custom_paket_items'] : null,
                'qty' => (float) $item['qty'],
                'harga' => (float) $item['harga'],
                'diskon' => (float) ($item['diskon'] ?? 0),
                'subtotal' => $subtotal,
            ];
        }
        return $payload;
    }

    private function resolvePromoEligibleSubtotal(array $items, array $eligiblePaketIds = [], ?float $fallbackSubtotal = null): float
    {
        if (empty($items)) {
            return max((float) ($fallbackSubtotal ?? 0), 0);
        }

        if (empty($eligiblePaketIds)) {
            return max((float) collect($items)->sum('subtotal'), 0);
        }

        $eligibleMap = collect($eligiblePaketIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->flip();

        return max((float) collect($items)
            ->filter(function ($item) use ($eligibleMap) {
                return ($item['jenis_item'] ?? null) === 'PAKET'
                    && isset($item['paket_id'])
                    && $eligibleMap->has((int) $item['paket_id']);
            })
            ->sum('subtotal'), 0);
    }

    private function applyPromoToItemPayload(array $items, string $promoSumber, string $promoKode, Carbon $tanggal, int $cabangId): array
    {
        $promoSumber = strtoupper(trim($promoSumber));
        $promoKode = strtoupper(trim($promoKode));
        if (empty($items) || $promoSumber === '' || $promoKode === '') {
            return [$items, 0.0];
        }

        $subtotalOrder = max((float) collect($items)->sum('subtotal'), 0);
        if ($subtotalOrder <= 0) {
            return [$items, 0.0];
        }

        $promo = null;
        $eligiblePaketIds = [];
        if ($promoSumber === 'OTOMATIS') {
            if (!str_starts_with($promoKode, 'AUTO-')) {
                return [$items, 0.0];
            }
            $diskonId = (int) str_replace('AUTO-', '', $promoKode);
            if ($diskonId <= 0) {
                return [$items, 0.0];
            }

            $promo = DiskonOtomatis::query()
                ->with(['cabangs:id', 'pakets:id'])
                ->whereKey($diskonId)
                ->where('status', true)
                ->first();
            if (!$promo) {
                return [$items, 0.0];
            }

            $eligiblePaketIds = $promo->pakets->pluck('id')->map(fn($id) => (int) $id)->all();
        } elseif ($promoSumber === 'VOUCHER') {
            $promo = VoucherPromosi::query()
                ->with('cabangs:id')
                ->where('kode', $promoKode)
                ->where('status', true)
                ->first();
            if (!$promo) {
                return [$items, 0.0];
            }
        } else {
            return [$items, 0.0];
        }

        if (!$this->isPromoAktifPadaJam($promo, $tanggal)) {
            return [$items, 0.0];
        }
        if ($tanggal->toDateString() < $promo->aktif_mulai->toDateString() || $tanggal->toDateString() > $promo->aktif_sampai->toDateString()) {
            return [$items, 0.0];
        }

        if (!empty($promo->hari_aktif) && is_array($promo->hari_aktif)) {
            $hariKe = (int) $tanggal->dayOfWeekIso;
            $hariSesuai = in_array($hariKe, array_map('intval', $promo->hari_aktif), true);
            if (!$hariSesuai) {
                return [$items, 0.0];
            }
        }

        $cabangValid = $promo->cabangs->pluck('id')->map(fn($id) => (int) $id)->all();
        if (!empty($cabangValid) && !in_array($cabangId, $cabangValid, true)) {
            return [$items, 0.0];
        }
        if (empty($cabangValid) && !empty($promo->cabang_id) && (int) $promo->cabang_id !== $cabangId) {
            return [$items, 0.0];
        }

        if ($promoSumber === 'VOUCHER' && $promo->kuota !== null && (int) $promo->terpakai >= (int) $promo->kuota) {
            return [$items, 0.0];
        }

        if ($subtotalOrder < (float) ($promo->minimum_pembelian ?? 0)) {
            return [$items, 0.0];
        }

        $subtotalEligible = $this->resolvePromoEligibleSubtotal($items, $eligiblePaketIds, $subtotalOrder);
        if ($subtotalEligible <= 0) {
            return [$items, 0.0];
        }

        $diskonTotal = $promo->tipe_diskon === 'PERSEN'
            ? ($subtotalEligible * ((float) $promo->nilai_diskon / 100))
            : (float) $promo->nilai_diskon;
        $diskonTotal = min($diskonTotal, $subtotalEligible);
        if ($diskonTotal <= 0) {
            return [$items, 0.0];
        }

        // Diskon general (eligiblePaketIds kosong): TIDAK distribusikan ke item.
        // Diskon dipotong langsung dari total transaksi (order-level), bukan per-item.
        // Diskon per-paket/produk (eligiblePaketIds terisi): baru didistribusikan proporsional.
        if (empty($eligiblePaketIds)) {
            return [$items, max($diskonTotal, 0)];
        }

        $eligibleIndexes = [];
        foreach ($items as $idx => $item) {
            if (($item['jenis_item'] ?? null) === 'PAKET'
                && in_array((int) ($item['paket_id'] ?? 0), $eligiblePaketIds, true)
                && (float) ($item['subtotal'] ?? 0) > 0
            ) {
                $eligibleIndexes[] = $idx;
            }
        }

        if (empty($eligibleIndexes)) {
            return [$items, 0.0];
        }

        $remaining = $diskonTotal;
        $totalEligibleSubtotal = (float) collect($eligibleIndexes)->sum(fn($idx) => (float) ($items[$idx]['subtotal'] ?? 0));
        foreach ($eligibleIndexes as $n => $idx) {
            $itemSubtotal = (float) ($items[$idx]['subtotal'] ?? 0);
            if ($itemSubtotal <= 0) {
                continue;
            }

            if ($n === (count($eligibleIndexes) - 1)) {
                $alokasi = $remaining;
            } else {
                $alokasi = $totalEligibleSubtotal > 0 ? ($diskonTotal * ($itemSubtotal / $totalEligibleSubtotal)) : 0;
                $alokasi = min($alokasi, $remaining);
            }
            $alokasi = min($alokasi, $itemSubtotal);

            $items[$idx]['diskon'] = (float) ($items[$idx]['diskon'] ?? 0) + $alokasi;
            $items[$idx]['subtotal'] = max($itemSubtotal - $alokasi, 0);
            $remaining -= $alokasi;
        }

        return [$items, max($diskonTotal - max($remaining, 0), 0)];
    }

    private function upsertPelanggan(array $validated): ?Pelanggan
    {
        $phone = trim((string) ($validated['phone'] ?? ''));
        $name = trim((string) ($validated['customer_name'] ?? ''));
        $address = $validated['address'] ?? null;

        $pelanggan = Pelanggan::query()
            ->where('no_hp', $phone)
            ->where('nama', $name)
            ->first();

        if ($pelanggan) {
            return $pelanggan;
        }

        return Pelanggan::query()->create([
            'no_hp' => $phone,
            'nama' => $name,
            'alamat' => $address,
            'catatan' => null,
        ]);
    }

    private function createPayments(array $payments, ?int $pesananId, ?int $currentUserId = null, ?int $shiftKasirId = null, ?Carbon $paidAt = null): float
    {
        $total = 0;
        $paidAt = $paidAt ?: now();
        foreach ($payments as $payment) {
            $nominal = (float) ($payment['nominal'] ?? 0);
            if ($nominal <= 0) {
                continue;
            }

            if ($pesananId !== null) {
                $paymentRow = PembayaranPenjualan::query()->create([
                    'pesanan_penjualan_id' => $pesananId,
                    'metode_pembayaran_id' => $payment['metode_pembayaran_id'],
                    'shift_kasir_id' => $shiftKasirId,
                    'kasir_user_id' => $currentUserId,
                    'nominal' => $nominal,
                    'tipe' => $payment['tipe'],
                    'tanggal_bayar' => $paidAt,
                    'created_at' => $paidAt,
                    'updated_at' => $paidAt,
                ]);
                $this->forceSetTimestamps('pembayaran_penjualan', (int) $paymentRow->id, $paidAt);
            }

            $total += $nominal;
        }

        return $total;
    }

    private function resolveOrCreateOpenShiftKasir(int $cabangId, int $userId): ShiftKasir
    {
        $shift = ShiftKasir::query()
            ->where('cabang_id', $cabangId)
            ->where('user_id', $userId)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();

        if ($shift) {
            if ($shift->dibuka_pada && !$shift->dibuka_pada->isToday()) {
                throw ValidationException::withMessages([
                    'shift_kasir' => ['Shift kasir tanggal ' . $shift->dibuka_pada->format('d-m-Y') . ' belum ditutup. Silakan buka menu Tutup Kasir dan tutup shift lama terlebih dahulu.'],
                ]);
            }
            return $shift;
        }

        return ShiftKasir::query()->create([
            'cabang_id' => $cabangId,
            'user_id' => $userId,
            'modal_awal' => 0,
            'kas_expected' => 0,
            'dibuka_pada' => now(),
            'status' => 'OPEN',
        ]);
    }

    private function applyStockMutationForItem(array $item, int $cabangId, int $pesananId, Carbon $transactionAt, string $statusPembayaran = 'PAID', bool $allowMinusStock = false): void
    {
        if ($item['jenis_item'] === 'PRODUK' && $item['produk_id']) {
            $produk = Produk::query()->find($item['produk_id']);
            if ($produk && $produk->track_stok) {
                $this->kurangiStok($produk->id, $cabangId, (float) $item['qty'], $pesananId, $transactionAt, $statusPembayaran, $allowMinusStock);
            }
        }

        if ($item['jenis_item'] === 'PAKET' && $item['paket_id']) {
            if (!empty($item['custom_paket_items']) && is_array($item['custom_paket_items'])) {
                foreach ($item['custom_paket_items'] as $customItem) {
                    $produkBom = Produk::query()->find($customItem['produk_id'] ?? null);
                    if ($produkBom && $produkBom->track_stok) {
                        $qtyKeluar = (float) ($customItem['qty'] ?? 1) * (float) $item['qty'];
                        $this->kurangiStok($produkBom->id, $cabangId, $qtyKeluar, $pesananId, $transactionAt, $statusPembayaran, $allowMinusStock);
                    }
                }
            } else {
                $paket = \App\Models\Paket::query()->with('items')->find($item['paket_id']);
                if ($paket) {
                    foreach ($paket->items as $paketItem) {
                        $produkBom = Produk::query()->find($paketItem->produk_id);
                        if ($produkBom && $produkBom->track_stok) {
                            $qtyKeluar = (float) $paketItem->qty * (float) $item['qty'];
                            $this->kurangiStok($produkBom->id, $cabangId, $qtyKeluar, $pesananId, $transactionAt, $statusPembayaran, $allowMinusStock);
                        }
                    }
                }
            }
        }
    }

    private function kurangiStok(int $produkId, int $cabangId, float $qtyKeluar, int $referensiId, Carbon $transactionAt, string $statusPembayaran = 'PAID', bool $forceAllowNegative = false): void
    {
        $stok = StokCabang::query()->firstOrCreate(
            ['produk_id' => $produkId, 'cabang_id' => $cabangId],
            ['qty' => 0, 'qty_on_order' => 0]
        );

        $allowNegative = $forceAllowNegative || $this->allowMinusStockByCabang($cabangId);
        $isBooking = in_array($statusPembayaran, ['DRAFT', 'PARTIALLY_PAID'], true);

        if ($isBooking) {
            $stokTersedia = (float) $stok->qty - (float) $stok->qty_on_order;
            if (!$allowNegative && ($stokTersedia - $qtyKeluar) < 0) {
                throw ValidationException::withMessages([
                    'items' => ['Stok tersedia tidak mencukupi untuk salah satu produk.'],
                ]);
            }

            $stok->update([
                'qty_on_order' => (float) $stok->qty_on_order + $qtyKeluar
            ]);
            $saldoAkhir = (float) $stok->qty;
        } else {
            $saldoAkhir = (float) $stok->qty - $qtyKeluar;
            if (!$allowNegative && $saldoAkhir < 0) {
                throw ValidationException::withMessages([
                    'items' => ['Stok tidak mencukupi untuk salah satu produk.'],
                ]);
            }

            $stok->update(['qty' => $saldoAkhir]);
        }

        $kartuStok = KartuStok::query()->create([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tipe_mutasi' => 'PENJUALAN',
            'referensi_tipe' => 'pesanan_penjualan',
            'referensi_id' => $referensiId,
            'qty_masuk' => 0,
            'qty_keluar' => $isBooking ? 0 : $qtyKeluar,
            'saldo_akhir' => $saldoAkhir,
            'catatan' => $isBooking ? 'Reservasi stok On-Order dari transaksi POS (booking/belum lunas)' : 'Potong stok dari transaksi POS',
            'tanggal_mutasi' => $transactionAt,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kartu_stok', (int) $kartuStok->id, $transactionAt);
    }

    private function reverseStokOnOrder(int $produkId, int $cabangId, float $qtyKembali, int $referensiId, Carbon $transactionAt): void
    {
        $stok = StokCabang::query()->firstOrCreate(
            ['produk_id' => $produkId, 'cabang_id' => $cabangId],
            ['qty' => 0, 'qty_on_order' => 0]
        );

        $qtyOnOrderSebelum = (float) $stok->qty_on_order;
        $qtyKembaliEfektif = min($qtyOnOrderSebelum, $qtyKembali);
        $qtyOnOrderBaru = max(0, $qtyOnOrderSebelum - $qtyKembaliEfektif);
        $saldoAkhir = (float) $stok->qty;

        $stok->update([
            'qty_on_order' => $qtyOnOrderBaru
        ]);

        $kartuStok = KartuStok::query()->create([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tipe_mutasi' => 'RETUR_PENJUALAN', // Assuming returning to active stock
            'referensi_tipe' => 'pesanan_penjualan',
            'referensi_id' => $referensiId,
            'qty_masuk' => 0,
            'qty_keluar' => 0,
            'saldo_akhir' => $saldoAkhir,
            'catatan' => 'Rilis reservasi stok On-Order transaksi POS (Edit QTY Turun)',
            'tanggal_mutasi' => $transactionAt,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kartu_stok', (int) $kartuStok->id, $transactionAt);
    }

    private function finishStokOnOrder(
        int $produkId,
        int $cabangId,
        float $qtySelesai,
        int $referensiId,
        Carbon $transactionAt,
        bool $forceAllowNegative = false
    ): void
    {
        if ($qtySelesai <= 0) {
            return;
        }

        $stok = StokCabang::query()->firstOrCreate(
            ['produk_id' => $produkId, 'cabang_id' => $cabangId],
            ['qty' => 0, 'qty_on_order' => 0]
        );

        $qtyOnOrderSebelum = (float) $stok->qty_on_order;
        $qtyCommit = min($qtyOnOrderSebelum, $qtySelesai);
        if ($qtyCommit <= 0) {
            return;
        }

        $allowNegative = $forceAllowNegative || $this->allowMinusStockByCabang($cabangId);
        $legacySudahTerpotong = min($qtyCommit, $this->legacyOnOrderAlreadyDeductedQty($produkId, $referensiId));
        $qtyKeluarSaatLunas = max(0, $qtyCommit - $legacySudahTerpotong);
        $saldoAkhir = (float) $stok->qty - $qtyKeluarSaatLunas;

        if (!$allowNegative && $saldoAkhir < 0) {
            throw ValidationException::withMessages([
                'items' => ['Stok tidak mencukupi untuk menyelesaikan order booking.'],
            ]);
        }

        $stok->update([
            'qty' => $saldoAkhir,
            'qty_on_order' => max(0, $qtyOnOrderSebelum - $qtyCommit),
        ]);

        if ($qtyKeluarSaatLunas <= 0) {
            return;
        }

        $kartuStok = KartuStok::query()->create([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tipe_mutasi' => 'PENJUALAN',
            'referensi_tipe' => 'pesanan_penjualan',
            'referensi_id' => $referensiId,
            'qty_masuk' => 0,
            'qty_keluar' => $qtyKeluarSaatLunas,
            'saldo_akhir' => $saldoAkhir,
            'catatan' => 'Penyelesaian On-Order (lunas) transaksi POS',
            'tanggal_mutasi' => $transactionAt,
            'created_at' => $transactionAt,
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kartu_stok', (int) $kartuStok->id, $transactionAt);
    }

    private function legacyOnOrderAlreadyDeductedQty(int $produkId, int $pesananId): float
    {
        $qtyKeluarLegacy = (float) KartuStok::query()
            ->where('produk_id', $produkId)
            ->where('referensi_tipe', 'pesanan_penjualan')
            ->where('referensi_id', $pesananId)
            ->where('catatan', 'Potong stok (Dialihkan ke On-Order) dari transaksi POS')
            ->sum('qty_keluar');

        $qtyMasukLegacy = (float) KartuStok::query()
            ->where('produk_id', $produkId)
            ->where('referensi_tipe', 'pesanan_penjualan')
            ->where('referensi_id', $pesananId)
            ->where('catatan', 'Pengembalian stok dari On-Order transaksi POS (Edit QTY Turun)')
            ->sum('qty_masuk');

        return max(0, $qtyKeluarLegacy - $qtyMasukLegacy);
    }

    private function canEditExistingItems(PesananPenjualan $order): bool
    {
        return in_array((string) $order->status_pembayaran, ['DRAFT', 'PARTIALLY_PAID'], true);
    }

    private function calculateExistingOrderNetChange(PesananPenjualan $order, array $validated): float
    {
        $itemPayload = $this->buildItemPayload($validated['items'] ?? []);
        $tanggal = isset($validated['tanggal'])
            ? Carbon::parse((string) $validated['tanggal'])->setTimeFrom(now())
            : now();
        [$itemPayload, $_promoDiskonTervalidasi] = $this->applyPromoToItemPayload(
            $itemPayload,
            (string) ($validated['promo_sumber'] ?? ''),
            (string) ($validated['promo_kode'] ?? ''),
            $tanggal,
            (int) ($validated['cabang_id'] ?? $order->cabang_id)
        );
        $subtotalBaru = (float) collect($itemPayload)->sum('subtotal');
        $netChange = $subtotalBaru;

        $existingItems = collect($validated['existing_items'] ?? []);
        if ($existingItems->isEmpty()) {
            return $netChange;
        }

        $activeItems = PesananPenjualanItem::query()
            ->where('pesanan_penjualan_id', (int) $order->id)
            ->whereIn('id', $existingItems->pluck('id')->map(fn($id) => (int) $id)->all())
            ->where('is_void', false)
            ->get()
            ->keyBy('id');

        foreach ($existingItems as $row) {
            $itemId = (int) ($row['id'] ?? 0);
            /** @var PesananPenjualanItem|null $existingItem */
            $existingItem = $activeItems->get($itemId);
            if (!$existingItem) {
                continue;
            }

            $qtyLama = (float) $existingItem->qty;
            $qtyBaru = max(0, (float) ($row['qty'] ?? 0));
            if (abs($qtyBaru - $qtyLama) < 0.00001 || $qtyLama <= 0) {
                continue;
            }

            $diffQty = $qtyBaru - $qtyLama;
            $hargaSatuan = (float) $existingItem->harga;
            $diskonSatuan = (float) $existingItem->diskon / $qtyLama;
            $netChange += ($diffQty * $hargaSatuan) - ($diffQty * $diskonSatuan);
        }

        return $netChange;
    }

    private function resolveRemoveOtpForExistingItems(PesananPenjualan $order, array $validated): ?PenjualanVoidOtp
    {
        $existingItems = collect($validated['existing_items'] ?? []);
        $hasRemoveRequest = $existingItems->contains(function ($row) {
            return (float) ($row['qty'] ?? 0) <= 0;
        });

        if (!$hasRemoveRequest) {
            return null;
        }

        // OTP hanya diperlukan jika order sudah LUNAS
        // Untuk order DRAFT atau PARTIALLY_PAID, tidak perlu OTP
        if (!in_array((string) $order->status_pembayaran, ['DRAFT', 'PARTIALLY_PAID'], true)) {
            throw ValidationException::withMessages([
                'existing_items' => ['Remove item existing hanya boleh untuk transaksi yang belum lunas.'],
            ]);
        }

        // Order belum lunas, tidak perlu OTP untuk perubahan apapun
        return null;
    }

    private function forceSetTimestamps(string $table, int $id, Carbon $timestamp, bool $withCreatedAt = true): void
    {
        $payload = ['updated_at' => $timestamp];
        if ($withCreatedAt) {
            $payload['created_at'] = $timestamp;
        }

        DB::table($table)->where('id', $id)->update($payload);
    }

    private function allowMinusStockByCabang(int $cabangId): bool
    {
        if (isset($this->allowMinusStockCabangCache[$cabangId])) {
            return $this->allowMinusStockCabangCache[$cabangId];
        }

        $cabangAllow = Cabang::query()->where('id', $cabangId)->value('allow_minus_stock');
        $allow = is_null($cabangAllow)
            ? (bool) config('pos.izinkan_stok_negatif', false)
            : (bool) $cabangAllow;

        $this->allowMinusStockCabangCache[$cabangId] = $allow;
        return $allow;
    }

    private function markPromoAsUsed(?string $sumber, ?string $kode): void
    {
        if (!$sumber || !$kode) {
            return;
        }

        if ($sumber === 'VOUCHER') {
            $voucher = VoucherPromosi::query()->where('kode', $kode)->lockForUpdate()->first();
            if ($voucher) {
                $voucher->increment('terpakai');
            }
        }
    }

    private function createBookingFromOrder(PesananPenjualan $pesanan, array $validated, float $paidAwal): void
    {
        $bookingDate = trim((string) ($validated['booking_date'] ?? ''));
        $bookingTime = trim((string) ($validated['booking_time'] ?? ''));
        if ($bookingTime === '') {
            $bookingTime = '23:59';
        }
        $tanggalBooking = $bookingDate !== ''
            ? Carbon::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $bookingTime)
            : now();

        BookingStudio::query()->create([
            'nomor_booking' => $this->generateNomorBooking(),
            'pesanan_penjualan_id' => $pesanan->id,
            'pelanggan_id' => $pesanan->pelanggan_id,
            'cabang_id' => $pesanan->cabang_id,
            'studio_id' => null,
            'tanggal_booking' => $tanggalBooking,
            'status' => $paidAwal > 0 ? 'BOOKED_DP' : 'BOOKED_UNPAID',
        ]);
    }

    private function generateNomorBooking(): string
    {
        $prefix = 'BK-' . now()->format('Ymd') . '-';
        $last = BookingStudio::query()
            ->where('nomor_booking', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_booking');

        $next = 1;
        if ($last) {
            $tail = (int) substr($last, -4);
            $next = $tail + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateNomorSo(): string
    {
        $prefix = 'SO-' . now()->format('Ymd') . '-';
        $last = PesananPenjualan::query()
            ->where('nomor_so', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_so');

        $next = 1;
        if ($last) {
            $tail = (int) substr($last, -4);
            $next = $tail + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function generateNomorKo(): string
    {
        $prefix = 'KO-' . now()->format('Ymd') . '-';
        $last = KantongOrder::query()
            ->where('nomor_ko', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_ko');

        $next = 1;
        if ($last) {
            $tail = (int) substr($last, -4);
            $next = $tail + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function archiveKoForReuse(KantongOrder $ko, Carbon $transactionAt): void
    {
        $nomorAsli = trim((string) $ko->nomor_ko);
        if ($nomorAsli === '') {
            return;
        }

        $suffix = '-V' . (int) $ko->id;
        $baseLength = max(1, 30 - strlen($suffix));
        $nomorArsip = substr($nomorAsli, 0, $baseLength) . $suffix;
        $catatanArsip = 'Nomor asli ' . $nomorAsli . ' diarsipkan agar bisa dipakai ulang pada ' . $transactionAt->format('Y-m-d H:i:s') . '.';
        $catatanLama = trim((string) $ko->catatan);

        $ko->update([
            'nomor_ko' => $nomorArsip,
            'catatan' => $catatanLama === '' ? $catatanArsip : ($catatanLama . PHP_EOL . $catatanArsip),
            'updated_at' => $transactionAt,
        ]);
        $this->forceSetTimestamps('kantong_order', (int) $ko->id, $transactionAt, false);
    }

    public function authorizePriceOverride(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = trim($validated['username']);
        $user = User::query()
            ->where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password otorisator salah.',
            ], 422);
        }

        if (!$user->status) {
            return response()->json([
                'success' => false,
                'message' => 'Akun otorisator sedang tidak aktif.',
            ], 403);
        }

        if (!$user->hasPermission('pos.transaksi.override_price')) {
            return response()->json([
                'success' => false,
                'message' => 'User ini tidak memiliki hak otorisasi perubahan harga. Hubungi Super Admin / SPV.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'authorizer_user_id' => $user->id,
            'authorizer_name' => $user->name,
            'message' => 'Perubahan harga berhasil diotorisasi oleh ' . $user->name,
        ]);
    }
}

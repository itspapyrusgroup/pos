<?php

namespace App\Http\Controllers;

use App\Models\KpiKonfigurasi;
use App\Models\PesananPenjualan;
use App\Models\User;
use Illuminate\Http\Request;

class LaporanKpiController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = $this->resolveCabangFilter($request);
        $allowedCabangIds = $this->accessibleCabangIds();

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));
        $karyawanId = $request->input('karyawan_id');

        // Get KPI konfigurasi
        $kpiConfig = KpiKonfigurasi::query()
            ->when($cabangId, fn($q) => $q->where('cabang_id', $cabangId))
            ->where('status', true)
            ->first();

        $persenCsKasirSpv = $kpiConfig?->persen_cs_kasir_spv ?? 60;
        $persenFotografer = $kpiConfig?->persen_fotografer ?? 40;
        $includeKasir = $kpiConfig?->include_kasir ?? true;
        $includeSpv = $kpiConfig?->include_spv ?? true;

        // Base query
        $baseQuery = PesananPenjualan::query()
            ->whereNotIn('status_pembayaran', ['CANCELLED', 'VOID'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if (!empty($allowedCabangIds)) {
            $baseQuery->whereIn('cabang_id', $allowedCabangIds);
        }
        if ($cabangId) {
            $baseQuery->where('cabang_id', $cabangId);
        }

        // Get orders with relationships
        $orders = (clone $baseQuery)
            ->with(['kasir:id,name', 'spv:id,name', 'fotografer:id,name', 'cs:id,name', 'cs1:id,name', 'cs2:id,name', 'cabang:id,nama'])
            ->get();

        // Calculate ACTUAL total omset (sum of all order totals, NOT cumulative per employee)
        $totalOmset = (float) $orders->sum('total');
        $jumlahOrder = $orders->count();

        // Calculate stats per karyawan (count transaksi saja, bukan omset)
        $karyawanStats = [];
        $pesertaCsKasirSpv = [];
        $pesertaFotografer = [];

        foreach ($orders as $order) {
            // Kasir
            if ($order->kasir_user_id && $includeKasir) {
                $kasirId = $order->kasir_user_id;
                if (!isset($karyawanStats[$kasirId])) {
                    $karyawanStats[$kasirId] = [
                        'id' => $kasirId,
                        'nama' => $order->kasir?->name ?? "User #$kasirId",
                        'role' => 'Kasir',
                        'cabang_id' => $order->cabang_id,
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$kasirId]['jumlah_order']++;
                $pesertaCsKasirSpv[$kasirId] = true;
            }

            // CS (3 kolom: cs, cs1, cs2)
            $csIds = array_filter([
                $order->cs_user_id,
                $order->cs1_user_id,
                $order->cs2_user_id,
            ]);

            foreach ($csIds as $csId) {
                if (!isset($karyawanStats[$csId])) {
                    $csName = match(true) {
                        $csId == $order->cs_user_id => $order->cs?->name ?? "User #$csId",
                        $csId == $order->cs1_user_id => $order->cs1?->name ?? "User #$csId",
                        $csId == $order->cs2_user_id => $order->cs2?->name ?? "User #$csId",
                        default => "User #$csId",
                    };

                    $karyawanStats[$csId] = [
                        'id' => $csId,
                        'nama' => $csName,
                        'role' => 'CS',
                        'cabang_id' => $order->cabang_id,
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$csId]['jumlah_order']++;
                $pesertaCsKasirSpv[$csId] = true;
            }

            // SPV
            if ($order->spv_user_id && $includeSpv) {
                $spvId = $order->spv_user_id;
                if (!isset($karyawanStats[$spvId])) {
                    $karyawanStats[$spvId] = [
                        'id' => $spvId,
                        'nama' => $order->spv?->name ?? "User #$spvId",
                        'role' => 'SPV',
                        'cabang_id' => $order->cabang_id,
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$spvId]['jumlah_order']++;
                $pesertaCsKasirSpv[$spvId] = true;
            }

            // Fotografer
            if ($order->fotografer_user_id) {
                $ftgId = $order->fotografer_user_id;
                if (!isset($karyawanStats[$ftgId])) {
                    $karyawanStats[$ftgId] = [
                        'id' => $ftgId,
                        'nama' => $order->fotografer?->name ?? "User #$ftgId",
                        'role' => 'Fotografer',
                        'cabang_id' => $order->cabang_id,
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$ftgId]['jumlah_order']++;
                $pesertaFotografer[$ftgId] = true;
            }
        }

        // Calculate bagi hasil
        $jumlahPesertaCsKasirSpv = count($pesertaCsKasirSpv);
        $jumlahPesertaFotografer = count($pesertaFotografer);

        $nilaiBagihasilCsKasirSpv = ($totalOmset * $persenCsKasirSpv) / 100;
        $nilaiBagihasilFotografer = ($totalOmset * $persenFotografer) / 100;

        // Hitung bagi hasil proporsional berdasarkan jumlah transaksi
        foreach ($karyawanStats as &$stats) {
            if ($jumlahOrder > 0) {
                if ($stats['role'] === 'Fotografer') {
                    // Bagi hasil proporsional untuk fotografer
                    $stats['bagi_hasil'] = ($stats['jumlah_order'] / $jumlahOrder) * $nilaiBagihasilFotografer;
                } else {
                    // Bagi hasil proporsional untuk CS/Kasir/SPV
                    $stats['bagi_hasil'] = ($stats['jumlah_order'] / $jumlahOrder) * $nilaiBagihasilCsKasirSpv;
                }
            } else {
                $stats['bagi_hasil'] = 0;
            }
        }
        unset($stats);

        // Filter by karyawan if selected
        if ($karyawanId) {
            $karyawanStats = array_filter($karyawanStats, fn($k) => $k['id'] == $karyawanId);
        }

        // Sort by jumlah_order descending
        usort($karyawanStats, fn($a, $b) => $b['jumlah_order'] <=> $a['jumlah_order']);

        // Get list karyawan for filter dropdown
        $karyawanList = User::query()
            ->with(['role:id,nama'])
            ->where('status', true)
            ->when($cabangId, function ($q) use ($cabangId) {
                $q->whereHas('cabang', fn($inner) => $inner->where('cabang.id', $cabangId));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role_id']);

        return view('pages.pos.laporan-kpi', [
            'karyawanStats' => array_values($karyawanStats),
            'karyawanList' => $karyawanList,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'filterCabangId' => $cabangId,
            'filterDateFrom' => $dateFrom,
            'filterDateTo' => $dateTo,
            'filterKaryawanId' => $karyawanId,
            'summary' => [
                'total_omset' => $totalOmset,
                'jumlah_order' => $jumlahOrder,
                'jumlah_karyawan' => count($karyawanStats),
                'persen_cs_kasir_spv' => $persenCsKasirSpv,
                'persen_fotografer' => $persenFotografer,
                'nilai_bagi_hasil_cs_kasir_spv' => $nilaiBagihasilCsKasirSpv,
                'nilai_bagi_hasil_fotografer' => $nilaiBagihasilFotografer,
                'jumlah_peserta_cs_kasir_spv' => $jumlahPesertaCsKasirSpv,
                'jumlah_peserta_fotografer' => $jumlahPesertaFotografer,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $cabangId = $this->resolveCabangFilter($request);
        $allowedCabangIds = $this->accessibleCabangIds();

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));

        $baseQuery = PesananPenjualan::query()
            ->whereNotIn('status_pembayaran', ['CANCELLED', 'VOID'])
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if (!empty($allowedCabangIds)) {
            $baseQuery->whereIn('cabang_id', $allowedCabangIds);
        }
        if ($cabangId) {
            $baseQuery->where('cabang_id', $cabangId);
        }

        $orders = (clone $baseQuery)
            ->with(['kasir:id,name', 'spv:id,name', 'fotografer:id,name', 'cs:id,name', 'cs1:id,name', 'cs2:id,name', 'cabang:id,nama'])
            ->get();

        $totalOmset = (float) $orders->sum('total');

        $kpiConfig = KpiKonfigurasi::query()
            ->when($cabangId, fn($q) => $q->where('cabang_id', $cabangId))
            ->where('status', true)
            ->first();

        $persenCsKasirSpv = $kpiConfig?->persen_cs_kasir_spv ?? 60;
        $persenFotografer = $kpiConfig?->persen_fotografer ?? 40;
        $includeKasir = $kpiConfig?->include_kasir ?? true;
        $includeSpv = $kpiConfig?->include_spv ?? true;

        $karyawanStats = [];
        $pesertaCsKasirSpv = [];
        $pesertaFotografer = [];

        foreach ($orders as $order) {
            if ($order->kasir_user_id && $includeKasir) {
                $kasirId = $order->kasir_user_id;
                if (!isset($karyawanStats[$kasirId])) {
                    $karyawanStats[$kasirId] = [
                        'nama' => $order->kasir?->name ?? "User #$kasirId",
                        'role' => 'Kasir',
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$kasirId]['jumlah_order']++;
                $pesertaCsKasirSpv[$kasirId] = true;
            }

            $csIds = array_filter([
                $order->cs_user_id,
                $order->cs1_user_id,
                $order->cs2_user_id,
            ]);

            foreach ($csIds as $csId) {
                if (!isset($karyawanStats[$csId])) {
                    $csName = match(true) {
                        $csId == $order->cs_user_id => $order->cs?->name ?? "User #$csId",
                        $csId == $order->cs1_user_id => $order->cs1?->name ?? "User #$csId",
                        $csId == $order->cs2_user_id => $order->cs2?->name ?? "User #$csId",
                        default => "User #$csId",
                    };
                    $karyawanStats[$csId] = [
                        'nama' => $csName,
                        'role' => 'CS',
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$csId]['jumlah_order']++;
                $pesertaCsKasirSpv[$csId] = true;
            }

            if ($order->spv_user_id && $includeSpv) {
                $spvId = $order->spv_user_id;
                if (!isset($karyawanStats[$spvId])) {
                    $karyawanStats[$spvId] = [
                        'nama' => $order->spv?->name ?? "User #$spvId",
                        'role' => 'SPV',
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$spvId]['jumlah_order']++;
                $pesertaCsKasirSpv[$spvId] = true;
            }

            if ($order->fotografer_user_id) {
                $ftgId = $order->fotografer_user_id;
                if (!isset($karyawanStats[$ftgId])) {
                    $karyawanStats[$ftgId] = [
                        'nama' => $order->fotografer?->name ?? "User #$ftgId",
                        'role' => 'Fotografer',
                        'cabang' => $order->cabang?->nama ?? '-',
                        'jumlah_order' => 0,
                    ];
                }
                $karyawanStats[$ftgId]['jumlah_order']++;
                $pesertaFotografer[$ftgId] = true;
            }
        }

        usort($karyawanStats, fn($a, $b) => $b['jumlah_order'] <=> $a['jumlah_order']);

        $rows = array_map(fn($k) => [
            $k['nama'],
            $k['role'],
            $k['cabang'],
            $k['jumlah_order'],
        ], array_values($karyawanStats));

        return app(\App\Services\XlsxExportService::class)->download(
            'laporan-kpi-' . now()->format('Ymd-His') . '.xlsx',
            ['Nama Karyawan', 'Role', 'Cabang', 'Jumlah Order Ditangani'],
            $rows,
            'KPI'
        );
    }

    public function konfigurasi(Request $request)
    {
        $cabangId = $this->resolveCabangFilter($request);
        $allowedCabangIds = $this->accessibleCabangIds();

        $query = KpiKonfigurasi::query()
            ->with('cabang:id,nama');

        if (!empty($allowedCabangIds)) {
            $query->whereIn('cabang_id', $allowedCabangIds);
        }
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        $configs = $query->orderBy('cabang_id')->paginate(15);

        return view('pages.pos.laporan-kpi-konfigurasi', [
            'configs' => $configs,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'filterCabangId' => $cabangId,
        ]);
    }

    public function simpanKonfigurasi(Request $request)
    {
        $validated = $request->validate([
            'cabang_id' => ['required', 'exists:cabang,id'],
            'nama_konfigurasi' => ['required', 'string', 'max:100'],
            'persen_cs_kasir_spv' => ['required', 'numeric', 'min:0', 'max:100'],
            'persen_fotografer' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'include_kasir' => ['nullable', 'boolean'],
            'include_spv' => ['nullable', 'boolean'],
        ]);

        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        $persenCs = (float) $validated['persen_cs_kasir_spv'];
        $persenFtg = (float) ($validated['persen_fotografer'] ?? 0);
        if (abs(($persenCs + $persenFtg) - 100) > 0.01) {
            return back()->withErrors([
                'persen_fotografer' => 'Total persentase CS+Kasir+SPV dan Fotografer harus sama dengan 100%.'
            ])->withInput();
        }

        KpiKonfigurasi::updateOrCreate(
            [
                'cabang_id' => $validated['cabang_id'],
                'nama_konfigurasi' => $validated['nama_konfigurasi'],
            ],
            [
                'persen_cs_kasir_spv' => $persenCs,
                'persen_fotografer' => $persenFtg,
                'include_kasir' => $request->boolean('include_kasir'),
                'include_spv' => $request->boolean('include_spv'),
                'status' => true,
            ]
        );

        return redirect()
            ->route('laporan-kpi.konfigurasi')
            ->with('success', 'Konfigurasi KPI berhasil disimpan.');
    }
}

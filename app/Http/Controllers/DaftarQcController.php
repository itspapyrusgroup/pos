<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\KantongOrder;
use App\Models\KoTrackingKoCheck;
use App\Models\TrackingReference;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DaftarQcController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'no_ko' => ['nullable', 'string', 'max:60'],
            'status_qc' => ['nullable', 'in:ALL,CHECKED,UNCHECKED'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
        ]);

        $statusQc = $validated['status_qc'] ?? 'ALL';
        $dateFrom = $validated['date_from'] ?? now()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $this->ensureCabangAccessible($cabangId);

        $qcStepCode = (string) (
            TrackingReference::query()
                ->where('tipe', 'KO')
                ->whereRaw('UPPER(kode) = ?', ['QC_PAKET'])
                ->value('kode')
            ?? 'QC_PAKET'
        );

        $baseQuery = KantongOrder::query()
            ->with([
                'pesananPenjualan:id,cabang_id,customer_name,pelanggan_id',
                'pesananPenjualan.pelanggan:id,nama',
                'pesananPenjualan.cabang:id,nama',
            ])
            ->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $this->applyCabangScope($q);
                if ($cabangId) {
                    $q->where('cabang_id', $cabangId);
                }
            })
            ->whereDate('kantong_order.created_at', '>=', $dateFrom)
            ->whereDate('kantong_order.created_at', '<=', $dateTo);

        if (!empty($validated['no_ko'])) {
            $keyword = trim((string) $validated['no_ko']);
            $baseQuery->where('nomor_ko', 'like', '%' . $keyword . '%');
        }

        $checkedScope = function ($q) use ($qcStepCode): void {
            $q->whereExists(function ($sub) use ($qcStepCode) {
                $sub->select(DB::raw(1))
                    ->from('ko_tracking_ko_checks as kk')
                    ->whereColumn('kk.pesanan_penjualan_id', 'kantong_order.pesanan_penjualan_id')
                    ->whereRaw('UPPER(kk.step_kode) = ?', [strtoupper($qcStepCode)])
                    ->where('kk.is_checked', true);
            });
        };

        if ($statusQc === 'CHECKED') {
            $checkedScope($baseQuery);
        } elseif ($statusQc === 'UNCHECKED') {
            $baseQuery->whereNotExists(function ($sub) use ($qcStepCode) {
                $sub->select(DB::raw(1))
                    ->from('ko_tracking_ko_checks as kk')
                    ->whereColumn('kk.pesanan_penjualan_id', 'kantong_order.pesanan_penjualan_id')
                    ->whereRaw('UPPER(kk.step_kode) = ?', [strtoupper($qcStepCode)])
                    ->where('kk.is_checked', true);
            });
        }

        $summaryQuery = clone $baseQuery;
        $totalKo = (clone $summaryQuery)->count();
        $checkedKo = (clone $summaryQuery)->where($checkedScope)->count();
        $uncheckedKo = max($totalKo - $checkedKo, 0);

        $rows = $baseQuery
            ->latest('kantong_order.id')
            ->paginate(20)
            ->withQueryString();

        $orderIds = $rows->getCollection()
            ->pluck('pesanan_penjualan_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $qcChecksByOrder = KoTrackingKoCheck::query()
            ->with('checkedBy:id,name')
            ->whereIn('pesanan_penjualan_id', $orderIds)
            ->whereRaw('UPPER(step_kode) = ?', [strtoupper($qcStepCode)])
            ->get()
            ->keyBy('pesanan_penjualan_id');

        /** @var LengthAwarePaginator $rows */
        $rows->setCollection(
            $rows->getCollection()->map(function (KantongOrder $ko) use ($qcChecksByOrder) {
                $check = $qcChecksByOrder->get((int) $ko->pesanan_penjualan_id);

                return [
                    'id' => (int) $ko->id,
                    'nomor_ko' => (string) $ko->nomor_ko,
                    'tanggal_selesai' => $ko->tanggal_selesai,
                    'customer_name' => (string) (
                        $ko->pesananPenjualan?->customer_name
                        ?: ($ko->pesananPenjualan?->pelanggan?->nama ?? '-')
                    ),
                    'cabang_nama' => (string) ($ko->pesananPenjualan?->cabang?->nama ?? '-'),
                    'is_qc_checked' => (bool) ($check?->is_checked ?? false),
                    'qc_checked_by' => $check?->checkedBy?->name,
                    'qc_checked_at' => $check?->checked_at,
                ];
            })
        );

        return view('daftar-qc', [
            'rows' => $rows,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'filters' => [
                'no_ko' => $validated['no_ko'] ?? '',
                'status_qc' => $statusQc,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
            ],
            'summary' => [
                'total_ko' => $totalKo,
                'checked_ko' => $checkedKo,
                'unchecked_ko' => $uncheckedKo,
            ],
        ]);
    }
}

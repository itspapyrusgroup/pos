<?php

namespace App\Http\Controllers;

use App\Models\JabatanTrackingReference;
use App\Models\KantongOrder;
use App\Models\KoTrackingItemCheck;
use App\Models\TrackingReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PekerjaanDgController extends Controller
{
    private const DG_TRACKING_CODES = ['SPV_DG', 'STAFF_DG'];

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'no_ko' => ['nullable', 'string', 'max:60'],
            'status_dg' => ['nullable', 'in:ALL,DONE,PENDING'],
            'deadline_from' => ['nullable', 'date'],
            'deadline_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
        ]);

        $statusDg = $validated['status_dg'] ?? 'PENDING';
        $deadlineFrom = $validated['deadline_from'] ?? null;
        $deadlineTo = $validated['deadline_to'] ?? null;
        if ($deadlineFrom && $deadlineTo && $deadlineFrom > $deadlineTo) {
            [$deadlineFrom, $deadlineTo] = [$deadlineTo, $deadlineFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $this->ensureCabangAccessible($cabangId);

        $dgTrackingIds = $this->resolveDgTrackingReferenceIds();

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
            });

        if (!empty($validated['no_ko'])) {
            $baseQuery->where('nomor_ko', 'like', '%' . trim((string) $validated['no_ko']) . '%');
        }

        if ($deadlineFrom) {
            $baseQuery->whereDate('tanggal_selesai', '>=', $deadlineFrom);
        }
        if ($deadlineTo) {
            $baseQuery->whereDate('tanggal_selesai', '<=', $deadlineTo);
        }

        $this->applyHasDgItemsFilter($baseQuery, $dgTrackingIds);

        if ($statusDg === 'DONE') {
            $this->applyDoneFilter($baseQuery, $dgTrackingIds);
        } elseif ($statusDg === 'PENDING') {
            $this->applyPendingFilter($baseQuery, $dgTrackingIds);
        }

        $summaryBase = clone $baseQuery;
        $totalKo = (clone $summaryBase)->count();
        $doneQuery = clone $summaryBase;
        $this->applyDoneFilter($doneQuery, $dgTrackingIds);
        $doneKo = $doneQuery->count();
        $pendingKo = max($totalKo - $doneKo, 0);

        $rows = $baseQuery->latest('kantong_order.id')->paginate(20)->withQueryString();
        $orderIds = $rows->getCollection()
            ->pluck('pesanan_penjualan_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $requiredMap = $this->resolveRequiredDgItemsByOrder($orderIds, $dgTrackingIds);
        $requiredKeys = collect($requiredMap)
            ->flatMap(fn (array $orderMap) => array_keys($orderMap['items']))
            ->unique()
            ->values();

        $checkRowsByKey = collect();
        if ($requiredKeys->isNotEmpty()) {
            $pairRows = $requiredKeys->map(function (string $key) {
                [$orderItemId, $produkId] = array_map('intval', explode(':', $key));
                return ['order_item_id' => $orderItemId, 'produk_id' => $produkId];
            });

            $orderItemIds = $pairRows->pluck('order_item_id')->unique()->values()->all();
            $produkIds = $pairRows->pluck('produk_id')->unique()->values()->all();

            $checkRowsByKey = KoTrackingItemCheck::query()
                ->with('checkedBy:id,name')
                ->whereIn('pesanan_penjualan_item_id', $orderItemIds)
                ->whereIn('produk_id', $produkIds)
                ->get()
                ->keyBy(fn ($row) => (int) $row->pesanan_penjualan_item_id . ':' . (int) $row->produk_id);
        }

        $canMarkDone = $this->userCanUpdateDgTracking($dgTrackingIds);

        /** @var LengthAwarePaginator $rows */
        $rows->setCollection(
            $rows->getCollection()->map(function (KantongOrder $ko) use ($requiredMap, $checkRowsByKey, $canMarkDone) {
                $orderId = (int) $ko->pesanan_penjualan_id;
                $required = $requiredMap[$orderId] ?? ['items' => [], 'paket' => []];
                $requiredItems = $required['items'] ?? [];
                $requiredCount = count($requiredItems);

                $checkedRows = collect($requiredItems)
                    ->map(fn ($item) => $checkRowsByKey->get($item['key']))
                    ->filter(fn ($row) => (bool) ($row?->is_checked ?? false))
                    ->values();
                $checkedCount = $checkedRows->count();
                $isDone = $requiredCount > 0 && $checkedCount >= $requiredCount;
                $latestCheckedRow = $checkedRows->sortByDesc(fn ($row) => (string) ($row->checked_at ?? ''))->first();

                return [
                    'id' => (int) $ko->id,
                    'nomor_ko' => (string) $ko->nomor_ko,
                    'pesanan_penjualan_id' => $orderId,
                    'customer_name' => (string) (
                        $ko->pesananPenjualan?->customer_name
                        ?: ($ko->pesananPenjualan?->pelanggan?->nama ?? '-')
                    ),
                    'cabang_nama' => (string) ($ko->pesananPenjualan?->cabang?->nama ?? '-'),
                    'tanggal_selesai' => $ko->tanggal_selesai,
                    'paket_names' => array_values($required['paket'] ?? []),
                    'required_count' => $requiredCount,
                    'checked_count' => $checkedCount,
                    'is_done' => $isDone,
                    'done_by' => $isDone ? ($latestCheckedRow?->checkedBy?->name ?? '-') : null,
                    'done_at' => $isDone ? ($latestCheckedRow?->checked_at ?? null) : null,
                    'can_mark_done' => $canMarkDone,
                ];
            })
        );

        return view('pekerjaan-dg', [
            'rows' => $rows,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'filters' => [
                'no_ko' => $validated['no_ko'] ?? '',
                'status_dg' => $statusDg,
                'deadline_from' => $deadlineFrom,
                'deadline_to' => $deadlineTo,
                'cabang_id' => $cabangId,
            ],
            'summary' => [
                'total_ko' => $totalKo,
                'done_ko' => $doneKo,
                'pending_ko' => $pendingKo,
            ],
        ]);
    }

    public function markDone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'no_ko' => ['required', 'string', 'max:60'],
        ]);

        $noKo = trim((string) $validated['no_ko']);
        $ko = KantongOrder::query()
            ->with('pesananPenjualan:id,cabang_id')
            ->where('nomor_ko', $noKo)
            ->first();

        if (!$ko?->pesananPenjualan) {
            throw ValidationException::withMessages([
                'no_ko' => ['No KO tidak ditemukan.'],
            ]);
        }

        $this->ensureCabangAccessible((int) $ko->pesananPenjualan->cabang_id);
        $dgTrackingIds = $this->resolveDgTrackingReferenceIds();
        if (!$this->userCanUpdateDgTracking($dgTrackingIds)) {
            throw ValidationException::withMessages([
                'tracking' => ['Anda tidak berhak menyelesaikan pekerjaan DG.'],
            ]);
        }

        $requiredMap = $this->resolveRequiredDgItemsByOrder([(int) $ko->pesanan_penjualan_id], $dgTrackingIds);
        $requiredItems = ($requiredMap[(int) $ko->pesanan_penjualan_id]['items'] ?? []);
        if (empty($requiredItems)) {
            return back()->with('warning', 'KO ini tidak memiliki item pekerjaan Desain Grafis.');
        }

        DB::transaction(function () use ($requiredItems) {
            foreach ($requiredItems as $required) {
                $row = KoTrackingItemCheck::query()
                    ->where('pesanan_penjualan_item_id', (int) $required['order_item_id'])
                    ->where('produk_id', (int) $required['produk_id'])
                    ->first();

                if ($row && $row->is_checked) {
                    continue;
                }

                KoTrackingItemCheck::query()->updateOrCreate(
                    [
                        'pesanan_penjualan_item_id' => (int) $required['order_item_id'],
                        'produk_id' => (int) $required['produk_id'],
                    ],
                    [
                        'is_checked' => true,
                        'checked_at' => now(),
                        'checked_by_user_id' => (int) auth()->id(),
                    ]
                );
            }
        });

        return back()->with('success', 'Pekerjaan DG untuk KO ' . $noKo . ' berhasil ditandai selesai.');
    }

    private function resolveDgTrackingReferenceIds(): array
    {
        $ids = TrackingReference::query()
            ->where('tipe', 'ITEM')
            ->where(function ($q) {
                foreach (self::DG_TRACKING_CODES as $code) {
                    $q->orWhereRaw('UPPER(kode) = ?', [strtoupper($code)]);
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids;
    }

    private function resolveRequiredDgItemsByOrder(array $orderIds, array $dgTrackingIds): array
    {
        if (empty($orderIds) || empty($dgTrackingIds)) {
            return [];
        }

        $rows = DB::table('pesanan_penjualan_item as ppi')
            ->join('paket as p', 'p.id', '=', 'ppi.paket_id')
            ->join('paket_item as pi', 'pi.paket_id', '=', 'p.kode')
            ->join('produk as pr', 'pr.id', '=', 'pi.produk_id')
            ->join('kategori_produk as kp', 'kp.kode', '=', 'pr.kategori_produk_kode')
            ->whereIn('ppi.pesanan_penjualan_id', $orderIds)
            ->where('ppi.is_void', false)
            ->whereIn('kp.tracking_reference_id', $dgTrackingIds)
            ->get([
                'ppi.pesanan_penjualan_id',
                'ppi.id as order_item_id',
                'pi.produk_id',
                'p.nama as paket_nama',
            ]);

        $map = [];
        foreach ($rows as $row) {
            $orderId = (int) $row->pesanan_penjualan_id;
            if (!isset($map[$orderId])) {
                $map[$orderId] = [
                    'items' => [],
                    'paket' => [],
                ];
            }

            $key = (int) $row->order_item_id . ':' . (int) $row->produk_id;
            $map[$orderId]['items'][$key] = [
                'key' => $key,
                'order_item_id' => (int) $row->order_item_id,
                'produk_id' => (int) $row->produk_id,
            ];
            $map[$orderId]['paket'][(string) $row->paket_nama] = (string) $row->paket_nama;
        }

        return $map;
    }

    private function applyHasDgItemsFilter($query, array $dgTrackingIds): void
    {
        if (empty($dgTrackingIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereExists(function ($sub) use ($dgTrackingIds) {
            $sub->select(DB::raw(1))
                ->from('pesanan_penjualan_item as ppi')
                ->join('paket as p', 'p.id', '=', 'ppi.paket_id')
                ->join('paket_item as pi', 'pi.paket_id', '=', 'p.kode')
                ->join('produk as pr', 'pr.id', '=', 'pi.produk_id')
                ->join('kategori_produk as kp', 'kp.kode', '=', 'pr.kategori_produk_kode')
                ->whereColumn('ppi.pesanan_penjualan_id', 'kantong_order.pesanan_penjualan_id')
                ->where('ppi.is_void', false)
                ->whereIn('kp.tracking_reference_id', $dgTrackingIds);
        });
    }

    private function applyPendingFilter($query, array $dgTrackingIds): void
    {
        if (empty($dgTrackingIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereExists(function ($sub) use ($dgTrackingIds) {
            $sub->select(DB::raw(1))
                ->from('pesanan_penjualan_item as ppi')
                ->join('paket as p', 'p.id', '=', 'ppi.paket_id')
                ->join('paket_item as pi', 'pi.paket_id', '=', 'p.kode')
                ->join('produk as pr', 'pr.id', '=', 'pi.produk_id')
                ->join('kategori_produk as kp', 'kp.kode', '=', 'pr.kategori_produk_kode')
                ->whereColumn('ppi.pesanan_penjualan_id', 'kantong_order.pesanan_penjualan_id')
                ->where('ppi.is_void', false)
                ->whereIn('kp.tracking_reference_id', $dgTrackingIds)
                ->whereNotExists(function ($checkSub) {
                    $checkSub->select(DB::raw(1))
                        ->from('ko_tracking_item_checks as kic')
                        ->whereColumn('kic.pesanan_penjualan_item_id', 'ppi.id')
                        ->whereColumn('kic.produk_id', 'pi.produk_id')
                        ->where('kic.is_checked', true);
                });
        });
    }

    private function applyDoneFilter($query, array $dgTrackingIds): void
    {
        if (empty($dgTrackingIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereNotExists(function ($sub) use ($dgTrackingIds) {
            $sub->select(DB::raw(1))
                ->from('pesanan_penjualan_item as ppi')
                ->join('paket as p', 'p.id', '=', 'ppi.paket_id')
                ->join('paket_item as pi', 'pi.paket_id', '=', 'p.kode')
                ->join('produk as pr', 'pr.id', '=', 'pi.produk_id')
                ->join('kategori_produk as kp', 'kp.kode', '=', 'pr.kategori_produk_kode')
                ->whereColumn('ppi.pesanan_penjualan_id', 'kantong_order.pesanan_penjualan_id')
                ->where('ppi.is_void', false)
                ->whereIn('kp.tracking_reference_id', $dgTrackingIds)
                ->whereNotExists(function ($checkSub) {
                    $checkSub->select(DB::raw(1))
                        ->from('ko_tracking_item_checks as kic')
                        ->whereColumn('kic.pesanan_penjualan_item_id', 'ppi.id')
                        ->whereColumn('kic.produk_id', 'pi.produk_id')
                        ->where('kic.is_checked', true);
                });
        });
    }

    private function userCanUpdateDgTracking(array $dgTrackingIds): bool
    {
        if (empty($dgTrackingIds)) {
            return false;
        }

        $user = auth()->user();
        $user?->loadMissing('karyawan.jabatan');
        $jabatanId = (int) ($user?->karyawan?->jabatan_id ?? 0);
        if ($jabatanId <= 0) {
            return false;
        }

        $allowedTrackingIds = JabatanTrackingReference::query()
            ->where('jabatan_id', $jabatanId)
            ->where('can_update', true)
            ->pluck('tracking_reference_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return collect($allowedTrackingIds)->intersect($dgTrackingIds)->isNotEmpty();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\JabatanTrackingReference;
use App\Models\KantongOrder;
use App\Models\KoTrackingItemCheck;
use App\Models\KoTrackingKoCheck;
use App\Models\PesananPenjualanItem;
use App\Models\TrackingReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TrackingOrderController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'no_ko' => ['nullable', 'string', 'max:60'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
        ]);

        $ko = null;
        $itemTrackingGroups = collect();
        $koStepChecks = collect();
        $overdueKoList = collect();
        $selectedKoProgress = null;
        $user = Auth::user();
        $user?->loadMissing('karyawan.jabatan');

        $allowedTrackingIds = $this->resolveAllowedTrackingIds((int) ($user?->karyawan?->jabatan_id ?? 0));
        $allowedKoStepCodes = TrackingReference::query()
            ->whereIn('id', $allowedTrackingIds)
            ->where('tipe', 'KO')
            ->pluck('kode')
            ->map(fn ($kode) => strtoupper((string) $kode))
            ->all();

        $koSteps = TrackingReference::query()
            ->where('status', true)
            ->where('tipe', 'KO')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['kode', 'nama']);

        $selectedCabangId = $request->filled('cabang_id')
            ? (int) $request->input('cabang_id')
            : null;
        if ($selectedCabangId) {
            $this->ensureCabangAccessible($selectedCabangId);
        }

        $today = now()->toDateString();
        $overdueKoList = KantongOrder::query()
            ->with([
                'pesananPenjualan:id,pelanggan_id,cabang_id,customer_name',
                'pesananPenjualan.pelanggan:id,nama',
            ])
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', $today)
            ->whereHas('pesananPenjualan', function ($q) use ($selectedCabangId) {
                $this->applyCabangScope($q);
                if ($selectedCabangId) {
                    $q->where('cabang_id', $selectedCabangId);
                }
            })
            ->orderBy('tanggal_selesai')
            ->orderBy('nomor_ko')
            ->get()
            ->map(function (KantongOrder $row) use ($koSteps) {
                if (!$row->pesananPenjualan) {
                    return null;
                }

                $progress = $this->resolveTrackingProgress((int) $row->pesanan_penjualan_id, $koSteps);
                if ($progress['is_finished']) {
                    return null;
                }

                return [
                    'nomor_ko' => $row->nomor_ko,
                    'tanggal_selesai' => $row->tanggal_selesai,
                    'customer_name' => $row->pesananPenjualan->customer_name ?: ($row->pesananPenjualan->pelanggan?->nama ?? '-'),
                    'unchecked_ko_steps' => $progress['unchecked_ko_steps'],
                    'unchecked_item_steps' => $progress['unchecked_item_steps'],
                ];
            })
            ->filter()
            ->values();

        if (!empty($validated['no_ko'])) {
            $noKo = trim((string) $validated['no_ko']);

            $ko = KantongOrder::query()
                ->with([
                    'pesananPenjualan.items.paket.items.produk.kategoriProduk.trackingReference',
                    'pesananPenjualan.pelanggan',
                ])
                ->where('nomor_ko', $noKo)
                ->first();

            if ($ko?->pesananPenjualan) {
                $this->ensureCabangAccessible((int) $ko->pesananPenjualan->cabang_id);
                $orderId = (int) $ko->pesananPenjualan->id;

                $koChecksByKode = KoTrackingKoCheck::query()
                    ->with('checkedBy:id,name')
                    ->where('pesanan_penjualan_id', $orderId)
                    ->whereIn('step_kode', $koSteps->pluck('kode')->all())
                    ->get()
                    ->keyBy(fn ($row) => strtoupper((string) $row->step_kode));

                $koStepChecks = $koSteps->map(function ($step) use ($koChecksByKode, $allowedKoStepCodes) {
                    $kode = strtoupper((string) $step->kode);
                    $row = $koChecksByKode->get($kode);

                    return [
                        'kode' => $kode,
                        'nama' => $step->nama,
                        'is_checked' => (bool) ($row?->is_checked ?? false),
                        'checked_by' => $row?->checkedBy?->name,
                        'checked_at' => $row?->checked_at,
                        'can_update' => in_array($kode, $allowedKoStepCodes, true),
                    ];
                })->values();

                $orderItems = $ko->pesananPenjualan->items->where('is_void', false)->sortBy('id')->values();
                $checkRows = KoTrackingItemCheck::query()
                    ->with('checkedBy:id,name')
                    ->whereIn('pesanan_penjualan_item_id', $orderItems->pluck('id')->all())
                    ->get()
                    ->keyBy(fn ($row) => $row->pesanan_penjualan_item_id . ':' . $row->produk_id);

                $itemTrackingGroups = $orderItems->map(function ($orderItem) use ($checkRows, $allowedTrackingIds) {
                    $paket = $orderItem->paket;
                    $paketItems = collect($paket?->items ?? [])
                        ->sortBy('id')
                        ->map(function ($paketItem) use ($checkRows, $orderItem, $allowedTrackingIds) {
                            $kategori = $paketItem->produk?->kategoriProduk;
                            $trackingId = (int) ($kategori?->tracking_reference_id ?? 0);
                            $key = $orderItem->id . ':' . $paketItem->produk_id;
                            $checkRow = $checkRows->get($key);

                            return [
                                'produk_id' => (int) ($paketItem->produk_id ?? 0),
                                'nama' => $paketItem->produk?->nama ?? '-',
                                'kategori' => $kategori?->nama ?? '-',
                                'tracking_nama' => $kategori?->trackingReference?->nama ?? '-',
                                'qty' => (float) ($paketItem->qty ?? 0),
                                'total_qty' => (float) ($paketItem->qty ?? 0) * (float) ($orderItem->qty ?? 0),
                                'is_checked' => (bool) ($checkRow?->is_checked ?? false),
                                'checked_by' => $checkRow?->checkedBy?->name,
                                'checked_at' => $checkRow?->checked_at,
                                'can_update' => $trackingId > 0 && in_array($trackingId, $allowedTrackingIds, true),
                            ];
                        })
                        ->values();

                    return [
                        'order_item_id' => (int) $orderItem->id,
                        'paket_nama' => $paket?->nama ?? ($orderItem->produk?->nama ?? 'Item'),
                        'order_qty' => (float) ($orderItem->qty ?? 0),
                        'paket_items' => $paketItems,
                    ];
                })->values();

                $selectedKoProgress = $this->resolveTrackingProgress($orderId, $koSteps);
            }
        }

        return view('tracking-order', [
            'ko' => $ko,
            'user' => $user,
            'allowedKoStepCodes' => $allowedKoStepCodes,
            'koStepChecks' => $koStepChecks,
            'itemTrackingGroups' => $itemTrackingGroups,
            'overdueKoList' => $overdueKoList,
            'selectedKoProgress' => $selectedKoProgress,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'filters' => [
                'no_ko' => $validated['no_ko'] ?? '',
                'cabang_id' => $selectedCabangId,
            ],
        ]);
    }

    private function resolveTrackingProgress(int $pesananPenjualanId, Collection $koSteps): array
    {
        $koStepCodes = $koSteps
            ->pluck('kode')
            ->map(fn ($kode) => strtoupper((string) $kode))
            ->filter()
            ->unique()
            ->values();

        $checkedKoStepCodes = collect();
        if ($koStepCodes->isNotEmpty()) {
            $checkedKoStepCodes = KoTrackingKoCheck::query()
                ->where('pesanan_penjualan_id', $pesananPenjualanId)
                ->where('is_checked', true)
                ->whereIn('step_kode', $koStepCodes->all())
                ->pluck('step_kode')
                ->map(fn ($kode) => strtoupper((string) $kode))
                ->unique()
                ->values();
        }

        $orderItems = PesananPenjualanItem::query()
            ->with('paket.items:id,paket_id,produk_id')
            ->where('pesanan_penjualan_id', $pesananPenjualanId)
            ->where('is_void', false)
            ->get(['id', 'paket_id']);

        $requiredItemKeys = collect();
        foreach ($orderItems as $orderItem) {
            foreach (($orderItem->paket?->items ?? collect()) as $paketItem) {
                $requiredItemKeys->push((int) $orderItem->id . ':' . (int) $paketItem->produk_id);
            }
        }
        $requiredItemKeys = $requiredItemKeys->unique()->values();

        $checkedItemKeys = collect();
        if ($requiredItemKeys->isNotEmpty()) {
            $checkedItemKeys = KoTrackingItemCheck::query()
                ->whereIn('pesanan_penjualan_item_id', $orderItems->pluck('id')->all())
                ->where('is_checked', true)
                ->get(['pesanan_penjualan_item_id', 'produk_id'])
                ->map(fn ($row) => (int) $row->pesanan_penjualan_item_id . ':' . (int) $row->produk_id)
                ->unique()
                ->values();
        }

        $uncheckedKoSteps = max($koStepCodes->count() - $checkedKoStepCodes->count(), 0);
        $checkedItemCount = $requiredItemKeys->intersect($checkedItemKeys)->count();
        $uncheckedItemSteps = max($requiredItemKeys->count() - $checkedItemCount, 0);

        return [
            'unchecked_ko_steps' => $uncheckedKoSteps,
            'unchecked_item_steps' => $uncheckedItemSteps,
            'is_finished' => $uncheckedKoSteps === 0 && $uncheckedItemSteps === 0,
        ];
    }

    public function updateItemCheck(Request $request)
    {
        $data = $request->validate([
            'no_ko' => ['required', 'string'],
            'pesanan_penjualan_item_id' => ['required', 'integer', 'exists:pesanan_penjualan_item,id'],
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
            'is_checked' => ['nullable', 'boolean'],
        ]);

        $noKo = trim((string) $data['no_ko']);
        $ko = KantongOrder::query()
            ->with([
                'pesananPenjualan.items.paket.items.produk.kategoriProduk.trackingReference',
            ])
            ->where('nomor_ko', $noKo)
            ->first();

        if (!$ko?->pesananPenjualan) {
            return $this->trackingError($request, 'No KO tidak ditemukan.');
        }

        $this->ensureCabangAccessible((int) $ko->pesananPenjualan->cabang_id);

        $orderItem = $ko->pesananPenjualan->items
            ->firstWhere('id', (int) $data['pesanan_penjualan_item_id']);

        if (!$orderItem || !$orderItem->paket) {
            return $this->trackingError($request, 'Item order tidak valid untuk KO ini.');
        }

        $paketItem = collect($orderItem->paket->items)
            ->first(fn ($item) => (int) $item->produk_id === (int) $data['produk_id']);

        if (!$paketItem) {
            return $this->trackingError($request, 'Produk tidak ada di paket item order ini.');
        }

        $user = Auth::user();
        if (!$user) {
            return $this->trackingError($request, 'User tidak valid.', 401);
        }
        $user->loadMissing('karyawan.jabatan');

        $allowedTrackingIds = $this->resolveAllowedTrackingIds((int) ($user->karyawan?->jabatan_id ?? 0));
        $trackingId = (int) ($paketItem->produk?->kategoriProduk?->tracking_reference_id ?? 0);
        if ($trackingId <= 0 || !in_array($trackingId, $allowedTrackingIds, true)) {
            return $this->trackingError($request, 'Anda tidak berhak mencentang item ini.', 403);
        }

        $isChecked = (bool) ($data['is_checked'] ?? false);
        KoTrackingItemCheck::query()->updateOrCreate(
            [
                'pesanan_penjualan_item_id' => (int) $data['pesanan_penjualan_item_id'],
                'produk_id' => (int) $data['produk_id'],
            ],
            [
                'is_checked' => $isChecked,
                'checked_at' => $isChecked ? now() : null,
                'checked_by_user_id' => $isChecked ? $user->id : null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Checklist item berhasil diperbarui.',
            ]);
        }

        return redirect()->route('tracking-order', ['no_ko' => $noKo])->with('success', 'Checklist item berhasil diperbarui.');
    }

    public function updateKoCheck(Request $request)
    {
        $allowedCodes = TrackingReference::query()
            ->where('status', true)
            ->where('tipe', 'KO')
            ->pluck('kode')
            ->map(fn ($kode) => strtoupper((string) $kode))
            ->all();

        $data = $request->validate([
            'no_ko' => ['required', 'string'],
            'step_kode' => ['required', 'string', 'in:' . implode(',', $allowedCodes)],
            'is_checked' => ['nullable', 'boolean'],
        ]);

        $noKo = trim((string) $data['no_ko']);
        $stepKode = strtoupper((string) $data['step_kode']);

        $ko = KantongOrder::query()
            ->with('pesananPenjualan:id,cabang_id')
            ->where('nomor_ko', $noKo)
            ->first();

        if (!$ko?->pesananPenjualan) {
            return $this->trackingError($request, 'No KO tidak ditemukan.');
        }

        $this->ensureCabangAccessible((int) $ko->pesananPenjualan->cabang_id);

        $user = Auth::user();
        if (!$user) {
            return $this->trackingError($request, 'User tidak valid.', 401);
        }
        $user->loadMissing('karyawan.jabatan');

        $allowedTrackingIds = $this->resolveAllowedTrackingIds((int) ($user->karyawan?->jabatan_id ?? 0));
        $allowed = TrackingReference::query()
            ->whereIn('id', $allowedTrackingIds)
            ->where('tipe', 'KO')
            ->whereRaw('UPPER(kode) = ?', [$stepKode])
            ->exists();

        if (!$allowed) {
            return $this->trackingError($request, 'Anda tidak berhak mencentang step KO ini.', 403);
        }

        $isChecked = (bool) ($data['is_checked'] ?? false);
        KoTrackingKoCheck::query()->updateOrCreate(
            [
                'pesanan_penjualan_id' => (int) $ko->pesananPenjualan->id,
                'step_kode' => $stepKode,
            ],
            [
                'is_checked' => $isChecked,
                'checked_at' => $isChecked ? now() : null,
                'checked_by_user_id' => $isChecked ? $user->id : null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Checklist KO berhasil diperbarui.',
            ]);
        }

        return redirect()->route('tracking-order', ['no_ko' => $noKo])->with('success', 'Checklist KO berhasil diperbarui.');
    }

    private function trackingError(Request $request, string $message, int $status = 422): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->withErrors(['tracking' => $message]);
    }

    private function resolveAllowedTrackingIds(int $jabatanId): array
    {
        if ($jabatanId <= 0) {
            return [];
        }

        return JabatanTrackingReference::query()
            ->where('jabatan_id', $jabatanId)
            ->where('can_update', true)
            ->pluck('tracking_reference_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}

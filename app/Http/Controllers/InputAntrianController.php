<?php

namespace App\Http\Controllers;

use App\Models\AntrianStudio;
use App\Models\BookingStudio;
use App\Models\Cabang;
use App\Models\KantongOrder;
use App\Models\KoTrackingItemCheck;
use App\Models\PesananPenjualanItem;
use App\Models\Studio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InputAntrianController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = (int) ($this->resolveCabangFilter($request) ?? 0);
        $this->ensureCabangAccessible($cabangId);

        $tanggalAntrian = $request->filled('tanggal_antrian')
            ? Carbon::parse((string) $request->input('tanggal_antrian'))->toDateString()
            : Carbon::now('Asia/Jakarta')->toDateString();

        return view('pages.pos.input-antrian', [
            'activeCabang' => $cabangId > 0 ? Cabang::query()->find($cabangId) : null,
            'cabangDefaultId' => $cabangId,
            'cabangTersedia' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'tanggalAntrian' => $tanggalAntrian,
            'studios' => $this->studioList($cabangId),
            'antrianByStudio' => $this->queueBoard($cabangId, $tanggalAntrian),
        ]);
    }

    public function cariKo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_ko' => ['required', 'string', 'max:30'],
        ]);

        $noKo = trim((string) $validated['no_ko']);
        $cabangId = (int) ($this->activeCabangId() ?? 0);

        $ko = KantongOrder::query()
            ->with([
                'pesananPenjualan.pelanggan',
                'pesananPenjualan.items.paket:id,nama',
                'pesananPenjualan.items.produk:id,nama',
            ])
            ->where('nomor_ko', $noKo)
            ->when($cabangId > 0, fn ($q) => $q->where('cabang_id', $cabangId))
            ->first();

        if (!$ko || !$ko->pesananPenjualan) {
            return response()->json([
                'exists' => false,
                'message' => 'No KO tidak ditemukan di cabang aktif.',
            ], 404);
        }

        $order = $ko->pesananPenjualan;
        $this->ensureCabangAccessible((int) $order->cabang_id);

        $namaPelanggan = $order->customer_name ?: ($order->pelanggan?->nama ?? '-');
        $pesananId = (int) $order->id;
        $hasCompletedQueue = AntrianStudio::query()
            ->where('cabang_id', (int) $order->cabang_id)
            ->where(function ($q) {
                $q->whereNotNull('end_at')
                    ->orWhereRaw('UPPER(status) = ?', ['DONE']);
            })
            ->whereHas('bookingStudio', function ($q) use ($pesananId) {
                $q->where('pesanan_penjualan_id', $pesananId);
            })
            ->exists();
        $remainingUncheckedTrackingItems = $this->remainingUncheckedTrackingItems($pesananId);
        $isQueueBlocked = $hasCompletedQueue && $remainingUncheckedTrackingItems === 0;

        $items = $order->items->map(function ($item) {
            return [
                'jenis_item' => $item->paket_id ? 'PAKET' : 'PRODUK',
                'nama_item' => $item->paket?->nama ?? $item->produk?->nama ?? '-',
                'qty' => (float) $item->qty,
            ];
        })->values();

        return response()->json([
            'exists' => true,
            'data' => [
                'no_ko' => $ko->nomor_ko,
                'nama_pelanggan' => $namaPelanggan,
                'pesanan_penjualan_id' => (int) $order->id,
                'cabang_id' => (int) $order->cabang_id,
                'has_completed_queue' => $hasCompletedQueue,
                'remaining_unchecked_tracking_items' => $remainingUncheckedTrackingItems,
                'is_queue_blocked' => $isQueueBlocked,
                'items' => $items,
            ],
        ]);
    }

    public function simpan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_ko' => ['required', 'string', 'max:30'],
            'tanggal_antrian' => ['required', 'date'],
            'studio_ids' => ['required', 'array', 'min:1'],
            'studio_ids.*' => ['required', 'integer', 'exists:studio,id'],
        ]);

        $tanggalAntrian = Carbon::parse((string) $validated['tanggal_antrian'])->toDateString();
        $todayJakarta = Carbon::now('Asia/Jakarta')->toDateString();
        if ($tanggalAntrian < $todayJakarta) {
            throw ValidationException::withMessages([
                'tanggal_antrian' => ['Tanggal antrian tidak boleh backdate (sebelum hari ini).'],
            ]);
        }

        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->ensureCabangAccessible($cabangId);

        $studioIds = collect($validated['studio_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $studioMap = Studio::query()
            ->where('cabang_id', $cabangId)
            ->where('status', true)
            ->whereIn('id', $studioIds)
            ->get(['id', 'nama'])
            ->keyBy('id');

        if (count($studioIds) !== $studioMap->count()) {
            throw ValidationException::withMessages([
                'studio_ids' => ['Ada studio yang tidak valid untuk cabang aktif.'],
            ]);
        }

        $noKo = trim((string) $validated['no_ko']);
        $ko = KantongOrder::query()
            ->with('pesananPenjualan')
            ->where('nomor_ko', $noKo)
            ->where('cabang_id', $cabangId)
            ->first();

        if (!$ko || !$ko->pesananPenjualan) {
            throw ValidationException::withMessages([
                'no_ko' => ['No KO tidak ditemukan di cabang aktif.'],
            ]);
        }

        $pesananId = (int) $ko->pesanan_penjualan_id;
        $hasCompletedQueue = AntrianStudio::query()
            ->where('cabang_id', $cabangId)
            ->where(function ($q) {
                $q->whereNotNull('end_at')
                    ->orWhereRaw('UPPER(status) = ?', ['DONE']);
            })
            ->whereHas('bookingStudio', function ($q) use ($pesananId) {
                $q->where('pesanan_penjualan_id', $pesananId);
            })
            ->exists();

        if ($hasCompletedQueue && $this->remainingUncheckedTrackingItems($pesananId) === 0) {
            throw ValidationException::withMessages([
                'no_ko' => ['KO sudah selesai difoto dan seluruh tracking paket sudah selesai, tidak bisa masuk antrian lagi.'],
            ]);
        }

        $duplicateStudios = collect();
        $createdCount = 0;

        DB::transaction(function () use (
            $studioIds,
            $studioMap,
            $tanggalAntrian,
            $cabangId,
            $pesananId,
            $ko,
            &$duplicateStudios,
            &$createdCount
        ) {
            foreach ($studioIds as $studioId) {
                // Cegah KO masuk ulang ke studio yang sama pada tanggal yang sama,
                // termasuk jika antrian sebelumnya sudah selesai.
                $isDuplicate = AntrianStudio::query()
                    ->where('cabang_id', $cabangId)
                    ->where('studio_id', $studioId)
                    ->whereHas('bookingStudio', function ($q) use ($pesananId, $tanggalAntrian) {
                        $q->where('pesanan_penjualan_id', $pesananId)
                            ->whereDate('tanggal_booking', $tanggalAntrian);
                    })
                    ->exists();

                if ($isDuplicate) {
                    $duplicateStudios->push($studioMap[$studioId]->nama ?? ('Studio #' . $studioId));
                    continue;
                }

                $booking = BookingStudio::query()->create([
                    'nomor_booking' => $this->generateNomorBooking(),
                    'pesanan_penjualan_id' => $pesananId,
                    'pelanggan_id' => $ko->pesananPenjualan?->pelanggan_id,
                    'cabang_id' => $cabangId,
                    'studio_id' => $studioId,
                    'tanggal_booking' => Carbon::parse($tanggalAntrian . ' 00:00:00'),
                    'status' => 'CHECKED_IN',
                ]);

                AntrianStudio::query()->create([
                    'booking_studio_id' => $booking->id,
                    'cabang_id' => $cabangId,
                    'studio_id' => $studioId,
                    'nomor_antrian' => $this->nextQueueNumber($cabangId, $studioId, $tanggalAntrian),
                    'status' => 'WAITING',
                ]);

                $createdCount++;
            }
        });

        return response()->json([
            'message' => $createdCount > 0
                ? 'Antrian berhasil ditambahkan.'
                : 'Tidak ada antrian baru yang ditambahkan.',
            'created_count' => $createdCount,
            'duplicates' => $duplicateStudios->values(),
            'board' => $this->queueBoard($cabangId, $tanggalAntrian),
        ]);
    }

    public function urutkan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_antrian' => ['required', 'date'],
            'queues' => ['required', 'array', 'min:1'],
            'queues.*.studio_id' => ['required', 'integer', 'exists:studio,id'],
            'queues.*.item_ids' => ['present', 'array'],
            'queues.*.item_ids.*' => ['required', 'integer', 'exists:antrian_studio,id'],
        ]);

        $tanggalAntrian = Carbon::parse((string) $validated['tanggal_antrian'])->toDateString();
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->ensureCabangAccessible($cabangId);

        $targetStudioIds = collect($validated['queues'])->pluck('studio_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $validStudios = Studio::query()
            ->where('cabang_id', $cabangId)
            ->where('status', true)
            ->whereIn('id', $targetStudioIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($targetStudioIds) !== count($validStudios)) {
            throw ValidationException::withMessages([
                'queues' => ['Ada studio tujuan yang tidak valid.'],
            ]);
        }

        $allItemIds = collect($validated['queues'])
            ->pluck('item_ids')
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($allItemIds->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada perubahan antrian.',
                'board' => $this->queueBoard($cabangId, $tanggalAntrian),
            ]);
        }

        $queueItems = AntrianStudio::query()
            ->with('bookingStudio:id,studio_id')
            ->whereIn('id', $allItemIds->all())
            ->where('cabang_id', $cabangId)
            ->whereHas('bookingStudio', function ($q) use ($tanggalAntrian) {
                $q->whereDate('tanggal_booking', $tanggalAntrian);
            })
            ->get()
            ->keyBy('id');

        if ($queueItems->count() !== $allItemIds->count()) {
            throw ValidationException::withMessages([
                'queues' => ['Sebagian item antrian tidak valid untuk tanggal/cabang aktif.'],
            ]);
        }

        DB::transaction(function () use ($validated, $queueItems) {
            foreach ($validated['queues'] as $queueColumn) {
                $studioId = (int) $queueColumn['studio_id'];
                $itemIds = collect($queueColumn['item_ids'] ?? [])->map(fn ($id) => (int) $id)->values();

                foreach ($itemIds as $index => $itemId) {
                    /** @var AntrianStudio $item */
                    $item = $queueItems->get($itemId);
                    if (!$item) {
                        continue;
                    }

                    $newNomor = $index + 1;
                    if ($this->isLockedQueue($item) && (int) $item->studio_id !== $studioId) {
                        throw ValidationException::withMessages([
                            'queues' => ['Antrian yang sudah berjalan/selesai tidak bisa dipindah.'],
                        ]);
                    }

                    $item->update([
                        'studio_id' => $studioId,
                        'nomor_antrian' => $newNomor,
                    ]);

                    if ($item->bookingStudio) {
                        $item->bookingStudio->update([
                            'studio_id' => $studioId,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'message' => 'Urutan antrian berhasil diperbarui.',
            'board' => $this->queueBoard($cabangId, $tanggalAntrian),
        ]);
    }

    public function hapus(Request $request, AntrianStudio $antrianStudio): JsonResponse
    {
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->ensureCabangAccessible($cabangId);

        if ((int) $antrianStudio->cabang_id !== $cabangId) {
            throw ValidationException::withMessages([
                'antrian' => ['Data antrian tidak berada di cabang aktif.'],
            ]);
        }
        if ($this->isLockedQueue($antrianStudio)) {
            throw ValidationException::withMessages([
                'antrian' => ['Antrian yang sudah berjalan/selesai tidak bisa dihapus.'],
            ]);
        }

        $tanggalAntrian = $request->filled('tanggal_antrian')
            ? Carbon::parse((string) $request->input('tanggal_antrian'))->toDateString()
            : Carbon::now('Asia/Jakarta')->toDateString();

        $antrianStudio->load('bookingStudio');
        $studioId = (int) $antrianStudio->studio_id;
        $booking = $antrianStudio->bookingStudio;

        DB::transaction(function () use ($antrianStudio, $booking, $cabangId, $studioId, $tanggalAntrian) {
            $antrianStudio->delete();

            if ($booking) {
                $booking->update([
                    'status' => 'CANCELLED',
                    'studio_id' => null,
                ]);
            }

            $remaining = AntrianStudio::query()
                ->where('cabang_id', $cabangId)
                ->where('studio_id', $studioId)
                ->whereHas('bookingStudio', function ($q) use ($tanggalAntrian) {
                    $q->whereDate('tanggal_booking', $tanggalAntrian);
                })
                ->orderBy('nomor_antrian')
                ->orderBy('id')
                ->get(['id']);

            foreach ($remaining as $index => $item) {
                AntrianStudio::query()
                    ->where('id', $item->id)
                    ->update(['nomor_antrian' => $index + 1]);
            }
        });

        return response()->json([
            'message' => 'Antrian berhasil dihapus.',
            'board' => $this->queueBoard($cabangId, $tanggalAntrian),
        ]);
    }

    private function studioList(int $cabangId)
    {
        return Studio::query()
            ->where('cabang_id', $cabangId)
            ->where('status', true)
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    private function queueBoard(int $cabangId, string $tanggalAntrian): array
    {
        $studios = $this->studioList($cabangId);

        $queues = AntrianStudio::query()
            ->with([
                'studio:id,nama',
                'bookingStudio:id,pesanan_penjualan_id,tanggal_booking',
                'bookingStudio.pesananPenjualan:id,pelanggan_id,customer_name',
                'bookingStudio.pesananPenjualan.pelanggan:id,nama',
                'bookingStudio.pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
                'tugas:id,antrian_studio_id,is_selesai',
            ])
            ->where('cabang_id', $cabangId)
            ->whereHas('bookingStudio', function ($q) use ($tanggalAntrian) {
                $q->whereDate('tanggal_booking', $tanggalAntrian);
            })
            ->orderBy('studio_id')
            ->orderBy('nomor_antrian')
            ->orderBy('id')
            ->get();

        $queueMap = $queues->groupBy('studio_id');
        $result = [];

        foreach ($studios as $studio) {
            $items = ($queueMap->get($studio->id) ?? collect())->map(function (AntrianStudio $antrian) {
                $order = $antrian->bookingStudio?->pesananPenjualan;
                $ko = $order?->kantongOrder?->nomor_ko ?? '-';
                $namaPelanggan = $order?->customer_name ?: ($order?->pelanggan?->nama ?? '-');
                $color = null;
                if ($antrian->end_at) {
                    $color = 'green';
                } elseif ($antrian->start_at) {
                    $color = 'red';
                }

                return [
                    'id' => (int) $antrian->id,
                    'studio_id' => (int) $antrian->studio_id,
                    'nomor_antrian' => (int) $antrian->nomor_antrian,
                    'status' => $antrian->status,
                    'no_ko' => $ko,
                    'nama_pelanggan' => $namaPelanggan,
                    'color' => $color,
                    'is_locked' => $this->isLockedQueue($antrian),
                ];
            })->values()->all();

            $result[] = [
                'studio_id' => (int) $studio->id,
                'studio_nama' => $studio->nama,
                'items' => $items,
            ];
        }

        return $result;
    }

    private function nextQueueNumber(int $cabangId, int $studioId, string $tanggalAntrian): int
    {
        $lastNumber = AntrianStudio::query()
            ->where('cabang_id', $cabangId)
            ->where('studio_id', $studioId)
            ->whereHas('bookingStudio', function ($q) use ($tanggalAntrian) {
                $q->whereDate('tanggal_booking', $tanggalAntrian);
            })
            ->max('nomor_antrian');

        return ((int) $lastNumber) + 1;
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
            $tail = (int) substr((string) $last, -4);
            $next = $tail + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function isLockedQueue(AntrianStudio $antrianStudio): bool
    {
        if ($antrianStudio->start_at || $antrianStudio->end_at) {
            return true;
        }

        $status = strtoupper((string) $antrianStudio->status);
        return in_array($status, ['IN_STUDIO', 'DONE'], true);
    }

    private function remainingUncheckedTrackingItems(int $pesananPenjualanId): int
    {
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

        if ($requiredItemKeys->isEmpty()) {
            return 0;
        }

        $checkedItemKeys = KoTrackingItemCheck::query()
            ->whereIn('pesanan_penjualan_item_id', $orderItems->pluck('id')->all())
            ->where('is_checked', true)
            ->get(['pesanan_penjualan_item_id', 'produk_id'])
            ->map(fn ($row) => (int) $row->pesanan_penjualan_item_id . ':' . (int) $row->produk_id)
            ->unique()
            ->values();

        $checkedItemCount = $requiredItemKeys->intersect($checkedItemKeys)->count();
        return max($requiredItemKeys->count() - $checkedItemCount, 0);
    }
}

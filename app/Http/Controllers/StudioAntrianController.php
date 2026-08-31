<?php

namespace App\Http\Controllers;

use App\Models\AntrianStudio;
use App\Models\AntrianStudioTugas;
use App\Models\Cabang;
use App\Models\JabatanTrackingReference;
use App\Models\KoTrackingItemCheck;
use App\Models\PesananPenjualanItem;
use App\Models\BookingStudio;
use App\Models\Studio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudioAntrianController extends Controller
{
    public function index(Request $request)
    {
        $cabangId = (int) ($this->resolveCabangFilter($request) ?? 0);
        $this->ensureCabangAccessible($cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);
        $studios = $this->studioList($cabangId);
        $selectedStudioId = $this->resolveSelectedStudioId($request, $studios, $cabangId);
        $this->ensureStudioAccessible($selectedStudioId, $cabangId);
        $this->persistSelectedStudio($cabangId, $selectedStudioId);

        return view('antrian-studio', [
            'activeCabang' => $cabangId > 0 ? Cabang::query()->find($cabangId) : null,
            'cabangDefaultId' => $cabangId,
            'cabangTersedia' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'tanggalAntrian' => $tanggalAntrian,
            'studios' => $studios,
            'selectedStudioId' => $selectedStudioId,
            'boardData' => $this->buildBoard($cabangId, $tanggalAntrian, $selectedStudioId),
        ]);
    }

    public function board(Request $request): JsonResponse
    {
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->ensureCabangAccessible($cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);
        $studioId = $request->filled('studio_id') ? (int) $request->input('studio_id') : 0;
        $this->ensureStudioAccessible($studioId, $cabangId);
        if ($studioId > 0) {
            $this->persistSelectedStudio($cabangId, $studioId);
        }

        return response()->json([
            'board' => $this->buildBoard($cabangId, $tanggalAntrian, $studioId),
        ]);
    }

    public function stream(Request $request): Response
    {
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->ensureCabangAccessible($cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);
        $studioId = $request->filled('studio_id') ? (int) $request->input('studio_id') : 0;
        $this->ensureStudioAccessible($studioId, $cabangId);
        if ($studioId > 0) {
            $this->persistSelectedStudio($cabangId, $studioId);
        }

        return response()->stream(function () use ($cabangId, $tanggalAntrian, $studioId) {
            @set_time_limit(0);
            $startedAt = time();
            $maxSeconds = 55; // biarkan browser reconnect berkala agar koneksi tetap sehat.
            $lastVersion = null;
            $lastSignature = null;

            echo "retry: 3000\n\n";
            @ob_flush();
            flush();

            while (!connection_aborted() && (time() - $startedAt) < $maxSeconds) {
                $currentVersion = $this->boardVersion($cabangId, $tanggalAntrian, $studioId);

                if ($lastVersion !== $currentVersion) {
                    $board = $this->buildBoard($cabangId, $tanggalAntrian, $studioId);
                    $signature = md5((string) json_encode($board));

                    if ($signature !== $lastSignature) {
                        echo "event: board\n";
                        echo 'data: ' . json_encode([
                            'board' => $board,
                            'signature' => $signature,
                        ]) . "\n\n";
                        $lastSignature = $signature;
                    }
                    $lastVersion = $currentVersion;
                } else {
                    echo ": ping\n\n";
                }

                @ob_flush();
                flush();
                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function customerDisplay(Request $request)
    {
        $cabangId = (int) ($this->resolveCabangFilter($request) ?? 0);
        $this->ensureCabangAccessible($cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        return view('antrian-studio-display-customer', [
            'activeCabang' => $cabangId > 0 ? Cabang::query()->find($cabangId) : null,
            'displayCabangId' => $cabangId,
            'tanggalAntrian' => $tanggalAntrian,
            'customerBoard' => $this->buildCustomerBoard($cabangId, $tanggalAntrian),
        ]);
    }

    public function audioAnnouncer(Request $request)
    {
        $cabangId = (int) ($this->resolveCabangFilter($request) ?? 0);
        $this->ensureCabangAccessible($cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        return view('antrian-studio-audio-announcer', [
            'activeCabang' => $cabangId > 0 ? Cabang::query()->find($cabangId) : null,
            'displayCabangId' => $cabangId,
            'tanggalAntrian' => $tanggalAntrian,
            'customerBoard' => $this->buildCustomerBoard($cabangId, $tanggalAntrian),
        ]);
    }

    public function customerBoard(Request $request): JsonResponse
    {
        $cabangId = (int) ($this->resolveCabangFilter($request) ?? 0);
        $this->ensureCabangAccessible($cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        return response()->json([
            'board' => $this->buildCustomerBoard($cabangId, $tanggalAntrian),
        ]);
    }

    public function customerStream(Request $request): Response
    {
        $cabangId = (int) ($this->resolveCabangFilter($request) ?? 0);
        $this->ensureCabangAccessible($cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        return response()->stream(function () use ($cabangId, $tanggalAntrian) {
            @set_time_limit(0);
            $startedAt = time();
            $maxSeconds = 55;
            $lastVersion = null;
            $lastSignature = null;

            echo "retry: 3000\n\n";
            @ob_flush();
            flush();

            while (!connection_aborted() && (time() - $startedAt) < $maxSeconds) {
                $currentVersion = $this->customerBoardVersion($cabangId, $tanggalAntrian);

                if ($lastVersion !== $currentVersion) {
                    $board = $this->buildCustomerBoard($cabangId, $tanggalAntrian);
                    $signature = md5((string) json_encode($board));

                    if ($signature !== $lastSignature) {
                        echo "event: customer-board\n";
                        echo 'data: ' . json_encode([
                            'board' => $board,
                            'signature' => $signature,
                        ]) . "\n\n";
                        $lastSignature = $signature;
                    }
                    $lastVersion = $currentVersion;
                } else {
                    echo ": ping\n\n";
                }

                @ob_flush();
                flush();
                sleep(3);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function audioBoard(Request $request): JsonResponse
    {
        return $this->customerBoard($request);
    }

    public function audioStream(Request $request): Response
    {
        return $this->customerStream($request);
    }

    public function panggil(Request $request, AntrianStudio $antrianStudio): JsonResponse
    {
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->validateAntrianAccess($antrianStudio, $cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        $antrianStudio->load([
            'studio:id,nama',
            'bookingStudio.pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
            'bookingStudio.pesananPenjualan.pelanggan:id,nama',
            'bookingStudio.pesananPenjualan:id,customer_name,pelanggan_id',
        ]);

        $antrianStudio->update([
            'status' => 'CALLED',
            'waktu_panggil' => now(),
            'called_at' => now(),
            'called_by_user_id' => Auth::id(),
        ]);

        $order = $antrianStudio->bookingStudio?->pesananPenjualan;
        $ko = $order?->kantongOrder?->nomor_ko ?? '-';
        $nama = $order?->customer_name ?: ($order?->pelanggan?->nama ?? '-');
        $studioNama = $antrianStudio->studio?->nama ?? 'studio';

        return response()->json([
            'message' => 'Customer berhasil dipanggil.',
            'announce_text' => sprintf(
                'Mohon perhatian, nomor Order %s atas nama %s, silahkan ke %s.',
                $this->formatKoForSpeech($ko),
                $nama,
                $studioNama
            ),
            'board' => $this->buildBoard($cabangId, $tanggalAntrian, (int) $antrianStudio->studio_id),
        ]);
    }

    public function start(Request $request, AntrianStudio $antrianStudio): JsonResponse
    {
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->validateAntrianAccess($antrianStudio, $cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        DB::transaction(function () use ($antrianStudio) {
            if (!$antrianStudio->start_at) {
                $antrianStudio->start_at = now();
            }
            $antrianStudio->status = 'IN_STUDIO';
            $antrianStudio->started_by_user_id = Auth::id();
            $antrianStudio->photographer_user_id = Auth::id();
            $antrianStudio->save();

            $this->initializeTugas($antrianStudio);
        });

        return response()->json([
            'message' => 'Sesi foto dimulai.',
            'board' => $this->buildBoard($cabangId, $tanggalAntrian, (int) $antrianStudio->studio_id),
        ]);
    }

    public function end(Request $request, AntrianStudio $antrianStudio): JsonResponse
    {
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->validateAntrianAccess($antrianStudio, $cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        $user = Auth::user();
        $user?->loadMissing('karyawan.jabatan');
        $allowedTrackingIds = $this->resolveAllowedTrackingIds($user);

        $antrianStudio->load([
            'bookingStudio.pesananPenjualan.items.paket.items.produk.kategoriProduk',
        ]);

        $order = $antrianStudio->bookingStudio?->pesananPenjualan;
        if ($order) {
            $orderItemIds = $order->items
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values();

            $checkRowsByKey = collect();
            if ($orderItemIds->isNotEmpty()) {
                $checkRowsByKey = KoTrackingItemCheck::query()
                    ->whereIn('pesanan_penjualan_item_id', $orderItemIds->all())
                    ->get()
                    ->keyBy(fn ($row) => $row->pesanan_penjualan_item_id . ':' . $row->produk_id);
            }

            $taskSummary = $this->buildTaskSummary($order, $allowedTrackingIds, $checkRowsByKey);
            if (($taskSummary['total'] ?? 0) > 0 && (int) ($taskSummary['done'] ?? 0) === 0) {
                return response()->json([
                    'message' => 'Belum ada tracking tugas Anda yang dicentang. Centang minimal satu item dulu sebelum End.',
                ], 422);
            }
        }

        $antrianStudio->update([
            'status' => 'DONE',
            'end_at' => now(),
            'ended_by_user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Sesi foto selesai.',
            'board' => $this->buildBoard($cabangId, $tanggalAntrian, (int) $antrianStudio->studio_id),
        ]);
    }

    public function toggleTugas(Request $request, AntrianStudioTugas $antrianStudioTugas): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['User tidak valid.'],
            ]);
        }
        $user->loadMissing('karyawan.jabatan');

        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $antrianStudioTugas->load([
            'antrianStudio',
            'produk.kategoriProduk.trackingReference',
        ]);
        $antrian = $antrianStudioTugas->antrianStudio;
        if (!$antrian) {
            throw ValidationException::withMessages([
                'tugas' => ['Data tugas tidak valid.'],
            ]);
        }

        $allowedTrackingIds = $this->resolveAllowedTrackingIds($user);
        $tugasTrackingId = (int) ($antrianStudioTugas->produk?->kategoriProduk?->tracking_reference_id ?? 0);
        if ($antrianStudioTugas->produk_id && ($tugasTrackingId <= 0 || !in_array($tugasTrackingId, $allowedTrackingIds, true))) {
            throw ValidationException::withMessages([
                'tugas' => ['Anda tidak berhak update tugas ini karena beda tracking.'],
            ]);
        }

        $this->validateAntrianAccess($antrian, $cabangId);
        $tanggalAntrian = $this->resolveTanggalAntrian($request);

        $next = !$antrianStudioTugas->is_selesai;
        $antrianStudioTugas->update([
            'is_selesai' => $next,
            'selesai_at' => $next ? now() : null,
            'selesai_by_user_id' => $next ? Auth::id() : null,
        ]);

        return response()->json([
            'message' => $next ? 'Tugas ditandai selesai.' : 'Tanda selesai tugas dibatalkan.',
            'board' => $this->buildBoard($cabangId, $tanggalAntrian, (int) $antrian->studio_id),
        ]);
    }

    public function trackingDetail(Request $request, AntrianStudio $antrianStudio): JsonResponse
    {
        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->validateAntrianAccess($antrianStudio, $cabangId);

        $antrianStudio->load([
            'studio:id,nama',
            'bookingStudio.pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
            'bookingStudio.pesananPenjualan.pelanggan:id,nama',
            'bookingStudio.pesananPenjualan.items.paket.items.produk.kategoriProduk.trackingReference',
        ]);

        $order = $antrianStudio->bookingStudio?->pesananPenjualan;
        if (!$order) {
            throw ValidationException::withMessages([
                'tracking' => ['Order pada antrian studio ini tidak valid.'],
            ]);
        }

        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['User tidak valid.'],
            ]);
        }
        $user->loadMissing('karyawan.jabatan');
        $allowedTrackingIds = $this->resolveAllowedTrackingIds($user);

        $orderItems = $order->items->sortBy('id')->values();
        $checkRows = KoTrackingItemCheck::query()
            ->with('checkedBy:id,name')
            ->whereIn('pesanan_penjualan_item_id', $orderItems->pluck('id')->all())
            ->get()
            ->keyBy(fn ($row) => $row->pesanan_penjualan_item_id . ':' . $row->produk_id);

        $canChecklist = (bool) $antrianStudio->start_at && !$antrianStudio->end_at;
        $groups = $orderItems->map(function ($orderItem) use ($checkRows, $allowedTrackingIds, $canChecklist) {
            $paket = $orderItem->paket;
            $paketItems = collect($paket?->items ?? [])
                ->sortBy('id')
                ->map(function ($paketItem) use ($checkRows, $orderItem, $allowedTrackingIds, $canChecklist) {
                    $kategori = $paketItem->produk?->kategoriProduk;
                    $trackingId = (int) ($kategori?->tracking_reference_id ?? 0);
                    $key = $orderItem->id . ':' . $paketItem->produk_id;
                    $checkRow = $checkRows->get($key);

                    return [
                        'produk_id' => (int) ($paketItem->produk_id ?? 0),
                        'nama' => (string) ($paketItem->produk?->nama ?? '-'),
                        'kategori' => (string) ($kategori?->nama ?? '-'),
                        'tracking_nama' => (string) ($kategori?->trackingReference?->nama ?? '-'),
                        'qty' => (float) ($paketItem->qty ?? 0),
                        'total_qty' => (float) ($paketItem->qty ?? 0) * (float) ($orderItem->qty ?? 0),
                        'is_checked' => (bool) ($checkRow?->is_checked ?? false),
                        'checked_by' => $checkRow?->checkedBy?->name,
                        'checked_at' => $checkRow?->checked_at?->format('d-m-Y H:i'),
                        'can_update' => $canChecklist && $trackingId > 0 && in_array($trackingId, $allowedTrackingIds, true),
                    ];
                })
                ->values();

            return [
                'order_item_id' => (int) $orderItem->id,
                'paket_nama' => (string) ($paket?->nama ?? ($orderItem->produk?->nama ?? 'Item')),
                'order_qty' => (float) ($orderItem->qty ?? 0),
                'paket_items' => $paketItems,
            ];
        })->values();

        return response()->json([
            'queue' => [
                'antrian_studio_id' => (int) $antrianStudio->id,
                'no_ko' => (string) ($order->kantongOrder?->nomor_ko ?? '-'),
                'nama_pelanggan' => (string) ($order->customer_name ?: ($order->pelanggan?->nama ?? '-')),
                'studio_nama' => (string) ($antrianStudio->studio?->nama ?? '-'),
                'start_at' => optional($antrianStudio->start_at)->format('Y-m-d H:i:s'),
                'end_at' => optional($antrianStudio->end_at)->format('Y-m-d H:i:s'),
            ],
            'groups' => $groups,
        ]);
    }

    public function updateTrackingItemCheck(Request $request, AntrianStudio $antrianStudio): JsonResponse
    {
        $data = $request->validate([
            'pesanan_penjualan_item_id' => ['required', 'integer', 'exists:pesanan_penjualan_item,id'],
            'produk_id' => ['required', 'integer', 'exists:produk,id'],
            'is_checked' => ['nullable', 'boolean'],
        ]);

        $cabangId = (int) ($this->activeCabangId() ?? 0);
        $this->validateAntrianAccess($antrianStudio, $cabangId);
        if (!$antrianStudio->start_at) {
            throw ValidationException::withMessages([
                'tracking' => ['Checklist tracking belum bisa diubah karena sesi belum Start.'],
            ]);
        }
        if ($antrianStudio->end_at) {
            throw ValidationException::withMessages([
                'tracking' => ['Checklist tracking tidak bisa diubah karena sesi sudah End.'],
            ]);
        }

        $antrianStudio->load([
            'bookingStudio.pesananPenjualan.items.paket.items.produk.kategoriProduk.trackingReference',
        ]);

        $order = $antrianStudio->bookingStudio?->pesananPenjualan;
        if (!$order) {
            throw ValidationException::withMessages([
                'tracking' => ['Order pada antrian studio ini tidak valid.'],
            ]);
        }

        $orderItem = $order->items->firstWhere('id', (int) $data['pesanan_penjualan_item_id']);
        if (!$orderItem || !$orderItem->paket) {
            throw ValidationException::withMessages([
                'tracking' => ['Item order tidak valid untuk KO ini.'],
            ]);
        }

        $paketItem = collect($orderItem->paket->items)
            ->first(fn ($item) => (int) $item->produk_id === (int) $data['produk_id']);
        if (!$paketItem) {
            throw ValidationException::withMessages([
                'tracking' => ['Produk tidak ada di paket item order ini.'],
            ]);
        }

        $user = Auth::user();
        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => ['User tidak valid.'],
            ]);
        }
        $user->loadMissing('karyawan.jabatan');
        $allowedTrackingIds = $this->resolveAllowedTrackingIds($user);

        $produkTrackingId = (int) ($paketItem->produk?->kategoriProduk?->tracking_reference_id ?? 0);
        if ($produkTrackingId <= 0 || !in_array($produkTrackingId, $allowedTrackingIds, true)) {
            throw ValidationException::withMessages([
                'tracking' => ['Anda tidak berhak mencentang item ini.'],
            ]);
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

        return response()->json([
            'message' => 'Checklist item berhasil diperbarui.',
        ]);
    }

    private function resolveTanggalAntrian(Request $request): string
    {
        return $request->filled('tanggal_antrian')
            ? Carbon::parse((string) $request->input('tanggal_antrian'))->toDateString()
            : Carbon::now('Asia/Jakarta')->toDateString();
    }

    private function validateAntrianAccess(AntrianStudio $antrianStudio, int $activeCabangId): void
    {
        if ((int) $antrianStudio->cabang_id !== $activeCabangId) {
            throw ValidationException::withMessages([
                'antrian' => ['Data antrian tidak berada di cabang aktif.'],
            ]);
        }
        $this->ensureCabangAccessible((int) $antrianStudio->cabang_id);
    }

    private function buildBoard(int $cabangId, string $tanggalAntrian, ?int $studioId = null): array
    {
        $user = Auth::user();
        $user?->loadMissing('karyawan.jabatan');
        $allowedTrackingIds = $this->resolveAllowedTrackingIds($user);

        $studios = $this->studioList($cabangId)
            ->when($studioId > 0, fn ($c) => $c->where('id', $studioId))
            ->values();

        $antrian = AntrianStudio::query()
            ->with([
                'studio:id,nama',
                'photographer:id,name',
                'bookingStudio:id,pesanan_penjualan_id,tanggal_booking',
                'bookingStudio.pesananPenjualan:id,pelanggan_id,customer_name',
                'bookingStudio.pesananPenjualan.pelanggan:id,nama',
                'bookingStudio.pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
                'bookingStudio.pesananPenjualan.items:id,pesanan_penjualan_id,produk_id,paket_id,qty',
                'bookingStudio.pesananPenjualan.items.paket:id,nama,kode',
                'bookingStudio.pesananPenjualan.items.paket.items:id,paket_id,produk_id,qty',
                'bookingStudio.pesananPenjualan.items.paket.items.produk:id,nama,kategori_produk_kode',
                'bookingStudio.pesananPenjualan.items.paket.items.produk.kategoriProduk:id,kode,tracking_reference_id',
            ])
            ->where('cabang_id', $cabangId)
            ->when($studioId > 0, fn ($q) => $q->where('studio_id', $studioId))
            ->whereHas('bookingStudio', function ($q) use ($tanggalAntrian) {
                $q->whereDate('tanggal_booking', $tanggalAntrian);
            })
            ->orderBy('studio_id')
            ->orderBy('nomor_antrian')
            ->orderBy('id')
            ->get();

        $orderItemIds = $antrian
            ->flatMap(fn (AntrianStudio $item) => $item->bookingStudio?->pesananPenjualan?->items?->pluck('id') ?? collect())
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $checkRowsByKey = collect();
        if ($orderItemIds->isNotEmpty()) {
            $checkRowsByKey = KoTrackingItemCheck::query()
                ->whereIn('pesanan_penjualan_item_id', $orderItemIds->all())
                ->get()
                ->keyBy(fn ($row) => $row->pesanan_penjualan_item_id . ':' . $row->produk_id);
        }

        $grouped = $antrian->groupBy('studio_id');
        $board = [];

        foreach ($studios as $studio) {
            $items = ($grouped->get($studio->id) ?? collect())->map(function (AntrianStudio $item) use ($allowedTrackingIds, $checkRowsByKey) {
                $order = $item->bookingStudio?->pesananPenjualan;
                $ko = $order?->kantongOrder?->nomor_ko ?? '-';
                $nama = $order?->customer_name ?: ($order?->pelanggan?->nama ?? '-');
                $taskSummary = $this->buildTaskSummary($order, $allowedTrackingIds, $checkRowsByKey);
                $pendingTugas = (int) ($taskSummary['pending'] ?? 0);

                $color = null;
                if ($item->end_at) {
                    $color = 'green';
                } elseif ($item->start_at && $pendingTugas > 0) {
                    $color = 'red';
                }

                return [
                    'id' => (int) $item->id,
                    'nomor_antrian' => (int) $item->nomor_antrian,
                    'status' => $item->status,
                    'no_ko' => $ko,
                    'nama_pelanggan' => $nama,
                    'studio_nama' => $item->studio?->nama ?? '-',
                    'start_at' => optional($item->start_at)->format('Y-m-d H:i:s'),
                    'end_at' => optional($item->end_at)->format('Y-m-d H:i:s'),
                    'duration_seconds' => $item->start_at
                        ? (int) ($item->end_at ? $item->start_at->diffInSeconds($item->end_at) : $item->start_at->diffInSeconds(now()))
                        : 0,
                    'photographer_name' => $item->photographer?->name,
                    'color' => $color,
                    'task_summary' => $taskSummary,
                ];
            })->values()->all();

            $board[] = [
                'studio_id' => (int) $studio->id,
                'studio_nama' => $studio->nama,
                'items' => $items,
            ];
        }

        return $board;
    }

    private function boardVersion(int $cabangId, string $tanggalAntrian, ?int $studioId = null): string
    {
        $bookingIds = BookingStudio::query()
            ->where('cabang_id', $cabangId)
            ->whereDate('tanggal_booking', $tanggalAntrian)
            ->pluck('id');

        if ($bookingIds->isEmpty()) {
            return 'empty';
        }

        $antrianBase = AntrianStudio::query()
            ->where('cabang_id', $cabangId)
            ->whereIn('booking_studio_id', $bookingIds->all())
            ->when($studioId > 0, fn ($q) => $q->where('studio_id', $studioId));

        $antrianAgg = (clone $antrianBase)
            ->selectRaw('COUNT(*) as c, MAX(updated_at) as m')
            ->first();

        $antrianIds = (clone $antrianBase)->pluck('id');
        if ($antrianIds->isEmpty()) {
            return 'empty';
        }

        $tugasAgg = AntrianStudioTugas::query()
            ->whereIn('antrian_studio_id', $antrianIds->all())
            ->selectRaw('COUNT(*) as c, MAX(updated_at) as m')
            ->first();

        $aCount = (int) ($antrianAgg->c ?? 0);
        $aMax = (string) ($antrianAgg->m ?? '');
        $tCount = (int) ($tugasAgg->c ?? 0); // legacy antrian studio tasks
        $tMax = (string) ($tugasAgg->m ?? '');

        $orderIds = BookingStudio::query()
            ->whereIn('id', $bookingIds->all())
            ->pluck('pesanan_penjualan_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $orderItemIds = collect();
        if ($orderIds->isNotEmpty()) {
            $orderItemIds = PesananPenjualanItem::query()
                ->whereIn('pesanan_penjualan_id', $orderIds->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();
        }

        $checkAgg = (object) ['c' => 0, 'm' => null];
        if ($orderItemIds->isNotEmpty()) {
            $checkAgg = KoTrackingItemCheck::query()
                ->whereIn('pesanan_penjualan_item_id', $orderItemIds->all())
                ->selectRaw('COUNT(*) as c, MAX(updated_at) as m')
                ->first();
        }

        $cCount = (int) ($checkAgg->c ?? 0);
        $cMax = (string) ($checkAgg->m ?? '');

        return md5($aCount . '|' . $aMax . '|' . $tCount . '|' . $tMax . '|' . $cCount . '|' . $cMax);
    }

    private function buildCustomerBoard(int $cabangId, string $tanggalAntrian): array
    {
        $antrian = AntrianStudio::query()
            ->with([
                'studio:id,nama',
                'bookingStudio:id,pesanan_penjualan_id,tanggal_booking',
                'bookingStudio.pesananPenjualan:id,pelanggan_id,customer_name',
                'bookingStudio.pesananPenjualan.pelanggan:id,nama',
                'bookingStudio.pesananPenjualan.kantongOrder:id,pesanan_penjualan_id,nomor_ko',
            ])
            ->where('cabang_id', $cabangId)
            ->whereNull('end_at')
            ->whereHas('bookingStudio', function ($q) use ($tanggalAntrian) {
                $q->whereDate('tanggal_booking', $tanggalAntrian);
            })
            ->orderByDesc('called_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (AntrianStudio $item) {
                $order = $item->bookingStudio?->pesananPenjualan;
                $calledAt = $item->called_at;
                $startAt = $item->start_at;
                return [
                    'id' => (int) $item->id,
                    'studio_id' => (int) ($item->studio?->id ?? 0),
                    'nomor_antrian' => (int) $item->nomor_antrian,
                    'no_ko' => (string) ($order?->kantongOrder?->nomor_ko ?? '-'),
                    'nama_pelanggan' => (string) ($order?->customer_name ?: ($order?->pelanggan?->nama ?? '-')),
                    'studio_nama' => (string) ($item->studio?->nama ?? '-'),
                    'called_at' => $calledAt ? $calledAt->format('Y-m-d H:i:s') : null,
                    'called_time' => $calledAt ? $calledAt->copy()->setTimezone('Asia/Jakarta')->format('H:i') : '',
                    'start_at' => $startAt ? $startAt->format('Y-m-d H:i:s') : null,
                    'start_time' => $startAt ? $startAt->copy()->setTimezone('Asia/Jakarta')->format('H:i') : '',
                ];
            })
            ->values();

        $activeCall = $antrian
            ->filter(fn ($item) => !empty($item['called_at']))
            ->sortByDesc('called_at')
            ->first();

        $perStudioActive = $antrian
            ->filter(fn ($item) => !empty($item['start_at']))
            ->sortByDesc('start_at')
            ->groupBy('studio_id')
            ->map(fn ($group) => $group->first());

        $studioStatuses = $this->studioList($cabangId)
            ->map(function ($studio) use ($perStudioActive) {
                $active = $perStudioActive->get((int) $studio->id);
                return [
                    'studio_id' => (int) $studio->id,
                    'studio_nama' => (string) $studio->nama,
                    'is_active' => (bool) $active,
                    'status_label' => $active ? 'Sedang Foto' : 'Kosong',
                    'no_ko' => (string) ($active['no_ko'] ?? '-'),
                    'nama_pelanggan' => (string) ($active['nama_pelanggan'] ?? '-'),
                    'start_time' => (string) ($active['start_time'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return [
            'active_call' => $activeCall,
            'queue' => [],
            'recent' => $antrian->all(),
            'total_active' => $antrian->count(),
            'studio_statuses' => $studioStatuses,
        ];
    }

    private function customerBoardVersion(int $cabangId, string $tanggalAntrian): string
    {
        $bookingIds = BookingStudio::query()
            ->where('cabang_id', $cabangId)
            ->whereDate('tanggal_booking', $tanggalAntrian)
            ->pluck('id');

        if ($bookingIds->isEmpty()) {
            return 'empty';
        }

        $agg = AntrianStudio::query()
            ->where('cabang_id', $cabangId)
            ->whereIn('booking_studio_id', $bookingIds->all())
            ->whereNotNull('called_at')
            ->whereNull('end_at')
            ->selectRaw('COUNT(*) as c, MAX(updated_at) as m, MAX(called_at) as called_m')
            ->first();

        return md5(
            ((int) ($agg->c ?? 0)) . '|' .
            ((string) ($agg->m ?? '')) . '|' .
            ((string) ($agg->called_m ?? ''))
        );
    }

    private function buildTaskSummary($order, array $allowedTrackingIds, $checkRowsByKey): array
    {
        $totalTugas = 0;
        $doneTugas = 0;

        foreach (($order?->items ?? collect()) as $orderItem) {
            $paketItems = collect($orderItem->paket?->items ?? []);
            foreach ($paketItems as $paketItem) {
                $trackingId = (int) ($paketItem->produk?->kategoriProduk?->tracking_reference_id ?? 0);
                if ($trackingId <= 0 || !in_array($trackingId, $allowedTrackingIds, true)) {
                    continue;
                }

                $totalTugas++;
                $checkKey = $orderItem->id . ':' . $paketItem->produk_id;
                if ((bool) ($checkRowsByKey->get($checkKey)?->is_checked ?? false)) {
                    $doneTugas++;
                }
            }
        }

        return [
            'total' => $totalTugas,
            'done' => $doneTugas,
            'pending' => max(0, $totalTugas - $doneTugas),
        ];
    }

    private function studioList(int $cabangId)
    {
        return Studio::query()
            ->where('cabang_id', $cabangId)
            ->where('status', true)
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    private function resolveSelectedStudioId(Request $request, $studios, int $cabangId): int
    {
        $studioIds = $studios
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($studioIds)) {
            return 0;
        }

        if ($request->filled('studio_id')) {
            $requestStudioId = (int) $request->input('studio_id');
            if (in_array($requestStudioId, $studioIds, true)) {
                return $requestStudioId;
            }
        }

        $sessionStudioId = (int) session($this->selectedStudioSessionKey($cabangId), 0);
        if ($sessionStudioId > 0 && in_array($sessionStudioId, $studioIds, true)) {
            return $sessionStudioId;
        }

        return (int) ($studioIds[0] ?? 0);
    }

    private function persistSelectedStudio(int $cabangId, int $studioId): void
    {
        if ($cabangId <= 0 || $studioId <= 0) {
            return;
        }

        session([$this->selectedStudioSessionKey($cabangId) => $studioId]);
    }

    private function selectedStudioSessionKey(int $cabangId): string
    {
        return 'antrian_studio.active_studio_id.' . $cabangId;
    }

    private function ensureStudioAccessible(?int $studioId, int $cabangId): void
    {
        if (!$studioId) {
            return;
        }

        $exists = Studio::query()
            ->where('id', $studioId)
            ->where('cabang_id', $cabangId)
            ->where('status', true)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'studio_id' => ['Studio tidak valid untuk cabang aktif.'],
            ]);
        }
    }

    private function initializeTugas(AntrianStudio $antrianStudio): void
    {
        if ($antrianStudio->tugas()->exists()) {
            return;
        }

        $booking = $antrianStudio->bookingStudio()
            ->with([
                'pesananPenjualan.items.paket:id,nama',
                'pesananPenjualan.items.paket.items:id,paket_id,produk_id,qty',
                'pesananPenjualan.items.paket.items.produk:id,nama,kategori_produk_kode',
                'pesananPenjualan.items.paket.items.produk.kategoriProduk:id,tipe',
            ])
            ->first();

        $orderItems = $booking?->pesananPenjualan?->items ?? collect();
        $created = 0;

        foreach ($orderItems as $orderItem) {
            if (!$orderItem->paket) {
                continue;
            }

            $jasaBomItems = collect($orderItem->paket->items)
                ->filter(function ($bomItem) {
                    $tipe = strtoupper((string) ($bomItem->produk?->kategoriProduk?->tipe ?? ''));
                    return $tipe === 'JASA';
                });

            if ($jasaBomItems->isEmpty()) {
                AntrianStudioTugas::query()->create([
                    'antrian_studio_id' => $antrianStudio->id,
                    'pesanan_penjualan_item_id' => $orderItem->id,
                    'produk_id' => null,
                    'nama_tugas' => 'Sesi Foto Paket ' . ($orderItem->paket->nama ?? '-'),
                    'qty' => (float) $orderItem->qty,
                    'is_selesai' => false,
                ]);
                $created++;
                continue;
            }

            foreach ($jasaBomItems as $bomItem) {
                $namaProduk = $bomItem->produk?->nama ?? 'Jasa';
                AntrianStudioTugas::query()->create([
                    'antrian_studio_id' => $antrianStudio->id,
                    'pesanan_penjualan_item_id' => $orderItem->id,
                    'produk_id' => $bomItem->produk_id,
                    'nama_tugas' => $namaProduk . ' - Paket ' . ($orderItem->paket->nama ?? '-'),
                    'qty' => (float) $bomItem->qty * (float) $orderItem->qty,
                    'is_selesai' => false,
                ]);
                $created++;
            }
        }

        if ($created === 0) {
            $fallbackItem = PesananPenjualanItem::query()
                ->where('pesanan_penjualan_id', $booking?->pesanan_penjualan_id)
                ->orderBy('id')
                ->first();

            AntrianStudioTugas::query()->create([
                'antrian_studio_id' => $antrianStudio->id,
                'pesanan_penjualan_item_id' => $fallbackItem?->id,
                'produk_id' => null,
                'nama_tugas' => 'Sesi Foto Studio',
                'qty' => 1,
                'is_selesai' => false,
            ]);
        }
    }

    private function resolveAllowedTrackingIds($user): array
    {
        $jabatanId = (int) ($user?->karyawan?->jabatan_id ?? 0);
        if ($jabatanId <= 0) {
            return [];
        }

        return JabatanTrackingReference::query()
            ->where('jabatan_id', $jabatanId)
            ->where('can_update', true)
            ->pluck('tracking_reference_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function formatKoForSpeech(?string $rawKo): string
    {
        $value = trim((string) $rawKo);
        if ($value === '' || $value === '-') {
            return 'tidak diketahui';
        }

        $numberPart = preg_replace('/^KO[\s\-:]*/i', '', $value);
        if (!is_string($numberPart) || trim($numberPart) === '') {
            $numberPart = $value;
        }

        return $this->spellTokenByToken($numberPart);
    }

    private function spellTokenByToken(string $value): string
    {
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = [];
        foreach ($chars as $ch) {
            if (ctype_digit($ch)) {
                $parts[] = $this->digitWord($ch);
                continue;
            }
            if (ctype_alpha($ch)) {
                $parts[] = strtoupper($ch);
                continue;
            }
            if ($ch === '-') {
                $parts[] = 'strip';
                continue;
            }
            if ($ch === '/') {
                $parts[] = 'garis miring';
                continue;
            }
            if ($ch === '.') {
                $parts[] = 'titik';
            }
        }

        $result = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? '');
        return $result !== '' ? $result : 'tidak diketahui';
    }

    private function digitWord(string $digit): string
    {
        return match ($digit) {
            '0' => 'nol',
            '1' => 'satu',
            '2' => 'dua',
            '3' => 'tiga',
            '4' => 'empat',
            '5' => 'lima',
            '6' => 'enam',
            '7' => 'tujuh',
            '8' => 'delapan',
            '9' => 'sembilan',
            default => $digit,
        };
    }
}

<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\KartuStok;
use App\Models\Produk;
use App\Models\StokCabang;
use App\Models\StokPenyesuaian;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokPenyesuaianController extends Controller
{
    public function index(Request $request)
    {
        $cabangList = $this->accessibleCabangQuery()->get(['id', 'kode', 'nama']);
        $cabangId = (int) ($request->input('cabang_id') ?: 0);
        if ($cabangId > 0) {
            $this->ensureCabangAccessible($cabangId);
        }

        $riwayat = StokPenyesuaian::query()
            ->with([
                'cabang:id,kode,nama',
                'dibuatOleh:id,name',
            ])
            ->when($cabangId > 0, fn ($q) => $q->where('cabang_id', $cabangId))
            ->when($request->filled('tanggal_dari'), fn ($q) => $q->whereDate('tanggal_penyesuaian', '>=', $request->input('tanggal_dari')))
            ->when($request->filled('tanggal_sampai'), fn ($q) => $q->whereDate('tanggal_penyesuaian', '<=', $request->input('tanggal_sampai')))
            ->when($request->filled('keyword'), function ($q) use ($request) {
                $keyword = trim((string) $request->input('keyword'));
                $q->where(function ($builder) use ($keyword) {
                    $builder->where('id', $keyword)
                        ->orWhere('catatan', 'like', '%' . $keyword . '%')
                        ->orWhereHas('dibuatOleh', fn ($u) => $u->where('name', 'like', '%' . $keyword . '%'));
                });
            })
            ->orderByDesc('tanggal_penyesuaian')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.master.persediaan.stok.penyesuaian', [
            'cabangList' => $cabangList,
            'selectedCabangId' => $cabangId,
            'riwayat' => $riwayat,
        ]);
    }

    public function create(Request $request)
    {
        $cabangList = $this->accessibleCabangQuery()->get(['id', 'kode', 'nama']);
        $cabangId = (int) ($this->resolveCabangFilter($request) ?: ($cabangList->first()->id ?? 0));
        $tanggal = $request->input('tanggal') ?: now()->toDateString();
        $selectedRows = [];

        $oldTargetQty = old('target_qty', []);
        if (is_array($oldTargetQty) && !empty($oldTargetQty)) {
            $produkIds = collect(array_keys($oldTargetQty))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($produkIds->isNotEmpty()) {
                $produkMap = Produk::query()
                    ->with(['kategoriProduk:id,kode,nama', 'satuan:id,nama'])
                    ->whereIn('id', $produkIds)
                    ->where('track_stok', true)
                    ->whereHas('kategoriProduk', fn ($q) => $q->where('tipe', 'BARANG'))
                    ->get(['id', 'kode', 'nama', 'kategori_produk_kode', 'satuan_id'])
                    ->keyBy('id');

                foreach ($produkIds as $produkId) {
                    $produk = $produkMap->get($produkId);
                    if (!$produk) {
                        continue;
                    }

                    $selectedRows[] = [
                        'id' => (int) $produk->id,
                        'kode' => (string) $produk->kode,
                        'nama' => (string) $produk->nama,
                        'satuan' => (string) ($produk->satuan?->nama ?? '-'),
                        'kategori' => (string) ($produk->kategoriProduk?->nama ?? '-'),
                        'stok_eksisting' => $this->stockAtDate((int) $produk->id, $cabangId, $tanggal),
                        'stok_on_order' => $this->stockOnOrderNow((int) $produk->id, $cabangId),
                        'target_qty' => $oldTargetQty[$produk->id],
                    ];
                }
            }
        }

        return view('pages.master.persediaan.stok.penyesuaian-create', [
            'cabangList' => $cabangList,
            'selectedCabangId' => $cabangId,
            'selectedTanggal' => $tanggal,
            'selectedRows' => $selectedRows,
        ]);
    }

    public function show(StokPenyesuaian $penyesuaian)
    {
        $this->ensureCabangAccessible((int) $penyesuaian->cabang_id);

        $penyesuaian->load([
            'cabang:id,kode,nama',
            'dibuatOleh:id,name',
            'items:id,stok_penyesuaian_id,produk_id,stok_sebelum,stok_setelah,qty_selisih',
            'items.produk:id,kode,nama',
        ]);

        return view('pages.master.persediaan.stok.penyesuaian-show', [
            'penyesuaian' => $penyesuaian,
        ]);
    }

    public function edit(StokPenyesuaian $penyesuaian)
    {
        $this->ensureCabangAccessible((int) $penyesuaian->cabang_id);

        $cabangList = $this->accessibleCabangQuery()->get(['id', 'kode', 'nama']);
        $selectedCabangId = (int) old('cabang_id', $penyesuaian->cabang_id);
        $selectedTanggal = (string) old(
            'tanggal_penyesuaian',
            optional($penyesuaian->tanggal_penyesuaian)->toDateString() ?: now()->toDateString()
        );

        $selectedRows = [];
        $oldTargetQty = old('target_qty', []);
        if (is_array($oldTargetQty) && !empty($oldTargetQty)) {
            $produkIds = collect(array_keys($oldTargetQty))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($produkIds->isNotEmpty()) {
                $produkMap = Produk::query()
                    ->with(['kategoriProduk:id,kode,nama', 'satuan:id,nama'])
                    ->whereIn('id', $produkIds)
                    ->where('track_stok', true)
                    ->whereHas('kategoriProduk', fn ($q) => $q->where('tipe', 'BARANG'))
                    ->get(['id', 'kode', 'nama', 'kategori_produk_kode', 'satuan_id'])
                    ->keyBy('id');

                foreach ($produkIds as $produkId) {
                    $produk = $produkMap->get($produkId);
                    if (!$produk) {
                        continue;
                    }

                    $selectedRows[] = [
                        'id' => (int) $produk->id,
                        'kode' => (string) $produk->kode,
                        'nama' => (string) $produk->nama,
                        'satuan' => (string) ($produk->satuan?->nama ?? '-'),
                        'kategori' => (string) ($produk->kategoriProduk?->nama ?? '-'),
                        'stok_eksisting' => $this->stockAtDate((int) $produk->id, $selectedCabangId, $selectedTanggal),
                        'stok_on_order' => $this->stockOnOrderNow((int) $produk->id, $selectedCabangId),
                        'target_qty' => $oldTargetQty[$produk->id],
                    ];
                }
            }
        } else {
            $selectedRows = $penyesuaian->items()
                ->with(['produk:id,kode,nama,kategori_produk_kode,satuan_id', 'produk.kategoriProduk:id,kode,nama', 'produk.satuan:id,nama'])
                ->get()
                ->map(function ($item) use ($selectedCabangId) {
                    return [
                        'id' => (int) $item->produk_id,
                        'kode' => (string) ($item->produk?->kode ?? '-'),
                        'nama' => (string) ($item->produk?->nama ?? '-'),
                        'satuan' => (string) ($item->produk?->satuan?->nama ?? '-'),
                        'kategori' => (string) ($item->produk?->kategoriProduk?->nama ?? '-'),
                        'stok_eksisting' => (float) $item->stok_sebelum,
                        'stok_on_order' => $this->stockOnOrderNow((int) $item->produk_id, $selectedCabangId),
                        'target_qty' => (float) $item->stok_setelah,
                    ];
                })
                ->values()
                ->all();
        }

        return view('pages.master.persediaan.stok.penyesuaian-edit', [
            'penyesuaian' => $penyesuaian,
            'cabangList' => $cabangList,
            'selectedCabangId' => $selectedCabangId,
            'selectedTanggal' => $selectedTanggal,
            'selectedRows' => $selectedRows,
        ]);
    }

    public function searchProduk(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cabang_id' => ['required', 'integer', 'exists:cabang,id'],
            'tanggal' => ['required', 'date'],
        ]);

        $cabangId = (int) $validated['cabang_id'];
        $this->ensureCabangAccessible($cabangId);

        $query = Produk::query()
            ->with(['kategoriProduk:id,kode,nama', 'satuan:id,nama'])
            ->where('track_stok', true)
            ->whereHas('kategoriProduk', fn ($q) => $q->where('tipe', 'BARANG'))
            ->orderBy('nama')
            ->limit(20);

        $keyword = trim((string) ($validated['q'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', '%' . $keyword . '%')
                    ->orWhere('kode', 'like', '%' . $keyword . '%');
            });
        }

        $items = $query->get(['id', 'kode', 'nama', 'kategori_produk_kode', 'satuan_id']);
        $results = $items->map(function ($produk) use ($cabangId, $validated) {
            $stokOnOrder = $this->stockOnOrderNow((int) $produk->id, $cabangId);
            $stokEksisting = $this->stockAtDate((int) $produk->id, $cabangId, $validated['tanggal']);
            return [
                'id' => (int) $produk->id,
                'kode' => (string) $produk->kode,
                'nama' => (string) $produk->nama,
                'satuan' => (string) ($produk->satuan?->nama ?? '-'),
                'kategori' => (string) ($produk->kategoriProduk?->nama ?? '-'),
                'stok_eksisting' => $stokEksisting,
                'stok_on_order' => $stokOnOrder,
                'stok_tersedia' => $stokEksisting - $stokOnOrder,
            ];
        })->values();

        return response()->json([
            'results' => $results,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_penyesuaian' => ['required', 'date'],
            'cabang_id' => ['required', 'integer', 'exists:cabang,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'target_qty' => ['required', 'array', 'min:1'],
            'target_qty.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cabangId = (int) $validated['cabang_id'];
        $this->ensureCabangAccessible($cabangId);

        $tanggalPenyesuaian = Carbon::parse($validated['tanggal_penyesuaian'])->endOfDay();
        $targetQty = collect($validated['target_qty'])
            ->map(fn ($qty) => $qty === null || $qty === '' ? null : (float) $qty)
            ->filter(fn ($qty) => $qty !== null)
            ->all();

        if (empty($targetQty)) {
            throw ValidationException::withMessages([
                'target_qty' => ['Isi minimal satu produk untuk penyesuaian.'],
            ]);
        }

        $produkIds = collect(array_keys($targetQty))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $produkAllowed = Produk::query()
            ->whereIn('id', $produkIds)
            ->where('track_stok', true)
            ->whereHas('kategoriProduk', fn ($q) => $q->where('tipe', 'BARANG'))
            ->get(['id', 'kode', 'nama'])
            ->keyBy('id');

        if ($produkAllowed->isEmpty()) {
            throw ValidationException::withMessages([
                'target_qty' => ['Produk yang dipilih tidak valid untuk penyesuaian stok.'],
            ]);
        }

        foreach ($targetQty as $produkIdRaw => $stokSetelah) {
            $produkId = (int) $produkIdRaw;
            if (!$produkAllowed->has($produkId)) {
                continue;
            }

            $stokOnOrder = $this->stockOnOrderNow($produkId, $cabangId);
            if ((float) $stokSetelah + 0.00001 < $stokOnOrder) {
                $produk = $produkAllowed->get($produkId);
                throw ValidationException::withMessages([
                    'target_qty' => ['Stok akhir untuk ' . ($produk?->kode ?? $produkId) . ' tidak boleh di bawah stok on-order (' . number_format($stokOnOrder, 2, ',', '.') . ').'],
                ]);
            }
        }

        $affectedProdukIds = [];

        DB::transaction(function () use (
            $validated,
            $cabangId,
            $tanggalPenyesuaian,
            $targetQty,
            $produkAllowed,
            &$affectedProdukIds
        ) {
            $penyesuaian = StokPenyesuaian::query()->create([
                'tanggal_penyesuaian' => $validated['tanggal_penyesuaian'],
                'cabang_id' => $cabangId,
                'catatan' => $validated['catatan'] ?? null,
                'dibuat_oleh' => auth()->id(),
            ]);

            foreach ($targetQty as $produkIdRaw => $stokSetelah) {
                $produkId = (int) $produkIdRaw;
                if (!$produkAllowed->has($produkId)) {
                    continue;
                }

                $stokSebelum = $this->stockAtTimestamp($produkId, $cabangId, $tanggalPenyesuaian);
                $selisih = round($stokSetelah - $stokSebelum, 2);
                if (abs($selisih) < 0.00001) {
                    continue;
                }

                $penyesuaian->items()->create([
                    'produk_id' => $produkId,
                    'stok_sebelum' => $stokSebelum,
                    'stok_setelah' => $stokSetelah,
                    'qty_selisih' => $selisih,
                ]);

                KartuStok::query()->create([
                    'produk_id' => $produkId,
                    'cabang_id' => $cabangId,
                    'tipe_mutasi' => 'ADJUSTMENT',
                    'referensi_tipe' => 'STOK_PENYESUAIAN',
                    'referensi_id' => $penyesuaian->id,
                    'qty_masuk' => $selisih > 0 ? $selisih : 0,
                    'qty_keluar' => $selisih < 0 ? abs($selisih) : 0,
                    'saldo_akhir' => round($stokSetelah, 2),
                    'catatan' => 'Penyesuaian stok manual',
                    'tanggal_mutasi' => $tanggalPenyesuaian,
                ]);

                $affectedProdukIds[] = $produkId;
            }

            if (empty($affectedProdukIds)) {
                throw ValidationException::withMessages([
                    'target_qty' => ['Tidak ada perubahan stok. Pastikan nilai stok setelah berbeda dari stok eksisting.'],
                ]);
            }
        });

        foreach (collect($affectedProdukIds)->unique()->values() as $produkId) {
            $this->rebuildLedger((int) $produkId, $cabangId);
        }

        return redirect()
            ->route('persediaan.stok.penyesuaian')
            ->with('success', 'Penyesuaian stok berhasil disimpan.');
    }

    public function destroy(StokPenyesuaian $penyesuaian)
    {
        $this->ensureCabangAccessible((int) $penyesuaian->cabang_id);

        $cabangId = (int) $penyesuaian->cabang_id;
        $itemRows = $penyesuaian->items()
            ->get(['produk_id', 'stok_sebelum', 'qty_selisih'])
            ->map(function ($item) {
                return [
                    'produk_id' => (int) $item->produk_id,
                    'stok_sebelum' => (float) $item->stok_sebelum,
                    'qty_selisih' => (float) $item->qty_selisih,
                ];
            });
        $produkIds = $itemRows->pluck('produk_id')->unique()->values()->all();
        $stokSebelumMap = $itemRows->keyBy('produk_id');
        $rollbackSelisihMap = $itemRows
            ->groupBy('produk_id')
            ->map(fn ($rows) => (float) $rows->sum('qty_selisih'));
        $stokSebelumDeleteMap = StokCabang::query()
            ->where('cabang_id', $cabangId)
            ->whereIn('produk_id', $produkIds)
            ->pluck('qty', 'produk_id')
            ->map(fn ($qty) => (float) $qty);

        DB::transaction(function () use ($penyesuaian) {
            KartuStok::query()
                ->where('referensi_tipe', 'STOK_PENYESUAIAN')
                ->where('referensi_id', $penyesuaian->id)
                ->delete();

            $penyesuaian->delete();
        });

        foreach ($produkIds as $produkId) {
            $hasLedger = KartuStok::query()
                ->where('produk_id', (int) $produkId)
                ->where('cabang_id', $cabangId)
                ->exists();

            if ($hasLedger) {
                $this->rebuildLedger((int) $produkId, $cabangId);
            } else {
                $stokSebelum = (float) ($stokSebelumMap->get((int) $produkId)['stok_sebelum'] ?? 0);
                StokCabang::query()->updateOrCreate(
                    [
                        'produk_id' => (int) $produkId,
                        'cabang_id' => $cabangId,
                    ],
                    [
                        'qty' => round($stokSebelum, 2),
                    ]
                );
            }

            $qtySebelumDelete = (float) ($stokSebelumDeleteMap->get((int) $produkId) ?? 0);
            $rollbackSelisih = (float) ($rollbackSelisihMap->get((int) $produkId) ?? 0);
            $qtyExpected = round($qtySebelumDelete - $rollbackSelisih, 2);
            StokCabang::query()->updateOrCreate(
                [
                    'produk_id' => (int) $produkId,
                    'cabang_id' => $cabangId,
                ],
                [
                    'qty' => $qtyExpected,
                ]
            );
        }

        return back()->with('success', 'Penyesuaian stok berhasil dihapus. Saldo stok sudah dikembalikan.');
    }

    public function update(Request $request, StokPenyesuaian $penyesuaian)
    {
        $oldCabangId = (int) $penyesuaian->cabang_id;
        $this->ensureCabangAccessible($oldCabangId);

        $validated = $request->validate([
            'tanggal_penyesuaian' => ['required', 'date'],
            'cabang_id' => ['required', 'integer', 'exists:cabang,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'target_qty' => ['required', 'array', 'min:1'],
            'target_qty.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cabangId = (int) $validated['cabang_id'];
        $this->ensureCabangAccessible($cabangId);

        $tanggalPenyesuaian = Carbon::parse($validated['tanggal_penyesuaian'])->endOfDay();
        $targetQty = collect($validated['target_qty'])
            ->map(fn ($qty) => $qty === null || $qty === '' ? null : (float) $qty)
            ->filter(fn ($qty) => $qty !== null)
            ->all();

        if (empty($targetQty)) {
            throw ValidationException::withMessages([
                'target_qty' => ['Isi minimal satu produk untuk penyesuaian.'],
            ]);
        }

        $produkIds = collect(array_keys($targetQty))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $produkAllowed = Produk::query()
            ->whereIn('id', $produkIds)
            ->where('track_stok', true)
            ->whereHas('kategoriProduk', fn ($q) => $q->where('tipe', 'BARANG'))
            ->get(['id', 'kode', 'nama'])
            ->keyBy('id');

        if ($produkAllowed->isEmpty()) {
            throw ValidationException::withMessages([
                'target_qty' => ['Produk yang dipilih tidak valid untuk penyesuaian stok.'],
            ]);
        }

        foreach ($targetQty as $produkIdRaw => $stokSetelah) {
            $produkId = (int) $produkIdRaw;
            if (!$produkAllowed->has($produkId)) {
                continue;
            }

            $stokOnOrder = $this->stockOnOrderNow($produkId, $cabangId);
            if ((float) $stokSetelah + 0.00001 < $stokOnOrder) {
                $produk = $produkAllowed->get($produkId);
                throw ValidationException::withMessages([
                    'target_qty' => ['Stok akhir untuk ' . ($produk?->kode ?? $produkId) . ' tidak boleh di bawah stok on-order (' . number_format($stokOnOrder, 2, ',', '.') . ').'],
                ]);
            }
        }

        $oldRows = $penyesuaian->items()
            ->get(['produk_id', 'stok_sebelum'])
            ->map(function ($item) {
                return [
                    'produk_id' => (int) $item->produk_id,
                    'stok_sebelum' => (float) $item->stok_sebelum,
                ];
            });

        $oldProdukIds = $oldRows->pluck('produk_id')->unique()->values()->all();
        $oldStokSebelumMap = $oldRows->keyBy('produk_id');
        $affectedProdukIds = [];

        DB::transaction(function () use (
            $penyesuaian,
            $validated,
            $cabangId,
            $tanggalPenyesuaian,
            $targetQty,
            $produkAllowed,
            &$affectedProdukIds
        ) {
            KartuStok::query()
                ->where('referensi_tipe', 'STOK_PENYESUAIAN')
                ->where('referensi_id', $penyesuaian->id)
                ->delete();
            $penyesuaian->items()->delete();

            $penyesuaian->update([
                'tanggal_penyesuaian' => $validated['tanggal_penyesuaian'],
                'cabang_id' => $cabangId,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($targetQty as $produkIdRaw => $stokSetelah) {
                $produkId = (int) $produkIdRaw;
                if (!$produkAllowed->has($produkId)) {
                    continue;
                }

                $stokSebelum = $this->stockAtTimestamp($produkId, $cabangId, $tanggalPenyesuaian);
                $selisih = round($stokSetelah - $stokSebelum, 2);
                if (abs($selisih) < 0.00001) {
                    continue;
                }

                $penyesuaian->items()->create([
                    'produk_id' => $produkId,
                    'stok_sebelum' => $stokSebelum,
                    'stok_setelah' => $stokSetelah,
                    'qty_selisih' => $selisih,
                ]);

                KartuStok::query()->create([
                    'produk_id' => $produkId,
                    'cabang_id' => $cabangId,
                    'tipe_mutasi' => 'ADJUSTMENT',
                    'referensi_tipe' => 'STOK_PENYESUAIAN',
                    'referensi_id' => $penyesuaian->id,
                    'qty_masuk' => $selisih > 0 ? $selisih : 0,
                    'qty_keluar' => $selisih < 0 ? abs($selisih) : 0,
                    'saldo_akhir' => round($stokSetelah, 2),
                    'catatan' => 'Penyesuaian stok manual',
                    'tanggal_mutasi' => $tanggalPenyesuaian,
                ]);

                $affectedProdukIds[] = $produkId;
            }

            if (empty($affectedProdukIds)) {
                throw ValidationException::withMessages([
                    'target_qty' => ['Tidak ada perubahan stok. Pastikan nilai stok setelah berbeda dari stok eksisting.'],
                ]);
            }
        });

        if ($oldCabangId === $cabangId) {
            $allProdukIds = collect($oldProdukIds)
                ->merge(collect($affectedProdukIds))
                ->unique()
                ->values();

            foreach ($allProdukIds as $produkId) {
                $hasLedger = KartuStok::query()
                    ->where('produk_id', (int) $produkId)
                    ->where('cabang_id', $cabangId)
                    ->exists();

                if ($hasLedger) {
                    $this->rebuildLedger((int) $produkId, $cabangId);
                    continue;
                }

                $stokSebelum = (float) ($oldStokSebelumMap->get((int) $produkId)['stok_sebelum'] ?? 0);
                StokCabang::query()->updateOrCreate(
                    [
                        'produk_id' => (int) $produkId,
                        'cabang_id' => $cabangId,
                    ],
                    [
                        'qty' => round($stokSebelum, 2),
                    ]
                );
            }
        } else {
            foreach ($oldProdukIds as $produkId) {
                $hasLedgerOldCabang = KartuStok::query()
                    ->where('produk_id', (int) $produkId)
                    ->where('cabang_id', $oldCabangId)
                    ->exists();

                if ($hasLedgerOldCabang) {
                    $this->rebuildLedger((int) $produkId, $oldCabangId);
                    continue;
                }

                $stokSebelum = (float) ($oldStokSebelumMap->get((int) $produkId)['stok_sebelum'] ?? 0);
                StokCabang::query()->updateOrCreate(
                    [
                        'produk_id' => (int) $produkId,
                        'cabang_id' => $oldCabangId,
                    ],
                    [
                        'qty' => round($stokSebelum, 2),
                    ]
                );
            }

            foreach (collect($affectedProdukIds)->unique()->values() as $produkId) {
                $this->rebuildLedger((int) $produkId, $cabangId);
            }
        }

        return redirect()
            ->route('persediaan.stok.penyesuaian')
            ->with('success', 'Penyesuaian stok berhasil diperbarui.');
    }

    private function stockAtDate(int $produkId, int $cabangId, string $tanggal): float
    {
        $timestamp = Carbon::parse($tanggal)->endOfDay();
        $saldo = KartuStok::query()
            ->where('produk_id', $produkId)
            ->where('cabang_id', $cabangId)
            ->where('tanggal_mutasi', '<=', $timestamp->format('Y-m-d H:i:s'))
            ->orderByDesc('tanggal_mutasi')
            ->orderByDesc('id')
            ->value('saldo_akhir');

        if ($saldo !== null) {
            return (float) $saldo;
        }

        return (float) (StokCabang::query()
            ->where('produk_id', $produkId)
            ->where('cabang_id', $cabangId)
            ->value('qty') ?? 0);
    }

    private function stockOnOrderNow(int $produkId, int $cabangId): float
    {
        return (float) (StokCabang::query()
            ->where('produk_id', $produkId)
            ->where('cabang_id', $cabangId)
            ->value('qty_on_order') ?? 0);
    }

    private function stockAtTimestamp(int $produkId, int $cabangId, Carbon $timestamp): float
    {
        $saldo = KartuStok::query()
            ->where('produk_id', $produkId)
            ->where('cabang_id', $cabangId)
            ->where('tanggal_mutasi', '<=', $timestamp->format('Y-m-d H:i:s'))
            ->orderByDesc('tanggal_mutasi')
            ->orderByDesc('id')
            ->value('saldo_akhir');

        if ($saldo !== null) {
            return (float) $saldo;
        }

        return (float) (StokCabang::query()
            ->where('produk_id', $produkId)
            ->where('cabang_id', $cabangId)
            ->value('qty') ?? 0);
    }

    private function rebuildLedger(int $produkId, int $cabangId): void
    {
        $rows = KartuStok::query()
            ->where('produk_id', $produkId)
            ->where('cabang_id', $cabangId)
            ->orderBy('tanggal_mutasi')
            ->orderBy('id')
            ->get(['id', 'qty_masuk', 'qty_keluar', 'saldo_akhir']);

        if ($rows->isEmpty()) {
            StokCabang::query()->updateOrCreate(
                [
                    'produk_id' => $produkId,
                    'cabang_id' => $cabangId,
                ],
                [
                    'qty' => 0,
                ]
            );
            return;
        }

        $first = $rows->first();
        $firstMovement = (float) $first->qty_masuk - (float) $first->qty_keluar;
        $running = (float) $first->saldo_akhir - $firstMovement;
        foreach ($rows as $row) {
            $running = $running + (float) $row->qty_masuk - (float) $row->qty_keluar;
            KartuStok::query()->whereKey($row->id)->update([
                'saldo_akhir' => round($running, 2),
            ]);
        }

        StokCabang::query()->updateOrCreate(
            [
                'produk_id' => $produkId,
                'cabang_id' => $cabangId,
            ],
            [
                'qty' => round($running, 2),
            ]
        );
    }
}

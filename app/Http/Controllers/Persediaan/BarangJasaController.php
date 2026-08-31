<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\CabangSalesMode;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\StokCabang;
use App\Models\TemplateHargaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangJasaController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::query()
            ->with(['kategoriProduk', 'satuan'])
            ->addSelect([
                'has_stock' => StokCabang::query()
                    ->selectRaw('CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END')
                    ->whereColumn('stok_cabang.produk_id', 'produk.id')
                    ->where('qty', '>', 0),
            ])
            ->latest('id');

        if ($request->filled('nama_item')) {
            $query->where('nama', 'like', '%' . $request->nama_item . '%');
        }

        if ($request->filled('kode_item')) {
            $query->where('kode', 'like', '%' . $request->kode_item . '%');
        }

        if ($request->filled('golongan')) {
            $query->where('kategori_produk_kode', $request->golongan);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        $produk = $query->paginate(50)->withQueryString();
        $produkIds = $produk->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $templateHargaOptions = $this->accessibleTemplateHargaOptions();
        $templateHargaIds = $templateHargaOptions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $produkTemplateHargaMap = (empty($produkIds) || empty($templateHargaIds))
            ? collect()
            : TemplateHargaItem::query()
                ->where('jenis_item', 'PRODUK')
                ->whereIn('item_id', $produkIds)
                ->whereIn('template_harga_id', $templateHargaIds)
                ->get(['template_harga_id', 'item_id', 'harga', 'status'])
                ->groupBy('item_id')
                ->map(fn ($group) => $group->keyBy('template_harga_id'));

        return view('pages.master.persediaan.barang_jasa.index', [
            'produk' => $produk,
            'golonganList' => KategoriProduk::query()->orderBy('nama')->get(),
            'satuanList' => Satuan::query()->orderBy('nama')->get(),
            'templateHargaOptions' => $templateHargaOptions,
            'produkTemplateHargaMap' => $produkTemplateHargaMap,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:produk,kode'],
            'nama' => ['required', 'string', 'max:150'],
            'kategori_produk_kode' => ['required', 'exists:kategori_produk,kode'],
            'satuan_id' => ['nullable', 'exists:satuan,id'],
            'track_stok' => ['nullable', 'boolean'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
            'template_harga_ids' => ['nullable', 'array'],
            'template_harga_ids.*' => ['integer', 'exists:template_harga,id'],
            'template_harga_prices' => ['nullable', 'array'],
            'template_harga_prices.*' => ['nullable', 'numeric', 'min:0'],
            'template_harga_status' => ['nullable', 'array'],
        ]);

        KategoriProduk::query()->where('kode', $validated['kategori_produk_kode'])->firstOrFail();

        DB::transaction(function () use ($validated) {
            $produk = Produk::query()->create([
                'kode' => strtoupper($validated['kode']),
                'nama' => $validated['nama'],
                'kategori_produk_kode' => strtoupper((string) $validated['kategori_produk_kode']),
                'satuan_id' => $validated['satuan_id'] ?? null,
                'track_stok' => (bool) ($validated['track_stok'] ?? false),
                'harga_default' => $validated['harga_default'],
                'status' => (bool) ($validated['status'] ?? true),
            ]);

            $this->syncTemplateHargaProduk($produk, $validated);
        });

        return redirect()->route('persediaan.barang-jasa')->with('success', 'Barang/Jasa berhasil ditambahkan.');
    }

    public function update(Request $request, Produk $barangJasa)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:produk,kode,' . $barangJasa->id],
            'nama' => ['required', 'string', 'max:150'],
            'kategori_produk_kode' => ['required', 'exists:kategori_produk,kode'],
            'satuan_id' => ['nullable', 'exists:satuan,id'],
            'track_stok' => ['nullable', 'boolean'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
            'template_harga_ids' => ['nullable', 'array'],
            'template_harga_ids.*' => ['integer', 'exists:template_harga,id'],
            'template_harga_prices' => ['nullable', 'array'],
            'template_harga_prices.*' => ['nullable', 'numeric', 'min:0'],
            'template_harga_status' => ['nullable', 'array'],
        ]);

        KategoriProduk::query()->where('kode', $validated['kategori_produk_kode'])->firstOrFail();
        $requestedTrackStok = (bool) ($validated['track_stok'] ?? false);
        if ($requestedTrackStok !== (bool) $barangJasa->track_stok && $this->produkMemilikiStok($barangJasa->id)) {
            return redirect()
                ->back()
                ->withErrors(['track_stok' => 'Track stok tidak bisa diubah karena produk ini masih memiliki stok.'])
                ->withInput();
        }

        DB::transaction(function () use ($barangJasa, $validated, $requestedTrackStok) {
            $barangJasa->update([
                'kode' => strtoupper($validated['kode']),
                'nama' => $validated['nama'],
                'kategori_produk_kode' => strtoupper((string) $validated['kategori_produk_kode']),
                'satuan_id' => $validated['satuan_id'] ?? null,
                'track_stok' => $requestedTrackStok,
                'harga_default' => $validated['harga_default'],
                'status' => (bool) ($validated['status'] ?? false),
            ]);

            $this->syncTemplateHargaProduk($barangJasa, $validated);
        });

        return redirect()->route('persediaan.barang-jasa')->with('success', 'Barang/Jasa berhasil diperbarui.');
    }

    public function destroy(Produk $barangJasa)
    {
        $dipakaiTransaksi = DB::table('pesanan_penjualan_item')->where('produk_id', $barangJasa->id)->exists()
            || DB::table('pesanan_pembelian_item')->where('produk_id', $barangJasa->id)->exists()
            || DB::table('penerimaan_barang_item')->where('produk_id', $barangJasa->id)->exists()
            || DB::table('faktur_pembelian_item')->where('produk_id', $barangJasa->id)->exists()
            || DB::table('retur_pembelian_item')->where('produk_id', $barangJasa->id)->exists()
            || DB::table('kartu_stok')->where('produk_id', $barangJasa->id)->exists();

        if ($dipakaiTransaksi) {
            if ($barangJasa->status) {
                $barangJasa->update(['status' => false]);
                return redirect()->route('persediaan.barang-jasa')->with('success', 'Barang/Jasa sudah dipakai transaksi, tidak bisa dihapus. Status diubah menjadi Non Aktif.');
            }

            return redirect()->route('persediaan.barang-jasa')->with('success', 'Barang/Jasa sudah dipakai transaksi dan tetap Non Aktif.');
        }

        $barangJasa->delete();
        return redirect()->route('persediaan.barang-jasa')->with('success', 'Barang/Jasa berhasil dihapus.');
    }

    public function batchUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:produk,id'],
            'field' => ['required', 'in:status,track_stok'],
            'value' => ['required', 'in:0,1'],
        ]);

        $ids = collect($validated['ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $produk = Produk::query()->whereIn('id', $ids)->get(['id', 'track_stok', 'status']);
        if ($produk->isEmpty()) {
            return redirect()->back()->with('warning', 'Tidak ada data yang dipilih untuk diproses.');
        }

        $field = $validated['field'];
        $newValue = (bool) ((int) $validated['value']);

        if ($field === 'status') {
            $updated = Produk::query()->whereIn('id', $produk->pluck('id'))->update(['status' => $newValue]);
            $statusText = $newValue ? 'Aktif' : 'Non Aktif';
            return redirect()->back()->with('success', "{$updated} item berhasil diubah ke status {$statusText}.");
        }

        $blockedIds = StokCabang::query()
            ->whereIn('produk_id', $produk->pluck('id'))
            ->where('qty', '>', 0)
            ->distinct()
            ->pluck('produk_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allowedIds = $produk->pluck('id')
            ->filter(fn ($id) => !in_array((int) $id, $blockedIds, true))
            ->values();
        $updated = 0;
        if ($allowedIds->isNotEmpty()) {
            $updated = Produk::query()->whereIn('id', $allowedIds)->update(['track_stok' => $newValue]);
        }

        if (count($blockedIds) > 0) {
            $stateText = $newValue ? 'Ya' : 'Tidak';
            $blockedCount = count($blockedIds);
            return redirect()->back()->with('warning', "{$updated} item berhasil diubah track stok ke {$stateText}. {$blockedCount} item dilewati karena masih memiliki stok.");
        }

        $stateText = $newValue ? 'Ya' : 'Tidak';
        return redirect()->back()->with('success', "{$updated} item berhasil diubah track stok ke {$stateText}.");
    }

    private function produkMemilikiStok(int $produkId): bool
    {
        return StokCabang::query()
            ->where('produk_id', $produkId)
            ->where('qty', '>', 0)
            ->exists();
    }

    private function accessibleTemplateHargaOptions()
    {
        $allowedCabangIds = $this->accessibleCabangIds();

        return CabangSalesMode::query()
            ->join('template_harga', 'template_harga.id', '=', 'cabang_sales_mode.template_harga_id')
            ->join('cabang', 'cabang.id', '=', 'cabang_sales_mode.cabang_id')
            ->whereNotNull('cabang_sales_mode.template_harga_id')
            ->where('cabang_sales_mode.status', true)
            ->where('template_harga.status', true)
            ->when(!empty($allowedCabangIds), function ($query) use ($allowedCabangIds) {
                $query->whereIn('cabang_sales_mode.cabang_id', $allowedCabangIds);
            })
            ->groupBy('template_harga.id', 'template_harga.kode', 'template_harga.nama')
            ->orderBy('template_harga.nama')
            ->get([
                'template_harga.id',
                'template_harga.kode',
                'template_harga.nama',
                DB::raw("GROUP_CONCAT(DISTINCT cabang.nama ORDER BY cabang.nama SEPARATOR ', ') as cabang_nama"),
            ]);
    }

    private function syncTemplateHargaProduk(Produk $produk, array $validated): void
    {
        $allowedTemplateIds = $this->accessibleTemplateHargaOptions()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $submittedTemplateIds = collect($validated['template_harga_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $invalidTemplateIds = array_values(array_diff($submittedTemplateIds, $allowedTemplateIds));
        if (!empty($invalidTemplateIds)) {
            abort(422, 'Ada template harga yang tidak termasuk akses cabang Anda.');
        }

        $submittedTemplateIdMap = array_flip($submittedTemplateIds);
        $priceMap = $validated['template_harga_prices'] ?? [];
        $statusMap = $validated['template_harga_status'] ?? [];

        foreach ($allowedTemplateIds as $templateHargaId) {
            if (!isset($submittedTemplateIdMap[$templateHargaId])) {
                continue;
            }

            $harga = (float) ($priceMap[$templateHargaId] ?? $produk->harga_default ?? 0);
            $status = (bool) ($statusMap[$templateHargaId] ?? false);

            TemplateHargaItem::query()->updateOrCreate(
                [
                    'template_harga_id' => $templateHargaId,
                    'jenis_item' => 'PRODUK',
                    'item_id' => $produk->id,
                ],
                [
                    'harga' => $harga,
                    'status' => $status,
                ]
            );
        }
    }
}

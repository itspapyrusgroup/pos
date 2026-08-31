<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\CabangSalesMode;
use App\Models\KategoriPaket;
use App\Models\KategoriProduk;
use App\Models\Paket;
use App\Models\PesananPenjualan;
use App\Models\Produk;
use App\Models\TemplateHarga;
use App\Models\TemplateHargaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateHargaController extends Controller
{
    public function index(Request $request)
    {
        $query = TemplateHarga::query()->latest('id');
        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.pos.template_harga.index', [
            'templateHarga' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        TemplateHarga::query()->create([
            'kode' => $this->generateKode(),
            'nama' => $validated['nama'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        return redirect()->route('template.harga')->with('success', 'Template harga berhasil ditambahkan.');
    }

    public function update(Request $request, TemplateHarga $templateHarga)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        $templateHarga->update([
            'nama' => $validated['nama'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()->route('template.harga')->with('success', 'Template harga berhasil diperbarui.');
    }

    public function destroy(TemplateHarga $templateHarga)
    {
        $dipakaiTransaksi = PesananPenjualan::query()
            ->where('template_harga_id', $templateHarga->id)
            ->exists();

        $masihDipakaiMappingCabang = CabangSalesMode::query()
            ->where('template_harga_id', $templateHarga->id)
            ->exists();

        if ($dipakaiTransaksi || $masihDipakaiMappingCabang) {
            if ($templateHarga->status) {
                $templateHarga->update(['status' => false]);
                return redirect()->route('template.harga')->with('success', 'Template harga sudah dipakai, tidak bisa dihapus. Status diubah menjadi Non Aktif.');
            }

            return redirect()->route('template.harga')->with('success', 'Template harga sudah dipakai dan tetap Non Aktif.');
        }

        $templateHarga->delete();
        return redirect()->route('template.harga')->with('success', 'Template harga berhasil dihapus.');
    }

    public function detail(TemplateHarga $templateHarga)
    {
        $items = $templateHarga->items->keyBy(function (TemplateHargaItem $item) {
            return $item->jenis_item . '-' . $item->item_id;
        });

        return view('pages.pos.template_harga.tambah', [
            'template' => $templateHarga,
            'produkList' => Produk::query()
                ->with('kategoriProduk:id,kode,nama,tipe')
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode', 'kategori_produk_kode', 'harga_default']),
            'paketList' => Paket::query()
                ->with('kategoriPaket:id,nama')
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode', 'kategori_paket_id', 'harga_default']),
            'addonList' => Addon::query()->orderBy('nama')->get(['id', 'nama']),
            'kategoriPaketList' => KategoriPaket::query()->orderBy('nama')->get(['id', 'nama']),
            'golonganList' => KategoriProduk::query()->orderBy('nama')->get(['kode', 'nama', 'tipe']),
            'templateSumberList' => TemplateHarga::query()
                ->where('id', '!=', $templateHarga->id)
                ->where('status', true)
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama']),
            'itemHarga' => $items,
        ]);
    }

    public function copySource(Request $request, TemplateHarga $templateHarga)
    {
        $validated = $request->validate([
            'source_template_id' => ['required', 'integer', 'exists:template_harga,id'],
        ]);

        $sourceTemplateId = (int) $validated['source_template_id'];
        if ($sourceTemplateId === (int) $templateHarga->id) {
            return response()->json([
                'message' => 'Template sumber tidak boleh template yang sedang diedit.',
            ], 422);
        }

        $sourceItems = TemplateHargaItem::query()
            ->where('template_harga_id', $sourceTemplateId)
            ->get(['jenis_item', 'item_id', 'harga', 'status'])
            ->mapWithKeys(function (TemplateHargaItem $item) {
                $key = $item->jenis_item . '-' . $item->item_id;
                return [
                    $key => [
                        'harga' => (float) $item->harga,
                        'status' => (bool) $item->status,
                    ],
                ];
            });

        return response()->json([
            'items' => $sourceItems,
        ]);
    }

    public function simpanDetail(Request $request, TemplateHarga $templateHarga)
    {
        $request->validate([
            'items_payload' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        $items = $request->input('items', []);
        if ($request->filled('items_payload')) {
            $decoded = json_decode((string) $request->input('items_payload'), true);
            if (!is_array($decoded)) {
                return redirect()
                    ->route('template.harga.detail', $templateHarga)
                    ->withErrors(['items_payload' => 'Format data item tidak valid.']);
            }
            $items = $decoded;
        }

        $validated = validator(
            ['items' => $items],
            [
                'items' => ['array'],
                'items.*.jenis_item' => ['required', 'in:PRODUK,PAKET,ADDON'],
                'items.*.item_id' => ['required', 'integer'],
                'items.*.harga' => ['required', 'numeric', 'min:0'],
                'items.*.status' => ['nullable', 'boolean'],
            ]
        )->validate();

        DB::transaction(function () use ($validated, $templateHarga) {
            $templateHarga->items()->delete();

            foreach (($validated['items'] ?? []) as $item) {
                TemplateHargaItem::query()->create([
                    'template_harga_id' => $templateHarga->id,
                    'jenis_item' => $item['jenis_item'],
                    'item_id' => $item['item_id'],
                    'harga' => $item['harga'],
                    'status' => (bool) ($item['status'] ?? true),
                ]);
            }
        });

        return redirect()->route('template.harga.detail', $templateHarga)->with('success', 'Detail template harga berhasil disimpan.');
    }

    private function generateKode(): string
    {
        $prefix = 'TH-';
        $lastId = (int) TemplateHarga::query()->max('id');
        return $prefix . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}

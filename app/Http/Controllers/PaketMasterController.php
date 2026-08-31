<?php

namespace App\Http\Controllers;

use App\Models\KategoriPaket;
use App\Models\CabangSalesMode;
use App\Models\Paket;
use App\Models\PaketItem;
use App\Models\PesananPenjualanItem;
use App\Models\Produk;
use App\Models\TemplateHargaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaketMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = Paket::query()->with(['kategoriPaket', 'items.produk'])->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }
        if ($request->filled('kategori_paket_id')) {
            $query->where('kategori_paket_id', $request->kategori_paket_id);
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        $paketList = $query->paginate(25)->withQueryString();
        $paketIds = $paketList->getCollection()->pluck('id')->all();
        $templateHargaOptions = $this->accessibleTemplateHargaOptions();
        $templateHargaIds = $templateHargaOptions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $paketDipakaiIds = empty($paketIds)
            ? []
            : PesananPenjualanItem::query()
                ->whereIn('paket_id', $paketIds)
                ->pluck('paket_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all();
        $paketTemplateHargaMap = (empty($paketIds) || empty($templateHargaIds))
            ? collect()
            : TemplateHargaItem::query()
                ->where('jenis_item', 'PAKET')
                ->whereIn('item_id', $paketIds)
                ->whereIn('template_harga_id', $templateHargaIds)
                ->get(['template_harga_id', 'item_id', 'harga', 'status'])
                ->groupBy('item_id')
                ->map(fn ($group) => $group->keyBy('template_harga_id'));

        $paketList->setCollection(
            $paketList->getCollection()->map(function (Paket $paket) use ($paketDipakaiIds) {
                $paket->is_dipakai_transaksi = in_array((int) $paket->id, $paketDipakaiIds, true);
                return $paket;
            })
        );

        return view('pages.pos.paket.index', [
            'paketList' => $paketList,
            'kategoriPaket' => KategoriPaket::query()->where('status', true)->orderBy('nama')->get(),
            'templateHargaOptions' => $templateHargaOptions,
            'paketTemplateHargaMap' => $paketTemplateHargaMap,
        ]);
    }

    public function cariProdukSelect(Request $request)
    {
        $term = trim((string) $request->query('term', ''));
        $page = max((int) $request->query('page', 1), 1);
        $perPage = 20;

        $query = Produk::query()
            ->where('status', true)
            ->when($term !== '', function ($builder) use ($term) {
                $builder->where(function ($inner) use ($term) {
                    $inner->where('kode', 'like', '%' . $term . '%')
                        ->orWhere('nama', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('kode')
            ->orderBy('nama');

        $total = (clone $query)->count();
        $rows = $query
            ->forPage($page, $perPage)
            ->get(['id', 'kode', 'nama']);

        return response()->json([
            'results' => $rows->map(function ($produk) {
                return [
                    'id' => (int) $produk->id,
                    'text' => trim(($produk->kode ? ($produk->kode . ' - ') : '') . $produk->nama),
                ];
            })->values(),
            'pagination' => [
                'more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'kategori_paket_id' => ['nullable', 'exists:kategori_paket,id'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'item_produk_id' => ['required', 'array', 'min:1'],
            'item_produk_id.*' => ['nullable', 'exists:produk,id'],
            'item_qty' => ['required', 'array', 'min:1'],
            'item_qty.*' => ['nullable', 'integer', 'min:1'],
            'template_harga_ids' => ['nullable', 'array'],
            'template_harga_ids.*' => ['integer', 'exists:template_harga,id'],
            'template_harga_prices' => ['nullable', 'array'],
            'template_harga_prices.*' => ['nullable', 'numeric', 'min:0'],
            'template_harga_status' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($validated) {
            $paket = Paket::query()->create([
                'kode' => $this->generateKode(),
                'nama' => $validated['nama'],
                'harga_default' => $validated['harga_default'],
                'kategori_paket_id' => $validated['kategori_paket_id'] ?? null,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'status' => (bool) ($validated['status'] ?? true),
            ]);

            $this->simpanItems($paket, $validated['item_produk_id'] ?? [], $validated['item_qty'] ?? []);
            $this->syncTemplateHargaPaket($paket, $validated);
        });

        return redirect()->route('paket.list')->with('success', 'Paket berhasil ditambahkan.');
    }

    public function update(Request $request, Paket $paket)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'kategori_paket_id' => ['nullable', 'exists:kategori_paket,id'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'item_produk_id' => ['required', 'array', 'min:1'],
            'item_produk_id.*' => ['nullable', 'exists:produk,id'],
            'item_qty' => ['required', 'array', 'min:1'],
            'item_qty.*' => ['nullable', 'integer', 'min:1'],
            'template_harga_ids' => ['nullable', 'array'],
            'template_harga_ids.*' => ['integer', 'exists:template_harga,id'],
            'template_harga_prices' => ['nullable', 'array'],
            'template_harga_prices.*' => ['nullable', 'numeric', 'min:0'],
            'template_harga_status' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($validated, $paket) {
            $paket->update([
                'nama' => $validated['nama'],
                'harga_default' => $validated['harga_default'],
                'kategori_paket_id' => $validated['kategori_paket_id'] ?? null,
                'deskripsi' => $validated['deskripsi'] ?? null,
                'status' => (bool) ($validated['status'] ?? false),
            ]);

            $paket->items()->delete();
            $this->simpanItems($paket, $validated['item_produk_id'] ?? [], $validated['item_qty'] ?? []);
            $this->syncTemplateHargaPaket($paket, $validated);
        });

        return redirect()->route('paket.list')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(Paket $paket)
    {
        $dipakaiTransaksi = PesananPenjualanItem::query()
            ->where('paket_id', $paket->id)
            ->exists();

        if ($dipakaiTransaksi) {
            if ($paket->status) {
                $paket->update(['status' => false]);
                return redirect()->route('paket.list')->with('success', 'Paket sudah dipakai transaksi, tidak bisa dihapus. Status diubah menjadi Non Aktif.');
            }

            return redirect()->route('paket.list')->with('success', 'Paket sudah dipakai transaksi dan tetap Non Aktif.');
        }

        $paket->delete();
        return redirect()->route('paket.list')->with('success', 'Paket berhasil dihapus.');
    }

    public function batchUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:paket,id'],
            'status' => ['required', 'in:0,1'],
        ]);

        $ids = collect($validated['ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $statusBaru = (bool) ((int) $validated['status']);
        $updated = Paket::query()->whereIn('id', $ids)->update(['status' => $statusBaru]);
        $statusText = $statusBaru ? 'Aktif' : 'Non Aktif';

        return redirect()->back()->with('success', "{$updated} paket berhasil diubah ke status {$statusText}.");
    }

    private function generateKode(): string
    {
        $prefix = 'PKT-' . now()->format('ymd') . '-';
        $last = Paket::query()->where('kode', 'like', $prefix . '%')->orderByDesc('id')->value('kode');
        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function simpanItems(Paket $paket, array $produkIds, array $qtys): void
    {
        $created = 0;
        foreach ($produkIds as $idx => $produkId) {
            $qty = (float) ($qtys[$idx] ?? 0);
            if ($produkId && $qty > 0) {
                PaketItem::query()->create([
                    'paket_id' => $paket->kode,
                    'produk_id' => $produkId,
                    'qty' => $qty,
                ]);
                $created++;
            }
        }

        if ($created === 0) {
            abort(422, 'Minimal 1 item paket wajib diisi.');
        }
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

    private function syncTemplateHargaPaket(Paket $paket, array $validated): void
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

            $harga = (float) ($priceMap[$templateHargaId] ?? $paket->harga_default ?? 0);
            $status = (bool) ($statusMap[$templateHargaId] ?? false);

            TemplateHargaItem::query()->updateOrCreate(
                [
                    'template_harga_id' => $templateHargaId,
                    'jenis_item' => 'PAKET',
                    'item_id' => $paket->id,
                ],
                [
                    'harga' => $harga,
                    'status' => $status,
                ]
            );
        }
    }
}

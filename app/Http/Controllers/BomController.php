<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $query = Bom::query()->with(['items.produk'])->withCount('items')->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }
        if ($request->filled('tipe')) {
            $query->where('tipe', strtoupper($request->tipe));
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.pos.bom.index', [
            'bomList' => $query->paginate(10)->withQueryString(),
            'produkList' => Produk::query()
                ->where('status', true)
                ->orderBy('kode')
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'tipe' => ['required', 'in:PAKET,ADDON'],
            'status' => ['nullable', 'boolean'],
            'item_produk_id' => ['required', 'array', 'min:1'],
            'item_produk_id.*' => ['nullable', 'exists:produk,id'],
            'item_qty' => ['required', 'array', 'min:1'],
            'item_qty.*' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated) {
            $bom = Bom::query()->create([
                'kode' => $this->generateKode(),
                'nama' => $validated['nama'],
                'tipe' => $validated['tipe'],
                'status' => (bool) ($validated['status'] ?? true),
            ]);

            $this->simpanItems($bom, $validated['item_produk_id'] ?? [], $validated['item_qty'] ?? []);
        });

        return redirect()->route('paket.bom')->with('success', 'BOM berhasil ditambahkan.');
    }

    public function update(Request $request, Bom $bom)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'tipe' => ['required', 'in:PAKET,ADDON'],
            'status' => ['nullable', 'boolean'],
            'item_produk_id' => ['required', 'array', 'min:1'],
            'item_produk_id.*' => ['nullable', 'exists:produk,id'],
            'item_qty' => ['required', 'array', 'min:1'],
            'item_qty.*' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($validated, $bom) {
            $bom->update([
                'nama' => $validated['nama'],
                'tipe' => $validated['tipe'],
                'status' => (bool) ($validated['status'] ?? false),
            ]);

            $bom->items()->delete();
            $this->simpanItems($bom, $validated['item_produk_id'] ?? [], $validated['item_qty'] ?? []);
        });

        return redirect()->route('paket.bom')->with('success', 'BOM berhasil diperbarui.');
    }

    public function destroy(Bom $bom)
    {
        $bom->delete();
        return redirect()->route('paket.bom')->with('success', 'BOM berhasil dihapus.');
    }

    private function generateKode(): string
    {
        $prefix = 'BOM-' . now()->format('ymd') . '-';
        $last = Bom::query()->where('kode', 'like', $prefix . '%')->orderByDesc('id')->value('kode');
        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function simpanItems(Bom $bom, array $produkIds, array $qtys): void
    {
        $created = 0;
        foreach ($produkIds as $idx => $produkId) {
            $qty = (float) ($qtys[$idx] ?? 0);
            if ($produkId && $qty > 0) {
                BomItem::query()->create([
                    'bom_id' => $bom->id,
                    'produk_id' => $produkId,
                    'qty' => $qty,
                ]);
                $created++;
            }
        }

        if ($created === 0) {
            abort(422, 'Minimal 1 item BOM wajib diisi.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Bom;
use App\Models\KategoriAddon;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function index(Request $request)
    {
        $query = Addon::query()->with('kategoriAddon')->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }
        if ($request->filled('kategori_addon_id')) {
            $query->where('kategori_addon_id', $request->kategori_addon_id);
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.pos.addon.index', [
            'addonList' => $query->paginate(10)->withQueryString(),
            'kategoriAddon' => KategoriAddon::query()->where('status', true)->orderBy('nama')->get(),
            'bomAddon' => Bom::query()->where('tipe', 'ADDON')->where('status', true)->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kategori_addon_id' => ['nullable', 'exists:kategori_addon,id'],
            'bom_id' => ['nullable', 'exists:bom,id'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        Addon::query()->create([
            'kode' => $this->generateKode(),
            'nama' => $validated['nama'],
            'kategori_addon_id' => $validated['kategori_addon_id'] ?? null,
            'bom_id' => $validated['bom_id'] ?? null,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        return redirect()->route('paket.addon')->with('success', 'Add on berhasil ditambahkan.');
    }

    public function update(Request $request, Addon $addon)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kategori_addon_id' => ['nullable', 'exists:kategori_addon,id'],
            'bom_id' => ['nullable', 'exists:bom,id'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        $addon->update([
            'nama' => $validated['nama'],
            'kategori_addon_id' => $validated['kategori_addon_id'] ?? null,
            'bom_id' => $validated['bom_id'] ?? null,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()->route('paket.addon')->with('success', 'Add on berhasil diperbarui.');
    }

    public function destroy(Addon $addon)
    {
        $addon->delete();
        return redirect()->route('paket.addon')->with('success', 'Add on berhasil dihapus.');
    }

    private function generateKode(): string
    {
        $prefix = 'ADN-' . now()->format('ymd') . '-';
        $last = Addon::query()->where('kode', 'like', $prefix . '%')->orderByDesc('id')->value('kode');
        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

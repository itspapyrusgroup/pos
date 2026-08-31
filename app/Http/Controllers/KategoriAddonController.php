<?php

namespace App\Http\Controllers;

use App\Models\KategoriAddon;
use Illuminate\Http\Request;

class KategoriAddonController extends Controller
{
    public function index(Request $request)
    {
        $query = KategoriAddon::query()->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.pos.kategori_addon.index', [
            'kategoriAddon' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_addon,nama'],
            'status' => ['nullable', 'boolean'],
        ]);

        KategoriAddon::query()->create([
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        return redirect()->route('paket.kategori-addon')->with('success', 'Kategori add on berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriAddon $kategoriAddon)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_addon,nama,' . $kategoriAddon->id],
            'status' => ['nullable', 'boolean'],
        ]);

        $kategoriAddon->update([
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()->route('paket.kategori-addon')->with('success', 'Kategori add on berhasil diperbarui.');
    }

    public function destroy(KategoriAddon $kategoriAddon)
    {
        $kategoriAddon->delete();
        return redirect()->route('paket.kategori-addon')->with('success', 'Kategori add on berhasil dihapus.');
    }
}

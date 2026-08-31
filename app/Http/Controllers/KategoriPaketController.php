<?php

namespace App\Http\Controllers;

use App\Models\KategoriPaket;
use Illuminate\Http\Request;

class KategoriPaketController extends Controller
{
    public function index(Request $request)
    {
        $query = KategoriPaket::query()->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.pos.kategori_paket.index', [
            'kategoriPaket' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_paket,nama'],
            'status' => ['nullable', 'boolean'],
        ]);

        KategoriPaket::query()->create([
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        return redirect()->route('paket.kategori')->with('success', 'Kategori paket berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriPaket $kategoriPaket)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_paket,nama,' . $kategoriPaket->id],
            'status' => ['nullable', 'boolean'],
        ]);

        $kategoriPaket->update([
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()->route('paket.kategori')->with('success', 'Kategori paket berhasil diperbarui.');
    }

    public function destroy(KategoriPaket $kategoriPaket)
    {
        $kategoriPaket->delete();
        return redirect()->route('paket.kategori')->with('success', 'Kategori paket berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DivisiController extends Controller
{
    public function index(Request $request): View
    {
        $query = Divisi::query()->orderBy('id');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q): void {
                $builder->where('nama', 'like', "%{$q}%");
            });
        }

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->string('nama')->toString() . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', (bool) $request->integer('status'));
        }

        return view('pages.master.divisi.index', [
            'divisi' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:divisi,nama'],
            'status' => ['nullable', 'boolean'],
        ]);

        Divisi::create([
            'nama' => strtoupper($data['nama']),
            'status' => (bool) ($data['status'] ?? false),
        ]);

        return redirect()->route('konfigurasi.divisi')->with('success', 'Divisi berhasil ditambahkan.');
    }

    public function update(Request $request, Divisi $divisi): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('divisi', 'nama')->ignore($divisi->id)],
            'status' => ['nullable', 'boolean'],
        ]);

        $divisi->update([
            'nama' => strtoupper($data['nama']),
            'status' => (bool) ($data['status'] ?? false),
        ]);

        return redirect()->route('konfigurasi.divisi')->with('success', 'Divisi berhasil diperbarui.');
    }

    public function destroy(Divisi $divisi): RedirectResponse
    {
        if ($divisi->karyawan()->exists()) {
            return back()->with('error', 'Divisi masih dipakai karyawan, tidak bisa dihapus.');
        }

        $divisi->delete();

        return redirect()->route('konfigurasi.divisi')->with('success', 'Divisi berhasil dihapus.');
    }
}

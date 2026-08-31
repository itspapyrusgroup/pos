<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index(Request $request)
    {
        $query = Satuan::query()->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', '%' . $request->kode . '%');
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.master.persediaan.satuan.index', [
            'satuan' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:satuan,kode'],
            'nama' => ['required', 'string', 'max:100', 'unique:satuan,nama'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        Satuan::query()->create([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        return redirect()->route('persediaan.satuan')->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function update(Request $request, Satuan $satuan)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:satuan,kode,' . $satuan->id],
            'nama' => ['required', 'string', 'max:100', 'unique:satuan,nama,' . $satuan->id],
            'keterangan' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ]);

        $satuan->update([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()->route('persediaan.satuan')->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Satuan $satuan)
    {
        $satuan->delete();
        return redirect()->route('persediaan.satuan')->with('success', 'Satuan berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Pemasok;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PemasokController extends Controller
{
    public function index()
    {
        $query = Pemasok::query()->latest('id');

        if (request('nama_pemasok')) {
            $query->where('nama', 'like', '%' . request('nama_pemasok') . '%');
        }

        if (request('kategori')) {
            $query->where('kategori', request('kategori'));
        }

        if (request('status') !== null && request('status') !== '') {
            $query->where('status', request('status') === 'Active');
        }

        $pemasok = $query->paginate(10)->withQueryString();

        return view('pages.master.pembelian.pemasok.index', [
            'pemasok' => $pemasok
        ]);
    }

    public function create()
    {
        return view('pages.master.pembelian.pemasok.tambah');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['nullable', 'string', 'max:20', Rule::unique('pemasok', 'kode')],
            'credit_terms' => ['nullable', 'integer', 'min:0'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'kontak' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'catatan' => ['nullable', 'string'],
        ]);

        Pemasok::create([
            'kode' => $validated['kode'] ?: $this->generateKode(),
            'nama' => $validated['nama'],
            'kontak' => $validated['kontak'] ?? null,
            'telepon' => $validated['telepon'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'npwp' => $validated['npwp'] ?? null,
            'kategori' => $validated['kategori'] ?? 'Default',
            'credit_terms_hari' => $validated['credit_terms'] ?? 0,
            'status' => $request->boolean('status', true),
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('pemasok.index')
            ->with('success', 'Pemasok berhasil ditambahkan');
    }

    public function edit($id)
    {
        $pemasok = Pemasok::findOrFail($id);

        return view('pages.master.pembelian.pemasok.edit', compact('pemasok'));
    }

    public function update(Request $request, $id)
    {
        $pemasok = Pemasok::findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['nullable', 'string', 'max:20', Rule::unique('pemasok', 'kode')->ignore($pemasok->id)],
            'credit_terms' => ['nullable', 'integer', 'min:0'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'kontak' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'catatan' => ['nullable', 'string'],
        ]);

        $pemasok->update([
            'kode' => $validated['kode'] ?: $pemasok->kode ?: $this->generateKode(),
            'nama' => $validated['nama'],
            'kontak' => $validated['kontak'] ?? null,
            'telepon' => $validated['telepon'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'npwp' => $validated['npwp'] ?? null,
            'kategori' => $validated['kategori'] ?? 'Default',
            'credit_terms_hari' => $validated['credit_terms'] ?? 0,
            'status' => $request->boolean('status'),
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('pemasok.index')
            ->with('success', 'Pemasok berhasil diperbarui');
    }

    public function destroy($id)
    {
        $pemasok = Pemasok::findOrFail($id);
        $pemasok->delete();

        return redirect()->route('pemasok.index')
            ->with('success', 'Pemasok berhasil dihapus');
    }

    private function generateKode(): string
    {
        $lastId = (int) Pemasok::query()->max('id');
        return 'SUP' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}

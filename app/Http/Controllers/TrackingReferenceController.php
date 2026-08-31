<?php

namespace App\Http\Controllers;

use App\Models\TrackingReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrackingReferenceController extends Controller
{
    public function index(Request $request): View
    {
        $query = TrackingReference::query()->orderBy('tipe')->orderBy('urutan')->orderBy('nama');

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($q): void {
                $builder->where('kode', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%");
            });
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', strtoupper((string) $request->input('tipe')));
        }

        if ($request->filled('status')) {
            $query->where('status', (bool) $request->integer('status'));
        }

        return view('pages.master.tracking.index', [
            'tracking' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:40', 'unique:tracking_references,kode'],
            'nama' => ['required', 'string', 'max:120', 'unique:tracking_references,nama'],
            'tipe' => ['required', Rule::in(['ITEM', 'KO'])],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ]);

        TrackingReference::query()->create([
            'kode' => strtoupper((string) $data['kode']),
            'nama' => $data['nama'],
            'tipe' => strtoupper((string) $data['tipe']),
            'urutan' => (int) ($data['urutan'] ?? 0),
            'status' => (bool) ($data['status'] ?? true),
        ]);

        return redirect()->route('konfigurasi.tracking')->with('success', 'Tracking berhasil ditambahkan.');
    }

    public function update(Request $request, TrackingReference $tracking): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:40', Rule::unique('tracking_references', 'kode')->ignore($tracking->id)],
            'nama' => ['required', 'string', 'max:120', Rule::unique('tracking_references', 'nama')->ignore($tracking->id)],
            'tipe' => ['required', Rule::in(['ITEM', 'KO'])],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ]);

        $tracking->update([
            'kode' => strtoupper((string) $data['kode']),
            'nama' => $data['nama'],
            'tipe' => strtoupper((string) $data['tipe']),
            'urutan' => (int) ($data['urutan'] ?? 0),
            'status' => (bool) ($data['status'] ?? false),
        ]);

        return redirect()->route('konfigurasi.tracking')->with('success', 'Tracking berhasil diperbarui.');
    }

    public function destroy(TrackingReference $tracking): RedirectResponse
    {
        if ($tracking->kategoriProduk()->exists()) {
            return back()->with('error', 'Tracking masih dipakai golongan/kategori produk, tidak bisa dihapus.');
        }

        $tracking->delete();

        return redirect()->route('konfigurasi.tracking')->with('success', 'Tracking berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\KategoriProduk;
use App\Models\Divisi;
use App\Models\TrackingReference;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GolonganController extends Controller
{
    public function index(Request $request)
    {
        $query = KategoriProduk::query()
            ->with(['divisi:id,nama', 'trackingReference:id,nama,tipe'])
            ->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', '%' . $request->kode . '%');
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', strtoupper((string) $request->tipe));
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.master.persediaan.golongan.index', [
            'golongan' => $query->paginate(10)->withQueryString(),
            'divisiList' => Divisi::query()->where('status', true)->orderBy('nama')->get(['id', 'nama']),
            'trackingList' => TrackingReference::query()
                ->where('status', true)
                ->where('tipe', 'ITEM')
                ->orderBy('urutan')
                ->orderBy('nama')
                ->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:kategori_produk,kode'],
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_produk,nama'],
            'tipe' => ['required', 'in:BARANG,JASA'],
            'id_divisi' => ['nullable', 'exists:divisi,id'],
            'tracking_reference_id' => ['nullable', Rule::exists('tracking_references', 'id')->where('tipe', 'ITEM')],
            'status' => ['nullable', 'boolean'],
        ]);

        $golongan = KategoriProduk::query()->create([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'tipe' => $validated['tipe'],
            'id_divisi' => $validated['id_divisi'] ?? null,
            'tracking_reference_id' => $validated['tracking_reference_id'] ?? null,
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Golongan berhasil ditambahkan.',
                'data' => $golongan->load(['divisi:id,nama', 'trackingReference:id,nama']),
            ]);
        }

        return redirect()->route('persediaan.golongan')->with('success', 'Golongan berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriProduk $golongan)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:kategori_produk,kode,' . $golongan->id],
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_produk,nama,' . $golongan->id],
            'tipe' => ['required', 'in:BARANG,JASA'],
            'id_divisi' => ['nullable', 'exists:divisi,id'],
            'tracking_reference_id' => ['nullable', Rule::exists('tracking_references', 'id')->where('tipe', 'ITEM')],
            'status' => ['nullable', 'boolean'],
        ]);

        $golongan->update([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'tipe' => $validated['tipe'],
            'id_divisi' => $validated['id_divisi'] ?? null,
            'tracking_reference_id' => $validated['tracking_reference_id'] ?? null,
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Golongan berhasil diperbarui.',
                'data' => $golongan->fresh()->load(['divisi:id,nama', 'trackingReference:id,nama']),
            ]);
        }

        return redirect()->route('persediaan.golongan')->with('success', 'Golongan berhasil diperbarui.');
    }

    public function destroy(Request $request, KategoriProduk $golongan)
    {
        $golongan->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Golongan berhasil dihapus.',
            ]);
        }

        return redirect()->route('persediaan.golongan')->with('success', 'Golongan berhasil dihapus.');
    }
}

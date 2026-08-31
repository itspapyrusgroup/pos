<?php

namespace App\Http\Controllers;

use App\Models\MetodePembayaran;
use App\Models\PembayaranPembelian;
use App\Models\PembayaranPenjualan;
use Illuminate\Http\Request;

class MetodePembayaranController extends Controller
{
    public function index(Request $request)
    {
        $query = MetodePembayaran::query()->latest('id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', '%' . $request->kode . '%');
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.master.finance.metode_pembayaran.index', [
            'metodePembayaran' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:metode_pembayaran,kode'],
            'nama' => ['required', 'string', 'max:100', 'unique:metode_pembayaran,nama'],
            'status' => ['nullable', 'boolean'],
        ]);

        MetodePembayaran::query()->create([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        return redirect()->route('metode-pembayaran')->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, MetodePembayaran $metodePembayaran)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:metode_pembayaran,kode,' . $metodePembayaran->id],
            'nama' => ['required', 'string', 'max:100', 'unique:metode_pembayaran,nama,' . $metodePembayaran->id],
            'status' => ['nullable', 'boolean'],
        ]);

        $targetKode = strtoupper($validated['kode']);

        if ($metodePembayaran->kode === 'CASH' && $targetKode !== 'CASH') {
            return redirect()->route('metode-pembayaran')->withErrors([
                'kode' => 'Kode CASH tidak boleh diubah karena digunakan untuk tutup kasir.',
            ])->withInput();
        }

        if ($metodePembayaran->kode === 'CASH' && !((bool) ($validated['status'] ?? false))) {
            return redirect()->route('metode-pembayaran')->withErrors([
                'status' => 'Metode pembayaran CASH harus tetap aktif.',
            ])->withInput();
        }

        $metodePembayaran->update([
            'kode' => $targetKode,
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()->route('metode-pembayaran')->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(MetodePembayaran $metodePembayaran)
    {
        if ($metodePembayaran->kode === 'CASH') {
            return redirect()->route('metode-pembayaran')->withErrors([
                'kode' => 'Metode pembayaran CASH tidak boleh dihapus.',
            ]);
        }

        $dipakaiPenjualan = PembayaranPenjualan::query()
            ->where('metode_pembayaran_id', $metodePembayaran->id)
            ->exists();

        $dipakaiPembelian = PembayaranPembelian::query()
            ->where('metode_pembayaran_id', $metodePembayaran->id)
            ->exists();

        if ($dipakaiPenjualan || $dipakaiPembelian) {
            if ($metodePembayaran->status) {
                $metodePembayaran->update(['status' => false]);

                return redirect()->route('metode-pembayaran')
                    ->with('success', 'Metode pembayaran sudah dipakai, tidak bisa dihapus. Status diubah menjadi Non Aktif.');
            }

            return redirect()->route('metode-pembayaran')
                ->with('success', 'Metode pembayaran sudah dipakai dan tetap Non Aktif.');
        }

        $metodePembayaran->delete();

        return redirect()->route('metode-pembayaran')->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}

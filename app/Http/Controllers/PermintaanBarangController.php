<?php

namespace App\Http\Controllers;

use App\Models\PermintaanBarang;
use App\Models\Produk;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PermintaanBarangController extends Controller
{
    public function index(Request $request)
    {
        $query = PermintaanBarang::query()->with('cabang')->withCount('pesananPembelian')->latest('id');
        $this->applyCabangScope($query);

        if ($request->filled('nomor_permintaan')) {
            $query->where('nomor_permintaan', 'like', '%' . $request->nomor_permintaan . '%');
        }

        $cabangId = $this->resolveCabangFilter($request);
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_permintaan', $request->tanggal);
        }

        return view('pages.master.persediaan.permintaan.index', [
            'permintaanBarang' => $query->paginate(10)->withQueryString(),
            'cabangList' => $this->accessibleCabangQuery()->get(),
        ]);
    }

    public function create()
    {
        return view('pages.master.persediaan.permintaan.tambah', [
            'nomorPermintaan' => $this->generateNomorPermintaan(),
            'cabangList' => $this->accessibleCabangQuery()->get(),
            'produkList' => Produk::query()->where('status', true)->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_permintaan' => ['required', 'date'],
            'tanggal_dibutuhkan' => ['nullable', 'date'],
            'cabang_id' => ['required', 'exists:cabang,id'],
            'status' => ['required', 'in:DRAFT,APPROVED,PROCESSED,CANCELLED'],
            'catatan' => ['nullable', 'string'],
            'produk_id' => ['required', 'array', 'min:1'],
            'produk_id.*' => ['required', 'exists:produk,id'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'numeric', 'min:0.01'],
            'catatan_item' => ['nullable', 'array'],
            'catatan_item.*' => ['nullable', 'string'],
        ]);

        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        DB::transaction(function () use ($validated) {
            $permintaan = PermintaanBarang::query()->create([
                'nomor_permintaan' => $this->generateNomorPermintaan(),
                'tanggal_permintaan' => $validated['tanggal_permintaan'],
                'tanggal_dibutuhkan' => $validated['tanggal_dibutuhkan'] ?? null,
                'cabang_id' => $validated['cabang_id'],
                'status' => $validated['status'],
                'dibuat_oleh' => auth()->id(),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($validated['produk_id'] as $index => $produkId) {
                $permintaan->items()->create([
                    'produk_id' => $produkId,
                    'qty' => $validated['qty'][$index],
                    'catatan' => $validated['catatan_item'][$index] ?? null,
                ]);
            }
        });

        return redirect()->route('permintaan-barang.index')
            ->with('success', 'Permintaan barang berhasil ditambahkan');
    }

    public function edit(PermintaanBarang $permintaanBarang)
    {
        $this->ensureCabangAccessible((int) $permintaanBarang->cabang_id);

        if (!$this->canModify($permintaanBarang)) {
            return redirect()->route('permintaan-barang.index')
                ->with('error', 'Permintaan tidak dapat diedit karena sudah diproses ke pesanan pembelian.');
        }

        $permintaanBarang->load('items.produk');
        return view('pages.master.persediaan.permintaan.edit', [
            'permintaan' => $permintaanBarang,
            'cabangList' => $this->accessibleCabangQuery()->get(),
            'produkList' => Produk::query()->where('status', true)->orderBy('nama')->get(),
        ]);
    }

    public function update(Request $request, PermintaanBarang $permintaanBarang)
    {
        $this->ensureCabangAccessible((int) $permintaanBarang->cabang_id);

        if (!$this->canModify($permintaanBarang)) {
            return redirect()->route('permintaan-barang.index')
                ->with('error', 'Permintaan tidak dapat diubah karena sudah diproses ke pesanan pembelian.');
        }

        $validated = $request->validate([
            'tanggal_permintaan' => ['required', 'date'],
            'tanggal_dibutuhkan' => ['nullable', 'date'],
            'cabang_id' => ['required', 'exists:cabang,id'],
            'status' => ['required', 'in:DRAFT,APPROVED,PROCESSED,CANCELLED'],
            'catatan' => ['nullable', 'string'],
            'produk_id' => ['required', 'array', 'min:1'],
            'produk_id.*' => ['required', 'exists:produk,id'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'numeric', 'min:0.01'],
            'catatan_item' => ['nullable', 'array'],
            'catatan_item.*' => ['nullable', 'string'],
        ]);

        $this->ensureCabangAccessible((int) $validated['cabang_id']);

        DB::transaction(function () use ($validated, $permintaanBarang) {
            $permintaanBarang->update([
                'tanggal_permintaan' => $validated['tanggal_permintaan'],
                'tanggal_dibutuhkan' => $validated['tanggal_dibutuhkan'] ?? null,
                'cabang_id' => $validated['cabang_id'],
                'status' => $validated['status'],
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $permintaanBarang->items()->delete();

            foreach ($validated['produk_id'] as $index => $produkId) {
                $permintaanBarang->items()->create([
                    'produk_id' => $produkId,
                    'qty' => $validated['qty'][$index],
                    'catatan' => $validated['catatan_item'][$index] ?? null,
                ]);
            }
        });

        return redirect()->route('permintaan-barang.index')
            ->with('success', 'Permintaan barang berhasil diperbarui');
    }

    public function destroy(PermintaanBarang $permintaanBarang)
    {
        $this->ensureCabangAccessible((int) $permintaanBarang->cabang_id);

        if (!$this->canModify($permintaanBarang)) {
            return redirect()->route('permintaan-barang.index')
                ->with('error', 'Permintaan tidak dapat dihapus karena sudah diproses ke pesanan pembelian.');
        }

        $permintaanBarang->delete();
        return redirect()->route('permintaan-barang.index')
            ->with('success', 'Permintaan barang berhasil dihapus');
    }

    public function show(PermintaanBarang $permintaanBarang)
    {
        $this->ensureCabangAccessible((int) $permintaanBarang->cabang_id);
        $permintaanBarang->load(['cabang.perusahaan', 'items.produk', 'pesananPembelian', 'pembuat']);
        return view('pages.master.persediaan.permintaan.show', [
            'permintaan' => $permintaanBarang,
        ]);
    }

    public function pdf(PermintaanBarang $permintaanBarang)
    {
        $this->ensureCabangAccessible((int) $permintaanBarang->cabang_id);
        $permintaanBarang->load(['cabang.perusahaan', 'items.produk', 'pesananPembelian', 'pembuat']);
        $pdf = Pdf::loadView('pdf.pembelian.permintaan', [
            'permintaan' => $permintaanBarang,
        ]);

        return $pdf->download($permintaanBarang->nomor_permintaan . '.pdf');
    }

    private function generateNomorPermintaan(): string
    {
        $prefix = 'PR' . now()->format('Ymd');
        $last = PermintaanBarang::query()
            ->where('nomor_permintaan', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_permintaan');

        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function canModify(PermintaanBarang $permintaanBarang): bool
    {
        if ($permintaanBarang->status === 'PROCESSED') {
            return false;
        }

        return !$permintaanBarang->pesananPembelian()->exists();
    }
}

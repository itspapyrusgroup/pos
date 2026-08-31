<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Controller;
use App\Models\KartuStok;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangItem;
use App\Models\PesananPembelian;
use App\Models\StokCabang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenerimaanBarangController extends Controller
{
    public function index(Request $request)
    {
        $query = PenerimaanBarang::query()->with(['pesananPembelian.pemasok', 'cabang'])->latest('id');

        if ($request->filled('nomor_penerimaan')) {
            $query->where('nomor_penerimaan', 'like', '%' . $request->nomor_penerimaan . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('pages.master.pembelian.penerimaan.index', [
            'penerimaanList' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function create()
    {
        $poList = PesananPembelian::query()
            ->with(['pemasok', 'items.produk'])
            ->whereIn('status', ['ORDERED', 'PARTIAL_RECEIVED'])
            ->latest('id')
            ->get();

        $poPayload = $poList->mapWithKeys(function ($po) {
            $items = $po->items->map(function ($item) {
                $received = (float) PenerimaanBarangItem::query()
                    ->where('pesanan_pembelian_item_id', $item->id)
                    ->sum('qty_terima');

                return [
                    'id' => $item->id,
                    'produk_id' => $item->produk_id,
                    'produk_nama' => $item->produk->nama ?? '-',
                    'qty_po' => (float) $item->qty,
                    'qty_sisa' => max((float) $item->qty - $received, 0),
                ];
            })->values();

            return [$po->id => $items];
        });

        return view('pages.master.pembelian.penerimaan.create', [
            'nomorPenerimaan' => $this->generateNomorPenerimaan(),
            'poList' => $poList,
            'poPayload' => $poPayload,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pesanan_pembelian_id' => ['required', 'exists:pesanan_pembelian,id'],
            'tanggal_penerimaan' => ['required', 'date'],
            'nomor_surat_jalan' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string'],
            'po_item_id' => ['required', 'array'],
            'po_item_id.*' => ['required', 'exists:pesanan_pembelian_item,id'],
            'qty_terima' => ['required', 'array'],
            'qty_terima.*' => ['required', 'numeric', 'min:0'],
            'catatan_item' => ['nullable', 'array'],
            'catatan_item.*' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $po = PesananPembelian::query()->with('items')->findOrFail($validated['pesanan_pembelian_id']);

            $penerimaan = PenerimaanBarang::query()->create([
                'nomor_penerimaan' => $this->generateNomorPenerimaan(),
                'nomor_surat_jalan' => $validated['nomor_surat_jalan'] ?? null,
                'pesanan_pembelian_id' => $po->id,
                'cabang_id' => $po->cabang_id,
                'tanggal_penerimaan' => $validated['tanggal_penerimaan'],
                'status' => 'POSTED', // Langsung POSTED saat simpan
                'dibuat_oleh' => auth()->id(),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($validated['po_item_id'] as $index => $poItemId) {
                $qtyTerima = (float) ($validated['qty_terima'][$index] ?? 0);
                if ($qtyTerima <= 0) {
                    continue;
                }

                $poItem = $po->items->firstWhere('id', $poItemId);
                if (!$poItem) {
                    continue;
                }

                $sudahDiterima = (float) PenerimaanBarangItem::query()
                    ->where('pesanan_pembelian_item_id', $poItem->id)
                    ->sum('qty_terima');

                $sisa = (float) $poItem->qty - $sudahDiterima;
                if ($qtyTerima > $sisa) {
                    $qtyTerima = $sisa;
                }

                if ($qtyTerima <= 0) {
                    continue;
                }

                $penerimaanItem = $penerimaan->items()->create([
                    'pesanan_pembelian_item_id' => $poItem->id,
                    'produk_id' => $poItem->produk_id,
                    'qty_terima' => $qtyTerima,
                    'catatan' => $validated['catatan_item'][$index] ?? null,
                ]);

                if ($penerimaan->status === 'POSTED') {
                    $this->tambahStokPembelian(
                        (int) $penerimaanItem->produk_id,
                        (int) $penerimaan->cabang_id,
                        (float) $penerimaanItem->qty_terima,
                        (int) $penerimaan->id
                    );
                }
            }

            $this->refreshPoStatus($po->id);
        });

        return redirect()->route('pembelian.penerimaan')->with('success', 'Penerimaan barang berhasil disimpan.');
    }

    public function show(PenerimaanBarang $penerimaanBarang)
    {
        $penerimaanBarang->load([
            'cabang.perusahaan',
            'pesananPembelian.pemasok',
            'items.produk',
            'items.pesananPembelianItem',
            'pembuat',
        ]);

        $penerimaanBarang->loadSum('items', 'qty_terima');

        return view('pages.master.pembelian.penerimaan.show', [
            'penerimaan' => $penerimaanBarang,
        ]);
    }

    public function pdf(PenerimaanBarang $penerimaanBarang)
    {
        $penerimaanBarang->load([
            'cabang.perusahaan',
            'pesananPembelian.pemasok',
            'items.produk',
            'items.pesananPembelianItem',
            'pembuat',
        ]);

        $pdf = Pdf::loadView('pdf.pembelian.penerimaan', [
            'penerimaan' => $penerimaanBarang,
        ]);

        return $pdf->download($penerimaanBarang->nomor_penerimaan . '.pdf');
    }

    private function refreshPoStatus(int $poId): void
    {
        $po = PesananPembelian::query()->with('items')->findOrFail($poId);

        $totalQty = (float) $po->items->sum('qty');
        $received = 0.0;
        foreach ($po->items as $item) {
            $received += (float) PenerimaanBarangItem::query()
                ->where('pesanan_pembelian_item_id', $item->id)
                ->sum('qty_terima');
        }

        $status = 'ORDERED';
        if ($received > 0 && $received < $totalQty) {
            $status = 'PARTIAL_RECEIVED';
        } elseif ($received >= $totalQty && $totalQty > 0) {
            $status = 'RECEIVED';
        }

        $po->update(['status' => $status]);
    }

    private function generateNomorPenerimaan(): string
    {
        $prefix = 'GRN' . now()->format('Ymd');
        $last = PenerimaanBarang::query()
            ->where('nomor_penerimaan', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_penerimaan');

        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function tambahStokPembelian(int $produkId, int $cabangId, float $qtyMasuk, int $referensiId): void
    {
        if ($qtyMasuk <= 0) {
            return;
        }

        $stok = StokCabang::query()->firstOrCreate(
            ['produk_id' => $produkId, 'cabang_id' => $cabangId],
            ['qty' => 0]
        );

        $saldoAkhir = (float) $stok->qty + $qtyMasuk;
        if ($saldoAkhir < 0) {
            throw ValidationException::withMessages([
                'qty_terima' => ['Saldo stok menjadi negatif.'],
            ]);
        }

        $stok->update(['qty' => $saldoAkhir]);

        KartuStok::query()->create([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tipe_mutasi' => 'PEMBELIAN',
            'referensi_tipe' => 'penerimaan_barang',
            'referensi_id' => $referensiId,
            'qty_masuk' => $qtyMasuk,
            'qty_keluar' => 0,
            'saldo_akhir' => $saldoAkhir,
            'catatan' => 'Tambah stok dari penerimaan barang pembelian',
            'tanggal_mutasi' => now(),
        ]);
    }
}

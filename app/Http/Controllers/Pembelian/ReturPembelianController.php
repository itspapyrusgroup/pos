<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Controller;
use App\Models\KartuStok;
use App\Models\PenerimaanBarang;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianItem;
use App\Models\StokCabang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturPembelianController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturPembelian::query()
            ->with(['penerimaanBarang', 'pesananPembelian', 'pemasok'])
            ->latest('id');

        if ($request->filled('nomor_retur')) {
            $query->where('nomor_retur', 'like', '%' . $request->nomor_retur . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('pages.master.pembelian.retur.index', [
            'returList' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function create()
    {
        $penerimaanList = PenerimaanBarang::query()
            ->with(['pesananPembelian.pemasok', 'items.produk'])
            ->where('status', 'POSTED')
            ->latest('id')
            ->get();

        $penerimaanPayload = $penerimaanList->mapWithKeys(function ($penerimaan) {
            $items = $penerimaan->items->map(function ($item) {
                $qtyRetur = (float) ReturPembelianItem::query()
                    ->where('penerimaan_barang_item_id', $item->id)
                    ->sum('qty');

                return [
                    'id' => $item->id,
                    'produk_id' => $item->produk_id,
                    'produk_nama' => $item->produk->nama ?? '-',
                    'qty_terima' => (float) $item->qty_terima,
                    'qty_sisa_retur' => max((float) $item->qty_terima - $qtyRetur, 0),
                ];
            })->values()->toArray();

            return [$penerimaan->id => $items];
        })->toArray();

        return view('pages.master.pembelian.retur.create', [
            'nomorRetur' => $this->generateNomorRetur(),
            'penerimaanList' => $penerimaanList,
            'penerimaanPayload' => $penerimaanPayload,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'penerimaan_barang_id' => ['required', 'exists:penerimaan_barang,id'],
            'tanggal_retur' => ['required', 'date'],
            'status' => ['required', 'in:DRAFT,POSTED'],
            'catatan' => ['nullable', 'string'],
            'penerimaan_barang_item_id' => ['required', 'array'],
            'penerimaan_barang_item_id.*' => ['required', 'exists:penerimaan_barang_item,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'numeric', 'min:0'],
            'alasan_retur' => ['nullable', 'array'],
            'alasan_retur.*' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($validated) {
            $penerimaan = PenerimaanBarang::query()
                ->with(['pesananPembelian', 'items'])
                ->findOrFail($validated['penerimaan_barang_id']);

            $retur = ReturPembelian::query()->create([
                'nomor_retur' => $this->generateNomorRetur(),
                'penerimaan_barang_id' => $penerimaan->id,
                'pesanan_pembelian_id' => $penerimaan->pesanan_pembelian_id,
                'pemasok_id' => $penerimaan->pesananPembelian->pemasok_id,
                'cabang_id' => $penerimaan->cabang_id,
                'tanggal_retur' => $validated['tanggal_retur'],
                'status' => $validated['status'],
                'dibuat_oleh' => auth()->id(),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($validated['penerimaan_barang_item_id'] as $index => $penerimaanItemId) {
                $qtyRetur = (float) ($validated['qty'][$index] ?? 0);
                if ($qtyRetur <= 0) {
                    continue;
                }

                $penerimaanItem = $penerimaan->items->firstWhere('id', $penerimaanItemId);
                if (!$penerimaanItem) {
                    continue;
                }

                $qtyReturSebelumnya = (float) ReturPembelianItem::query()
                    ->where('penerimaan_barang_item_id', $penerimaanItem->id)
                    ->sum('qty');

                $sisaRetur = max((float) $penerimaanItem->qty_terima - $qtyReturSebelumnya, 0);
                if ($qtyRetur > $sisaRetur) {
                    $qtyRetur = $sisaRetur;
                }

                if ($qtyRetur <= 0) {
                    continue;
                }

                $returItem = $retur->items()->create([
                    'penerimaan_barang_item_id' => $penerimaanItem->id,
                    'produk_id' => $penerimaanItem->produk_id,
                    'qty' => $qtyRetur,
                    'alasan_retur' => $validated['alasan_retur'][$index] ?? null,
                ]);

                if ($retur->status === 'POSTED') {
                    $this->kurangiStokRetur(
                        (int) $returItem->produk_id,
                        (int) $retur->cabang_id,
                        (float) $returItem->qty,
                        (int) $retur->id
                    );
                }
            }
        });

        return redirect()->route('pembelian.retur')->with('success', 'Retur pembelian berhasil disimpan.');
    }

    public function show(ReturPembelian $returPembelian)
    {
        $returPembelian->load([
            'cabang.perusahaan',
            'pemasok',
            'pesananPembelian',
            'penerimaanBarang',
            'items.produk',
            'items.penerimaanBarangItem',
            'pembuat',
        ]);

        return view('pages.master.pembelian.retur.show', [
            'retur' => $returPembelian,
        ]);
    }

    public function pdf(ReturPembelian $returPembelian)
    {
        $returPembelian->load([
            'cabang.perusahaan',
            'pemasok',
            'pesananPembelian',
            'penerimaanBarang',
            'items.produk',
            'items.penerimaanBarangItem',
            'pembuat',
        ]);

        $pdf = Pdf::loadView('pdf.pembelian.retur', [
            'retur' => $returPembelian,
        ]);

        return $pdf->download($returPembelian->nomor_retur . '.pdf');
    }

    private function kurangiStokRetur(int $produkId, int $cabangId, float $qtyKeluar, int $referensiId): void
    {
        if ($qtyKeluar <= 0) {
            return;
        }

        $stok = StokCabang::query()->firstOrCreate(
            ['produk_id' => $produkId, 'cabang_id' => $cabangId],
            ['qty' => 0]
        );

        $allowNegative = (bool) config('pos.izinkan_stok_negatif', false);
        $saldoAkhir = (float) $stok->qty - $qtyKeluar;

        if (!$allowNegative && $saldoAkhir < 0) {
            throw ValidationException::withMessages([
                'qty' => ['Stok tidak mencukupi untuk proses retur.'],
            ]);
        }

        $stok->update(['qty' => $saldoAkhir]);

        KartuStok::query()->create([
            'produk_id' => $produkId,
            'cabang_id' => $cabangId,
            'tipe_mutasi' => 'RETUR',
            'referensi_tipe' => 'retur_pembelian',
            'referensi_id' => $referensiId,
            'qty_masuk' => 0,
            'qty_keluar' => $qtyKeluar,
            'saldo_akhir' => $saldoAkhir,
            'catatan' => 'Pengurangan stok dari retur pembelian',
            'tanggal_mutasi' => now(),
        ]);
    }

    private function generateNomorRetur(): string
    {
        $prefix = 'RTB' . now()->format('Ymd');
        $last = ReturPembelian::query()
            ->where('nomor_retur', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_retur');

        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

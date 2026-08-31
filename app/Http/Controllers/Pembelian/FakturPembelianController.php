<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Controller;
use App\Models\FakturPembelian;
use App\Models\PesananPembelian;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FakturPembelianController extends Controller
{
    public function index(Request $request)
    {
        $query = FakturPembelian::query()->with(['pemasok', 'cabang', 'pesananPembelian'])->latest('id');

        if ($request->filled('nomor_faktur')) {
            $query->where('nomor_faktur', 'like', '%' . $request->nomor_faktur . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('pages.master.pembelian.faktur.index', [
            'fakturList' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function create()
    {
        $poList = PesananPembelian::query()
            ->with(['pemasok', 'items.produk'])
            ->whereIn('status', ['ORDERED', 'PARTIAL_RECEIVED', 'RECEIVED'])
            ->latest('id')
            ->get();

        $poPayload = $poList->mapWithKeys(function ($po) {
            return [
                $po->id => $po->items->map(function ($item) {
                    return [
                        'produk_id' => $item->produk_id,
                        'produk_nama' => $item->produk->nama ?? '-',
                        'qty' => (float) $item->qty,
                        'harga' => (float) $item->harga,
                    ];
                })->values()->toArray(),
            ];
        })->toArray();

        return view('pages.master.pembelian.faktur.create', [
            'nomorFaktur' => $this->generateNomorFaktur(),
            'poList' => $poList,
            'poPayload' => $poPayload,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pesanan_pembelian_id' => ['required', 'exists:pesanan_pembelian,id'],
            'tanggal_faktur' => ['required', 'date'],
            'jatuh_tempo' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
            'produk_id' => ['required', 'array', 'min:1'],
            'produk_id.*' => ['required', 'exists:produk,id'],
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'numeric', 'min:0.01'],
            'harga' => ['required', 'array', 'min:1'],
            'harga.*' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            $po = PesananPembelian::query()->findOrFail($validated['pesanan_pembelian_id']);
            $total = 0;

            $faktur = FakturPembelian::query()->create([
                'nomor_faktur' => $this->generateNomorFaktur(),
                'pesanan_pembelian_id' => $po->id,
                'pemasok_id' => $po->pemasok_id,
                'cabang_id' => $po->cabang_id,
                'tanggal_faktur' => $validated['tanggal_faktur'],
                'jatuh_tempo' => $validated['jatuh_tempo'] ?? null,
                'status' => 'DRAFT',
                'dibuat_oleh' => auth()->id(),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            foreach ($validated['produk_id'] as $index => $produkId) {
                $qty = (float) $validated['qty'][$index];
                $harga = (float) $validated['harga'][$index];
                $subtotal = $qty * $harga;
                $total += $subtotal;

                $faktur->items()->create([
                    'produk_id' => $produkId,
                    'qty' => $qty,
                    'harga' => $harga,
                    'subtotal' => $subtotal,
                ]);
            }

            $faktur->update([
                'total' => $total,
                'status' => 'DRAFT',
            ]);
        });

        return redirect()->route('pembelian.faktur')->with('success', 'Faktur pembelian berhasil disimpan.');
    }

    public function show(FakturPembelian $fakturPembelian)
    {
        $fakturPembelian->load(['pemasok', 'cabang.perusahaan', 'pesananPembelian', 'items.produk', 'pembayaran.metodePembayaran', 'pembuat']);
        return view('pages.master.pembelian.faktur.show', [
            'faktur' => $fakturPembelian,
        ]);
    }

    public function pdf(FakturPembelian $fakturPembelian)
    {
        $fakturPembelian->load(['pemasok', 'cabang.perusahaan', 'pesananPembelian', 'items.produk', 'pembayaran.metodePembayaran', 'pembuat']);
        $pdf = Pdf::loadView('pdf.pembelian.faktur', [
            'faktur' => $fakturPembelian,
        ]);

        return $pdf->download($fakturPembelian->nomor_faktur . '.pdf');
    }

    private function generateNomorFaktur(): string
    {
        $prefix = 'FPB' . now()->format('Ymd');
        $last = FakturPembelian::query()
            ->where('nomor_faktur', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_faktur');

        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

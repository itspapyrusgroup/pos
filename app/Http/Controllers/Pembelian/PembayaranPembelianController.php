<?php

namespace App\Http\Controllers\Pembelian;

use App\Http\Controllers\Controller;
use App\Models\FakturPembelian;
use App\Models\MetodePembayaran;
use App\Models\PembayaranPembelian;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranPembelianController extends Controller
{
    public function index(Request $request)
    {
        $query = PembayaranPembelian::query()
            ->with(['fakturPembelian.pemasok', 'metodePembayaran'])
            ->latest('id');

        if ($request->filled('nomor_pembayaran')) {
            $query->where('nomor_pembayaran', 'like', '%' . $request->nomor_pembayaran . '%');
        }

        return view('pages.master.pembelian.pembayaran.index', [
            'pembayaranList' => $query->paginate(10)->withQueryString(),
            'outstandingFaktur' => FakturPembelian::query()
                ->with('pemasok')
                ->whereRaw('total > dibayar')
                ->orderByDesc('id')
                ->get(),
            'metodeList' => MetodePembayaran::query()->where('status', true)->orderBy('nama')->get(),
            'nomorPembayaran' => $this->generateNomorPembayaran(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'faktur_pembelian_id' => ['required', 'exists:faktur_pembelian,id'],
            'metode_pembayaran_id' => ['nullable', 'exists:metode_pembayaran,id'],
            'tanggal_bayar' => ['required', 'date'],
            'nominal' => ['required', 'numeric', 'min:0.01'],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $faktur = FakturPembelian::query()->lockForUpdate()->findOrFail($validated['faktur_pembelian_id']);
            $sisa = (float) $faktur->total - (float) $faktur->dibayar;
            $nominal = min((float) $validated['nominal'], $sisa);

            PembayaranPembelian::query()->create([
                'nomor_pembayaran' => $this->generateNomorPembayaran(),
                'faktur_pembelian_id' => $faktur->id,
                'metode_pembayaran_id' => $validated['metode_pembayaran_id'] ?? null,
                'tanggal_bayar' => $validated['tanggal_bayar'],
                'nominal' => $nominal,
                'dibuat_oleh' => auth()->id(),
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $dibayarBaru = (float) $faktur->dibayar + $nominal;
            $status = 'PARTIAL';
            if ($dibayarBaru <= 0) {
                $status = 'DRAFT';
            } elseif ($dibayarBaru >= (float) $faktur->total) {
                $status = 'PAID';
            }

            $faktur->update([
                'dibayar' => $dibayarBaru,
                'status' => $status,
            ]);
        });

        return redirect()->route('pembelian.pembayaran')->with('success', 'Pembayaran pembelian berhasil disimpan.');
    }

    public function show(PembayaranPembelian $pembayaranPembelian)
    {
        $pembayaranPembelian->load(['metodePembayaran', 'fakturPembelian.pemasok', 'fakturPembelian.cabang.perusahaan', 'pembuat']);
        return view('pages.master.pembelian.pembayaran.show', [
            'pembayaran' => $pembayaranPembelian,
        ]);
    }

    public function pdf(PembayaranPembelian $pembayaranPembelian)
    {
        $pembayaranPembelian->load(['metodePembayaran', 'fakturPembelian.pemasok', 'fakturPembelian.cabang.perusahaan', 'pembuat']);
        $pdf = Pdf::loadView('pdf.pembelian.pembayaran', [
            'pembayaran' => $pembayaranPembelian,
        ]);

        return $pdf->download($pembayaranPembelian->nomor_pembayaran . '.pdf');
    }

    private function generateNomorPembayaran(): string
    {
        $prefix = 'BYR' . now()->format('Ymd');
        $last = PembayaranPembelian::query()
            ->where('nomor_pembayaran', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('nomor_pembayaran');

        $next = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FakturPembelian;
use App\Models\MetodePembayaran;
use App\Models\PembayaranPembelian;
use App\Models\Pemasok;
use App\Models\PenerimaanBarang;
use App\Models\PesananPembelian;
use App\Models\ReturPembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanPembelianController extends Controller
{
    public function pesanan(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'pemasok_id' => ['nullable', 'exists:pemasok,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'nomor' => ['nullable', 'string', 'max:50'],
        ]);

        [$dateFrom, $dateTo] = $this->resolvePeriod($validated['date_from'] ?? null, $validated['date_to'] ?? null);
        $cabangId = $this->resolveCabangFilter($request);
        $pemasokId = isset($validated['pemasok_id']) ? (int) $validated['pemasok_id'] : null;
        $status = (string) ($validated['status'] ?? '');
        $nomor = trim((string) ($validated['nomor'] ?? ''));

        $query = PesananPembelian::query()
            ->with(['pemasok', 'cabang'])
            ->withCount('items')
            ->withSum('items as total_po', 'subtotal')
            ->whereDate('tanggal_po', '>=', $dateFrom)
            ->whereDate('tanggal_po', '<=', $dateTo);
        $this->applyCabangScope($query);

        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }
        if ($pemasokId) {
            $query->where('pemasok_id', $pemasokId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($nomor !== '') {
            $query->where('nomor_po', 'like', '%' . $nomor . '%');
        }

        $summaryCount = (clone $query)->count();
        $summaryNominal = (float) (clone $query)->get()->sum(fn ($row) => (float) ($row->total_po ?? 0));

        return view('pages.laporan.pembelian.pesanan', [
            'rows' => $query->orderByDesc('tanggal_po')->orderByDesc('id')->paginate(20)->withQueryString(),
            'summary' => [
                'jumlah' => $summaryCount,
                'total_nominal' => $summaryNominal,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'pemasok_id' => $pemasokId,
                'status' => $status,
                'nomor' => $nomor,
            ],
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'pemasokList' => Pemasok::query()->orderBy('nama')->get(['id', 'nama']),
            'statusOptions' => ['DRAFT', 'ORDERED', 'PARTIAL_RECEIVED', 'RECEIVED', 'CLOSED'],
        ]);
    }

    public function penerimaan(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'pemasok_id' => ['nullable', 'exists:pemasok,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'nomor' => ['nullable', 'string', 'max:50'],
        ]);

        [$dateFrom, $dateTo] = $this->resolvePeriod($validated['date_from'] ?? null, $validated['date_to'] ?? null);
        $cabangId = $this->resolveCabangFilter($request);
        $pemasokId = isset($validated['pemasok_id']) ? (int) $validated['pemasok_id'] : null;
        $status = (string) ($validated['status'] ?? '');
        $nomor = trim((string) ($validated['nomor'] ?? ''));

        $query = PenerimaanBarang::query()
            ->with(['pesananPembelian.pemasok', 'cabang'])
            ->withCount('items')
            ->withSum('items as total_qty', 'qty_terima')
            ->whereDate('tanggal_penerimaan', '>=', $dateFrom)
            ->whereDate('tanggal_penerimaan', '<=', $dateTo);
        $this->applyCabangScope($query);

        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }
        if ($pemasokId) {
            $query->whereHas('pesananPembelian', function ($q) use ($pemasokId) {
                $q->where('pemasok_id', $pemasokId);
            });
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($nomor !== '') {
            $query->where('nomor_penerimaan', 'like', '%' . $nomor . '%');
        }

        $summaryCount = (clone $query)->count();
        $summaryQty = (float) (clone $query)->get()->sum(fn ($row) => (float) ($row->total_qty ?? 0));

        return view('pages.laporan.pembelian.penerimaan', [
            'rows' => $query->orderByDesc('tanggal_penerimaan')->orderByDesc('id')->paginate(20)->withQueryString(),
            'summary' => [
                'jumlah' => $summaryCount,
                'total_qty' => $summaryQty,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'pemasok_id' => $pemasokId,
                'status' => $status,
                'nomor' => $nomor,
            ],
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'pemasokList' => Pemasok::query()->orderBy('nama')->get(['id', 'nama']),
            'statusOptions' => ['DRAFT', 'POSTED'],
        ]);
    }

    public function faktur(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'pemasok_id' => ['nullable', 'exists:pemasok,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'nomor' => ['nullable', 'string', 'max:50'],
        ]);

        [$dateFrom, $dateTo] = $this->resolvePeriod($validated['date_from'] ?? null, $validated['date_to'] ?? null);
        $cabangId = $this->resolveCabangFilter($request);
        $pemasokId = isset($validated['pemasok_id']) ? (int) $validated['pemasok_id'] : null;
        $status = (string) ($validated['status'] ?? '');
        $nomor = trim((string) ($validated['nomor'] ?? ''));

        $query = FakturPembelian::query()
            ->with(['pemasok', 'cabang', 'pesananPembelian'])
            ->whereDate('tanggal_faktur', '>=', $dateFrom)
            ->whereDate('tanggal_faktur', '<=', $dateTo);
        $this->applyCabangScope($query);

        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }
        if ($pemasokId) {
            $query->where('pemasok_id', $pemasokId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($nomor !== '') {
            $query->where('nomor_faktur', 'like', '%' . $nomor . '%');
        }

        $summaryCount = (clone $query)->count();
        $summaryTotal = (float) (clone $query)->sum('total');
        $summaryDibayar = (float) (clone $query)->sum('dibayar');

        return view('pages.laporan.pembelian.faktur', [
            'rows' => $query->orderByDesc('tanggal_faktur')->orderByDesc('id')->paginate(20)->withQueryString(),
            'summary' => [
                'jumlah' => $summaryCount,
                'total' => $summaryTotal,
                'dibayar' => $summaryDibayar,
                'sisa' => max($summaryTotal - $summaryDibayar, 0),
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'pemasok_id' => $pemasokId,
                'status' => $status,
                'nomor' => $nomor,
            ],
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'pemasokList' => Pemasok::query()->orderBy('nama')->get(['id', 'nama']),
            'statusOptions' => ['DRAFT', 'PARTIAL', 'PAID'],
        ]);
    }

    public function pembayaran(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'pemasok_id' => ['nullable', 'exists:pemasok,id'],
            'metode_pembayaran_id' => ['nullable', 'exists:metode_pembayaran,id'],
            'nomor' => ['nullable', 'string', 'max:50'],
        ]);

        [$dateFrom, $dateTo] = $this->resolvePeriod($validated['date_from'] ?? null, $validated['date_to'] ?? null);
        $cabangId = $this->resolveCabangFilter($request);
        $pemasokId = isset($validated['pemasok_id']) ? (int) $validated['pemasok_id'] : null;
        $metodeId = isset($validated['metode_pembayaran_id']) ? (int) $validated['metode_pembayaran_id'] : null;
        $nomor = trim((string) ($validated['nomor'] ?? ''));

        $query = PembayaranPembelian::query()
            ->with(['fakturPembelian.pemasok', 'fakturPembelian.cabang', 'metodePembayaran'])
            ->whereDate('tanggal_bayar', '>=', $dateFrom)
            ->whereDate('tanggal_bayar', '<=', $dateTo);

        $allowedCabangIds = $this->accessibleCabangIds();
        if (!empty($allowedCabangIds)) {
            $query->whereHas('fakturPembelian', function ($q) use ($allowedCabangIds) {
                $q->whereIn('cabang_id', $allowedCabangIds);
            });
        }

        if ($cabangId) {
            $query->whereHas('fakturPembelian', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        if ($pemasokId) {
            $query->whereHas('fakturPembelian', function ($q) use ($pemasokId) {
                $q->where('pemasok_id', $pemasokId);
            });
        }
        if ($metodeId) {
            $query->where('metode_pembayaran_id', $metodeId);
        }
        if ($nomor !== '') {
            $query->where('nomor_pembayaran', 'like', '%' . $nomor . '%');
        }

        $summaryCount = (clone $query)->count();
        $summaryNominal = (float) (clone $query)->sum('nominal');

        return view('pages.laporan.pembelian.pembayaran', [
            'rows' => $query->orderByDesc('tanggal_bayar')->orderByDesc('id')->paginate(20)->withQueryString(),
            'summary' => [
                'jumlah' => $summaryCount,
                'total_nominal' => $summaryNominal,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'pemasok_id' => $pemasokId,
                'metode_pembayaran_id' => $metodeId,
                'nomor' => $nomor,
            ],
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'pemasokList' => Pemasok::query()->orderBy('nama')->get(['id', 'nama']),
            'metodeList' => MetodePembayaran::query()->where('status', true)->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function retur(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'pemasok_id' => ['nullable', 'exists:pemasok,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'nomor' => ['nullable', 'string', 'max:50'],
        ]);

        [$dateFrom, $dateTo] = $this->resolvePeriod($validated['date_from'] ?? null, $validated['date_to'] ?? null);
        $cabangId = $this->resolveCabangFilter($request);
        $pemasokId = isset($validated['pemasok_id']) ? (int) $validated['pemasok_id'] : null;
        $status = (string) ($validated['status'] ?? '');
        $nomor = trim((string) ($validated['nomor'] ?? ''));

        $query = ReturPembelian::query()
            ->with(['pemasok', 'cabang', 'pesananPembelian', 'penerimaanBarang'])
            ->withCount('items')
            ->withSum('items as total_qty', 'qty')
            ->whereDate('tanggal_retur', '>=', $dateFrom)
            ->whereDate('tanggal_retur', '<=', $dateTo);
        $this->applyCabangScope($query);

        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }
        if ($pemasokId) {
            $query->where('pemasok_id', $pemasokId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($nomor !== '') {
            $query->where('nomor_retur', 'like', '%' . $nomor . '%');
        }

        $summaryCount = (clone $query)->count();
        $summaryQty = (float) (clone $query)->get()->sum(fn ($row) => (float) ($row->total_qty ?? 0));

        return view('pages.laporan.pembelian.retur', [
            'rows' => $query->orderByDesc('tanggal_retur')->orderByDesc('id')->paginate(20)->withQueryString(),
            'summary' => [
                'jumlah' => $summaryCount,
                'total_qty' => $summaryQty,
            ],
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'pemasok_id' => $pemasokId,
                'status' => $status,
                'nomor' => $nomor,
            ],
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'pemasokList' => Pemasok::query()->orderBy('nama')->get(['id', 'nama']),
            'statusOptions' => ['DRAFT', 'POSTED'],
        ]);
    }

    private function resolvePeriod(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ?: now()->startOfMonth()->toDateString();
        $to = $dateTo ?: now()->toDateString();

        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MetodePembayaran;
use App\Models\ShiftKasir;
use App\Models\User;
use App\Services\ShiftKasirEmailReportService;
use App\Services\XlsxExportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShiftKasirController extends Controller
{
    private const PECAHAN = [
        100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100,
    ];

    public function index(Request $request): View
    {
        $cabangId = $this->activeCabangId();
        $this->ensureCabangAccessible($cabangId);
        $today = now()->toDateString();

        $userId = (int) auth()->id();
        $openShift = ShiftKasir::query()
            ->where('cabang_id', $cabangId)
            ->where('user_id', $userId)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();

        $kasExpected = $openShift ? $this->hitungKasExpected($openShift) : 0;
        $isStaleOpenShift = (bool) ($openShift?->dibuka_pada && !$openShift->dibuka_pada->isToday());

        $riwayat = ShiftKasir::query()
            ->with('cabang')
            ->where('user_id', $userId)
            ->where('cabang_id', $cabangId)
            ->whereDate('dibuka_pada', $today)
            ->latest('dibuka_pada')
            ->get();

        return view('pages.pos.tutup-kasir', [
            'openShift' => $openShift,
            'kasExpected' => $kasExpected,
            'isStaleOpenShift' => $isStaleOpenShift,
            'pecahan' => self::PECAHAN,
            'riwayatShift' => $riwayat,
        ]);
    }

    public function laporan(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'kasir_user_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'in:OPEN,CLOSED'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $cabangId = $this->resolveCabangFilter($request);
        $this->ensureCabangAccessible($cabangId);
        $kasirId = isset($validated['kasir_user_id']) ? (int) $validated['kasir_user_id'] : null;
        $status = $validated['status'] ?? null;

        $shiftQuery = ShiftKasir::query()
            ->with(['user:id,name,username', 'cabang:id,nama'])
            ->whereDate('dibuka_pada', '>=', $dateFrom)
            ->whereDate('dibuka_pada', '<=', $dateTo);
        $this->applyCabangScope($shiftQuery);
        if ($cabangId) {
            $shiftQuery->where('cabang_id', $cabangId);
        }
        if ($kasirId) {
            $shiftQuery->where('user_id', $kasirId);
        }
        if ($status) {
            $shiftQuery->where('status', $status);
        }

        $cashByShift = DB::table('pembayaran_penjualan as pp')
            ->join('metode_pembayaran as mp', 'mp.id', '=', 'pp.metode_pembayaran_id')
            ->selectRaw('
                pp.shift_kasir_id,
                COALESCE(SUM(CASE WHEN pp.nominal > 0 THEN pp.nominal ELSE 0 END), 0) as total_tunai_kotor,
                COALESCE(ABS(SUM(CASE WHEN pp.nominal < 0 THEN pp.nominal ELSE 0 END)), 0) as total_tunai_void,
                COALESCE(SUM(pp.nominal), 0) as total_tunai_bersih
            ')
            ->whereNotNull('pp.shift_kasir_id')
            ->where('mp.kode', 'CASH')
            ->groupBy('pp.shift_kasir_id')
            ->get()
            ->keyBy('shift_kasir_id');

        $shiftRows = $shiftQuery->latest('id')->get()->map(function (ShiftKasir $shift) use ($cashByShift) {
            $cashMeta = $cashByShift->get($shift->id);
            return [
                'id' => $shift->id,
                'status' => $shift->status,
                'kasir' => $shift->user,
                'cabang' => $shift->cabang,
                'dibuka_pada' => $shift->dibuka_pada,
                'ditutup_pada' => $shift->ditutup_pada,
                'modal_awal' => (float) ($shift->modal_awal ?? 0),
                'pendapatan_tunai_kotor' => (float) ($cashMeta->total_tunai_kotor ?? 0),
                'pendapatan_tunai_void' => (float) ($cashMeta->total_tunai_void ?? 0),
                'pendapatan_tunai' => (float) ($cashMeta->total_tunai_bersih ?? 0),
                'kas_expected' => (float) ($shift->kas_expected ?? 0),
                'kas_fisik' => (float) ($shift->kas_fisik ?? 0),
                'selisih' => (float) ($shift->selisih ?? 0),
            ];
        });

        $kasirOptionsQuery = ShiftKasir::query()
            ->whereDate('dibuka_pada', '>=', $dateFrom)
            ->whereDate('dibuka_pada', '<=', $dateTo);
        $this->applyCabangScope($kasirOptionsQuery);
        if ($cabangId) {
            $kasirOptionsQuery->where('cabang_id', $cabangId);
        }

        $kasirIds = $kasirOptionsQuery->distinct()->pluck('user_id')->all();
        $kasirList = User::query()
            ->whereIn('id', $kasirIds)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        if ($request->boolean('export_xlsx')) {
            $rowsXlsx = $shiftRows->map(function (array $row) {
                return [
                    '#' . $row['id'],
                    $row['kasir']?->name ?? '-',
                    $row['cabang']?->nama ?? '-',
                    $row['dibuka_pada']?->format('Y-m-d H:i'),
                    $row['ditutup_pada']?->format('Y-m-d H:i') ?? '-',
                    (float) $row['modal_awal'],
                    (float) $row['pendapatan_tunai_kotor'],
                    (float) $row['pendapatan_tunai_void'],
                    (float) $row['pendapatan_tunai'],
                    (float) $row['kas_expected'],
                    (float) $row['kas_fisik'],
                    (float) $row['selisih'],
                    (string) $row['status'],
                ];
            })->all();

            return app(XlsxExportService::class)->download(
                'laporan-tutup-kasir-' . now()->format('Ymd-His') . '.xlsx',
                ['Shift', 'Kasir', 'Cabang', 'Dibuka', 'Ditutup', 'Modal Awal', 'Kas Tunai Kotor', 'Void/Refund Tunai', 'Kas Tunai Bersih', 'Kas Expected', 'Kas Fisik', 'Selisih', 'Status'],
                $rowsXlsx,
                'Tutup Kasir'
            );
        }

        return view('pages.pos.laporan-tutup-kasir', [
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'kasirList' => $kasirList,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
                'kasir_user_id' => $kasirId,
                'status' => $status,
            ],
            'summary' => [
                'jumlah_shift' => $shiftRows->count(),
                'total_modal_awal' => (float) $shiftRows->sum('modal_awal'),
                'total_pendapatan_tunai_kotor' => (float) $shiftRows->sum('pendapatan_tunai_kotor'),
                'total_pendapatan_tunai_void' => (float) $shiftRows->sum('pendapatan_tunai_void'),
                'total_pendapatan_tunai' => (float) $shiftRows->sum('pendapatan_tunai'),
                'total_kas_expected' => (float) $shiftRows->sum('kas_expected'),
                'total_kas_fisik' => (float) $shiftRows->sum('kas_fisik'),
                'total_selisih' => (float) $shiftRows->sum('selisih'),
            ],
            'shiftRows' => $shiftRows,
        ]);
    }

    public function buka(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'modal_awal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cabangId = $this->activeCabangId();
        $this->ensureCabangAccessible($cabangId);
        $userId = (int) auth()->id();

        $existingOpen = ShiftKasir::query()
            ->where('cabang_id', $cabangId)
            ->where('user_id', $userId)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();

        if ($existingOpen) {
            if ($existingOpen->dibuka_pada && !$existingOpen->dibuka_pada->isToday()) {
                return back()->with('warning', 'Masih ada shift tanggal ' . $existingOpen->dibuka_pada->format('d-m-Y') . ' yang belum ditutup.');
            }
            return back()->with('warning', 'Masih ada shift kasir yang belum ditutup.');
        }

        ShiftKasir::query()->create([
            'cabang_id' => $cabangId,
            'user_id' => $userId,
            'modal_awal' => (float) ($validated['modal_awal'] ?? 0),
            'kas_expected' => (float) ($validated['modal_awal'] ?? 0),
            'dibuka_pada' => now(),
            'status' => 'OPEN',
        ]);

        return back()->with('success', 'Shift kasir berhasil dibuka.');
    }

    public function tutup(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::PECAHAN as $nominal) {
            $rules["pecahan.$nominal"] = ['nullable', 'integer', 'min:0'];
        }
        $validated = $request->validate($rules);

        $cabangId = $this->activeCabangId();
        $this->ensureCabangAccessible($cabangId);
        $userId = (int) auth()->id();

        $shift = ShiftKasir::query()
            ->where('cabang_id', $cabangId)
            ->where('user_id', $userId)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();

        if (!$shift) {
            throw ValidationException::withMessages([
                'shift' => ['Tidak ada shift OPEN untuk ditutup.'],
            ]);
        }

        $detail = [];
        $kasFisik = 0;
        foreach (self::PECAHAN as $nominal) {
            $qty = (int) data_get($validated, "pecahan.$nominal", 0);
            $detail[(string) $nominal] = $qty;
            $kasFisik += ($nominal * $qty);
        }

        $kasExpected = $this->hitungKasExpected($shift);
        $selisih = (float) $kasFisik - (float) $kasExpected;

        $shift->update([
            'kas_expected' => $kasExpected,
            'kas_fisik' => $kasFisik,
            'selisih' => $selisih,
            'detail_pecahan' => $detail,
            'ditutup_pada' => now(),
            'status' => 'CLOSED',
        ]);

        $successMessage = 'Shift kasir berhasil ditutup.';
        try {
            $emailResult = app(ShiftKasirEmailReportService::class)->sendDailyReport($shift);
            if (($emailResult['sent'] ?? false) === true) {
                $recipientCount = (int) ($emailResult['recipients_count'] ?? 0);
                $successMessage .= ' Laporan email masuk antrian kirim untuk ' . $recipientCount . ' penerima.';
            } elseif (($emailResult['reason'] ?? '') === 'recipients_empty') {
                $successMessage .= ' Pengiriman email dilewati karena daftar email cabang belum diisi.';
            }
        } catch (\Throwable $e) {
            report($e);
            $successMessage .= ' Namun antrian email laporan gagal dibuat.';
        }

        return back()->with('success', $successMessage);
    }

    public function kirimUlangEmail(ShiftKasir $shiftKasir): RedirectResponse
    {
        if ($shiftKasir->status !== 'CLOSED') {
            return back()->with('warning', 'Email laporan hanya bisa dikirim ulang untuk shift CLOSED.');
        }

        $this->ensureCabangAccessible((int) $shiftKasir->cabang_id);

        try {
            $emailResult = app(ShiftKasirEmailReportService::class)->sendDailyReport($shiftKasir);
            if (($emailResult['sent'] ?? false) === true) {
                $recipientCount = (int) ($emailResult['recipients_count'] ?? 0);
                return back()->with('success', 'Kirim ulang laporan berhasil diantrikan ke ' . $recipientCount . ' penerima.');
            }

            if (($emailResult['reason'] ?? '') === 'disabled') {
                return back()->with('warning', 'Kirim ulang email dilewati karena fitur email tutup kasir cabang belum aktif.');
            }

            if (($emailResult['reason'] ?? '') === 'recipients_empty') {
                return back()->with('warning', 'Kirim ulang email dilewati karena daftar email cabang belum diisi.');
            }

            return back()->with('warning', 'Kirim ulang email dilewati.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Kirim ulang email gagal. Silakan cek log dan konfigurasi mail.');
        }
    }

    private function hitungKasExpected(ShiftKasir $shift): float
    {
        $cashMethodId = MetodePembayaran::query()->where('kode', 'CASH')->value('id');
        if (!$cashMethodId) {
            return (float) $shift->modal_awal;
        }

        $cashIncome = (float) DB::table('pembayaran_penjualan')
            ->where('shift_kasir_id', $shift->id)
            ->where('metode_pembayaran_id', $cashMethodId)
            ->sum('nominal');

        return (float) $shift->modal_awal + $cashIncome;
    }
}

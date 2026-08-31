<?php

namespace App\Http\Controllers;

use App\Models\JabatanTrackingReference;
use App\Models\KantongOrder;
use App\Models\KoTrackingKoCheck;
use App\Models\TrackingReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KonfirmasiController extends Controller
{
    private const STEP_CODES = ['KIRIM_FILE', 'KIRIM_HASIL', 'PENGAMBILAN'];

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'no_ko' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'in:PENDING,DONE,ALL'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
        ]);

        $dateFrom = $validated['date_from'] ?? now()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->toDateString();
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $status = $validated['status'] ?? 'PENDING';
        $cabangId = $this->resolveCabangFilter($request);
        $this->ensureCabangAccessible($cabangId);

        $stepCodes = $this->resolveExistingStepCodes(self::STEP_CODES);
        $baseQuery = KantongOrder::query()
            ->with([
                'pesananPenjualan:id,cabang_id,customer_name,pelanggan_id',
                'pesananPenjualan.pelanggan:id,nama',
                'pesananPenjualan.cabang:id,nama',
            ])
            ->whereHas('pesananPenjualan', function ($q) use ($cabangId) {
                $this->applyCabangScope($q);
                if ($cabangId) {
                    $q->where('cabang_id', $cabangId);
                }
            })
            ->whereDate('kantong_order.created_at', '>=', $dateFrom)
            ->whereDate('kantong_order.created_at', '<=', $dateTo);

        if (!empty($validated['no_ko'])) {
            $keyword = trim((string) $validated['no_ko']);
            $baseQuery->where('nomor_ko', 'like', '%' . $keyword . '%');
        }

        if ($status === 'PENDING') {
            $this->applyPendingFilter($baseQuery, $stepCodes);
        } elseif ($status === 'DONE') {
            $this->applyDoneFilter($baseQuery, $stepCodes);
        }

        $rows = $baseQuery
            ->latest('kantong_order.id')
            ->paginate(20)
            ->withQueryString();

        $orderIds = $rows->getCollection()
            ->pluck('pesanan_penjualan_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $checks = KoTrackingKoCheck::query()
            ->with('checkedBy:id,name')
            ->whereIn('pesanan_penjualan_id', $orderIds)
            ->where(function ($q) use ($stepCodes) {
                foreach ($stepCodes as $code) {
                    $q->orWhereRaw('UPPER(step_kode) = ?', [strtoupper($code)]);
                }
            })
            ->get()
            ->groupBy('pesanan_penjualan_id');

        $allowedStepCodes = $this->resolveAllowedKoStepCodesForUser(auth()->id());

        /** @var LengthAwarePaginator $rows */
        $rows->setCollection($rows->getCollection()->map(function (KantongOrder $ko) use ($checks, $stepCodes, $allowedStepCodes) {
            $byStep = collect($checks->get((int) $ko->pesanan_penjualan_id, collect()))
                ->keyBy(fn ($row) => strtoupper((string) $row->step_kode));

            $steps = [];
            foreach ($stepCodes as $code) {
                $normalized = strtoupper($code);
                $check = $byStep->get($normalized);
                $steps[$normalized] = [
                    'is_checked' => (bool) ($check?->is_checked ?? false),
                    'checked_by' => $check?->checkedBy?->name,
                    'checked_at' => $check?->checked_at,
                    'can_update' => in_array($normalized, $allowedStepCodes, true),
                ];
            }

            $isDone = collect($steps)->every(fn ($s) => (bool) ($s['is_checked'] ?? false));

            return [
                'nomor_ko' => (string) $ko->nomor_ko,
                'customer_name' => (string) (
                    $ko->pesananPenjualan?->customer_name
                    ?: ($ko->pesananPenjualan?->pelanggan?->nama ?? '-')
                ),
                'cabang_nama' => (string) ($ko->pesananPenjualan?->cabang?->nama ?? '-'),
                'tanggal_selesai' => $ko->tanggal_selesai,
                'is_done' => $isDone,
                'steps' => $steps,
            ];
        }));

        $summaryBase = clone $baseQuery;
        $totalKo = (clone $summaryBase)->count();
        $doneQuery = clone $summaryBase;
        $this->applyDoneFilter($doneQuery, $stepCodes);
        $doneKo = $doneQuery->count();
        $pendingKo = max($totalKo - $doneKo, 0);

        return view('konfirmasi', [
            'rows' => $rows,
            'filters' => [
                'no_ko' => $validated['no_ko'] ?? '',
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'cabang_id' => $cabangId,
            ],
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'summary' => [
                'total_ko' => $totalKo,
                'pending_ko' => $pendingKo,
                'done_ko' => $doneKo,
            ],
            'stepCodes' => $stepCodes,
        ]);
    }

    public function updateKoStep(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'no_ko' => ['required', 'string'],
            'step_kode' => ['required', 'string'],
        ]);

        $requestedStep = strtoupper(trim((string) $data['step_kode']));
        $allowedSystemSteps = $this->resolveExistingStepCodes(self::STEP_CODES);
        if (!in_array($requestedStep, array_map('strtoupper', $allowedSystemSteps), true)) {
            throw ValidationException::withMessages([
                'step_kode' => ['Step tracking tidak valid.'],
            ]);
        }

        $noKo = trim((string) $data['no_ko']);
        $ko = KantongOrder::query()
            ->with('pesananPenjualan:id,cabang_id')
            ->where('nomor_ko', $noKo)
            ->first();

        if (!$ko?->pesananPenjualan) {
            throw ValidationException::withMessages([
                'no_ko' => ['No KO tidak ditemukan.'],
            ]);
        }

        $this->ensureCabangAccessible((int) $ko->pesananPenjualan->cabang_id);

        $allowedStepCodes = $this->resolveAllowedKoStepCodesForUser(auth()->id());
        if (!in_array($requestedStep, $allowedStepCodes, true)) {
            throw ValidationException::withMessages([
                'step_kode' => ['Anda tidak berhak mengubah step ini.'],
            ]);
        }

        KoTrackingKoCheck::query()->updateOrCreate(
            [
                'pesanan_penjualan_id' => (int) $ko->pesananPenjualan->id,
                'step_kode' => $requestedStep,
            ],
            [
                'is_checked' => true,
                'checked_at' => now(),
                'checked_by_user_id' => (int) auth()->id(),
            ]
        );

        return back()->with('success', 'Tracking ' . $requestedStep . ' berhasil dicentang.');
    }

    private function resolveExistingStepCodes(array $preferredCodes): array
    {
        $existing = TrackingReference::query()
            ->where('tipe', 'KO')
            ->where(function ($q) use ($preferredCodes) {
                foreach ($preferredCodes as $code) {
                    $q->orWhereRaw('UPPER(kode) = ?', [strtoupper($code)]);
                }
            })
            ->orderBy('urutan')
            ->pluck('kode')
            ->map(fn ($k) => strtoupper((string) $k))
            ->values()
            ->all();

        return !empty($existing)
            ? $existing
            : array_map('strtoupper', $preferredCodes);
    }

    private function resolveAllowedKoStepCodesForUser(?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        $user = auth()->user();
        $user?->loadMissing('karyawan.jabatan');
        $jabatanId = (int) ($user?->karyawan?->jabatan_id ?? 0);
        if ($jabatanId <= 0) {
            return [];
        }

        $trackingIds = JabatanTrackingReference::query()
            ->where('jabatan_id', $jabatanId)
            ->where('can_update', true)
            ->pluck('tracking_reference_id')
            ->all();

        if (empty($trackingIds)) {
            return [];
        }

        return TrackingReference::query()
            ->whereIn('id', $trackingIds)
            ->where('tipe', 'KO')
            ->pluck('kode')
            ->map(fn ($kode) => strtoupper((string) $kode))
            ->unique()
            ->values()
            ->all();
    }

    private function applyPendingFilter($query, array $stepCodes): void
    {
        $query->where(function ($outer) use ($stepCodes) {
            foreach ($stepCodes as $code) {
                $upperCode = strtoupper($code);
                $outer->orWhereNotExists(function ($sub) use ($upperCode) {
                    $sub->select(DB::raw(1))
                        ->from('ko_tracking_ko_checks as kk')
                        ->whereColumn('kk.pesanan_penjualan_id', 'kantong_order.pesanan_penjualan_id')
                        ->whereRaw('UPPER(kk.step_kode) = ?', [$upperCode])
                        ->where('kk.is_checked', true);
                });
            }
        });
    }

    private function applyDoneFilter($query, array $stepCodes): void
    {
        foreach ($stepCodes as $code) {
            $upperCode = strtoupper($code);
            $query->whereExists(function ($sub) use ($upperCode) {
                $sub->select(DB::raw(1))
                    ->from('ko_tracking_ko_checks as kk')
                    ->whereColumn('kk.pesanan_penjualan_id', 'kantong_order.pesanan_penjualan_id')
                    ->whereRaw('UPPER(kk.step_kode) = ?', [$upperCode])
                    ->where('kk.is_checked', true);
            });
        }
    }
}

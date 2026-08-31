<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\JabatanTrackingReference;
use App\Models\TrackingReference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JabatanController extends Controller
{
    private const LEVEL_OPTIONS = ['STAFF', 'SPV', 'MANAGER', 'GM', 'DIREKTUR', 'KOMISARIS'];

    public function index(Request $request): View
    {
        $query = Jabatan::query()->orderBy('kode');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q): void {
                $builder->where('kode', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (bool) $request->integer('status'));
        }

        return view('pages.master.jabatan.index', [
            'jabatan' => $query->paginate(10)->withQueryString(),
            'levelOptions' => self::LEVEL_OPTIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', 'unique:jabatan,kode'],
            'nama' => ['required', 'string', 'max:100', 'unique:jabatan,nama'],
            'level' => ['required', Rule::in(self::LEVEL_OPTIONS)],
            'status' => ['nullable', 'boolean'],
        ]);

        Jabatan::create([
            'kode' => strtoupper($data['kode']),
            'nama' => strtoupper($data['nama']),
            'level' => strtoupper((string) $data['level']),
            'status' => (bool) ($data['status'] ?? false),
        ]);

        return redirect()->route('konfigurasi.jabatan')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:20', Rule::unique('jabatan', 'kode')->ignore($jabatan->id)],
            'nama' => ['required', 'string', 'max:100', Rule::unique('jabatan', 'nama')->ignore($jabatan->id)],
            'level' => ['required', Rule::in(self::LEVEL_OPTIONS)],
            'status' => ['nullable', 'boolean'],
        ]);

        $jabatan->update([
            'kode' => strtoupper($data['kode']),
            'nama' => strtoupper($data['nama']),
            'level' => strtoupper((string) $data['level']),
            'status' => (bool) ($data['status'] ?? false),
        ]);

        return redirect()->route('konfigurasi.jabatan')->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Jabatan $jabatan): RedirectResponse
    {
        if ($jabatan->karyawan()->exists()) {
            return back()->with('error', 'Jabatan masih dipakai karyawan, tidak bisa dihapus.');
        }

        $jabatan->delete();

        return redirect()->route('konfigurasi.jabatan')->with('success', 'Jabatan berhasil dihapus.');
    }

    public function trackingKo(Jabatan $jabatan): View
    {
        $selected = JabatanTrackingReference::query()
            ->where('jabatan_id', $jabatan->id)
            ->where('can_update', true)
            ->pluck('tracking_reference_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('pages.master.jabatan.tracking-ko', [
            'jabatan' => $jabatan,
            'trackingItem' => TrackingReference::query()
                ->where('status', true)
                ->where('tipe', 'ITEM')
                ->orderBy('urutan')
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode']),
            'trackingKo' => TrackingReference::query()
                ->where('status', true)
                ->where('tipe', 'KO')
                ->orderBy('urutan')
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode']),
            'selectedTrackingIds' => $selected,
        ]);
    }

    public function updateTrackingKo(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $allowedIds = TrackingReference::query()
            ->where('status', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validated = $request->validate([
            'tracking_ids' => ['nullable', 'array'],
            'tracking_ids.*' => ['integer', Rule::in($allowedIds)],
        ]);

        $trackingIds = collect($validated['tracking_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => in_array($id, $allowedIds, true))
            ->unique()
            ->values();

        DB::transaction(function () use ($jabatan, $trackingIds): void {
            JabatanTrackingReference::query()
                ->where('jabatan_id', $jabatan->id)
                ->delete();

            foreach ($trackingIds as $trackingId) {
                JabatanTrackingReference::query()->create([
                    'jabatan_id' => $jabatan->id,
                    'tracking_reference_id' => $trackingId,
                    'can_update' => true,
                ]);
            }
        });

        return redirect()
            ->route('konfigurasi.jabatan.tracking-ko', $jabatan)
            ->with('success', 'Hak akses tracking jabatan berhasil diperbarui.');
    }
}

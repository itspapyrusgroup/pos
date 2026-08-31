<?php

namespace App\Http\Controllers;

use App\Models\DiskonOtomatis;
use App\Models\Paket;
use App\Models\PesananPenjualan;
use App\Models\VoucherPromosi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PromosiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:0,1'],
            'cabang_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'tab' => ['nullable', 'in:voucher,diskon'],
        ]);

        $voucherQuery = VoucherPromosi::query()->with(['cabang', 'cabangs'])->latest('id');
        $diskonQuery = DiskonOtomatis::query()->with(['cabang', 'cabangs', 'pakets:id,nama'])->latest('id');
        $allowedCabangIds = $this->accessibleCabangIds();

        $this->scopePromoByAccessibleCabang($voucherQuery, $allowedCabangIds);
        $this->scopePromoByAccessibleCabang($diskonQuery, $allowedCabangIds);

        $cabangId = (int) ($request->input('cabang_id') ?? 0);
        if ($cabangId > 0) {
            $this->applyPromoCabangFilter($voucherQuery, $cabangId);
            $this->applyPromoCabangFilter($diskonQuery, $cabangId);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $voucherQuery->where(function ($x) use ($q) {
                $x->where('nama', 'like', '%' . $q . '%')
                    ->orWhere('kode', 'like', '%' . $q . '%');
            });
            $diskonQuery->where(function ($x) use ($q) {
                $x->where('nama', 'like', '%' . $q . '%')
                    ->orWhereRaw("CONCAT('AUTO-', id) like ?", ['%' . $q . '%']);
            });
        }

        $statusParam = $request->input('status');
        if (!$request->exists('status')) {
            $voucherQuery->where('status', true);
            $diskonQuery->where('status', true);
        } elseif ($statusParam !== null && $statusParam !== '') {
            $status = (bool) $statusParam;
            $voucherQuery->where('status', $status);
            $diskonQuery->where('status', $status);
        }

        $activeTab = (string) $request->input('tab', '');
        if (!in_array($activeTab, ['voucher', 'diskon'], true)) {
            $activeTab = $request->filled('diskon_page') ? 'diskon' : 'voucher';
        }

        return view('pages.pos.promosi.index', [
            'voucherList' => $voucherQuery->paginate(10, ['*'], 'voucher_page')->withQueryString(),
            'diskonList' => $diskonQuery->paginate(10, ['*'], 'diskon_page')->withQueryString(),
            'cabangList' => $this->accessibleCabangQuery()->get(['id', 'nama']),
            'paketList' => Paket::query()->where('status', true)->orderBy('nama')->get(['id', 'nama']),
            'activeTab' => $activeTab,
        ]);
    }

    public function storeVoucher(Request $request)
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:voucher_promosi,kode'],
            'nama' => ['required', 'string', 'max:100'],
            'tipe_diskon' => ['required', 'in:PERSEN,NOMINAL'],
            'nilai_diskon' => ['required', 'numeric', 'min:0'],
            'minimum_pembelian' => ['nullable', 'numeric', 'min:0'],
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif_mulai' => ['required', 'date'],
            'aktif_sampai' => ['required', 'date', 'after_or_equal:aktif_mulai'],
            'aktif_24_jam' => ['nullable', 'boolean'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_sampai' => ['nullable', 'date_format:H:i'],
            'hari_aktif' => ['nullable', 'array'],
            'hari_aktif.*' => ['integer', 'between:1,7'],
            'kuota' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'boolean'],
        ]);

        $cabangIds = $this->extractCabangIds($validated);
        $this->ensureCabangIdsAccessible($cabangIds);
        [$aktif24Jam, $jamMulai, $jamSampai] = $this->resolveJamAktif($validated);

        $voucher = VoucherPromosi::query()->create([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'tipe_diskon' => $validated['tipe_diskon'],
            'nilai_diskon' => $validated['nilai_diskon'],
            'minimum_pembelian' => $validated['minimum_pembelian'] ?? 0,
            'cabang_id' => $cabangIds[0] ?? null,
            'aktif_mulai' => $validated['aktif_mulai'],
            'aktif_sampai' => $validated['aktif_sampai'],
            'aktif_24_jam' => $aktif24Jam,
            'jam_mulai' => $jamMulai,
            'jam_sampai' => $jamSampai,
            'hari_aktif' => $validated['hari_aktif'] ?? null,
            'kuota' => $validated['kuota'] ?? null,
            'terpakai' => 0,
            'status' => (bool) ($validated['status'] ?? true),
        ]);
        $voucher->cabangs()->sync($cabangIds);

        return redirect()->route('promosi')->with('success', 'Voucher promosi berhasil ditambahkan.');
    }

    public function updateVoucher(Request $request, VoucherPromosi $voucherPromosi)
    {
        $this->ensurePromoEntityAccessible($voucherPromosi->cabang_id ? [(int) $voucherPromosi->cabang_id] : [], $voucherPromosi->cabangs()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all());

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:voucher_promosi,kode,' . $voucherPromosi->id],
            'nama' => ['required', 'string', 'max:100'],
            'tipe_diskon' => ['required', 'in:PERSEN,NOMINAL'],
            'nilai_diskon' => ['required', 'numeric', 'min:0'],
            'minimum_pembelian' => ['nullable', 'numeric', 'min:0'],
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif_mulai' => ['required', 'date'],
            'aktif_sampai' => ['required', 'date', 'after_or_equal:aktif_mulai'],
            'aktif_24_jam' => ['nullable', 'boolean'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_sampai' => ['nullable', 'date_format:H:i'],
            'hari_aktif' => ['nullable', 'array'],
            'hari_aktif.*' => ['integer', 'between:1,7'],
            'kuota' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'boolean'],
        ]);

        $cabangIds = $this->extractCabangIds($validated);
        $this->ensureCabangIdsAccessible($cabangIds);
        [$aktif24Jam, $jamMulai, $jamSampai] = $this->resolveJamAktif($validated);

        $voucherPromosi->update([
            'kode' => strtoupper($validated['kode']),
            'nama' => $validated['nama'],
            'tipe_diskon' => $validated['tipe_diskon'],
            'nilai_diskon' => $validated['nilai_diskon'],
            'minimum_pembelian' => $validated['minimum_pembelian'] ?? 0,
            'cabang_id' => $cabangIds[0] ?? null,
            'aktif_mulai' => $validated['aktif_mulai'],
            'aktif_sampai' => $validated['aktif_sampai'],
            'aktif_24_jam' => $aktif24Jam,
            'jam_mulai' => $jamMulai,
            'jam_sampai' => $jamSampai,
            'hari_aktif' => $validated['hari_aktif'] ?? null,
            'kuota' => $validated['kuota'] ?? null,
            'status' => (bool) ($validated['status'] ?? true),
        ]);
        $voucherPromosi->cabangs()->sync($cabangIds);

        return redirect()->route('promosi')->with('success', 'Voucher promosi berhasil diperbarui.');
    }

    public function destroyVoucher(VoucherPromosi $voucherPromosi)
    {
        $this->ensurePromoEntityAccessible(
            $voucherPromosi->cabang_id ? [(int) $voucherPromosi->cabang_id] : [],
            $voucherPromosi->cabangs()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all()
        );

        $sudahDipakai = ((int) ($voucherPromosi->terpakai ?? 0)) > 0
            || PesananPenjualan::query()
                ->whereNotNull('catatan')
                ->where('catatan', 'like', '%Promo dipakai: ' . strtoupper((string) $voucherPromosi->kode) . ' (%')
                ->exists();

        if ($sudahDipakai) {
            if ($voucherPromosi->status) {
                $voucherPromosi->update(['status' => false]);
                return redirect()->route('promosi')->with('success', 'Voucher sudah dipakai transaksi, tidak bisa dihapus. Status diubah menjadi Non Aktif.');
            }

            return redirect()->route('promosi')->with('success', 'Voucher sudah dipakai transaksi dan tetap Non Aktif.');
        }

        $voucherPromosi->delete();
        return redirect()->route('promosi')->with('success', 'Voucher promosi berhasil dihapus.');
    }

    public function storeDiskonOtomatis(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tipe_diskon' => ['required', 'in:PERSEN,NOMINAL'],
            'nilai_diskon' => ['required', 'numeric', 'min:0'],
            'minimum_pembelian' => ['nullable', 'numeric', 'min:0'],
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif_mulai' => ['required', 'date'],
            'aktif_sampai' => ['required', 'date', 'after_or_equal:aktif_mulai'],
            'aktif_24_jam' => ['nullable', 'boolean'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_sampai' => ['nullable', 'date_format:H:i'],
            'hari_aktif' => ['nullable', 'array'],
            'hari_aktif.*' => ['integer', 'between:1,7'],
            'paket_ids' => ['nullable', 'array'],
            'paket_ids.*' => ['integer', 'exists:paket,id'],
            'status' => ['nullable', 'boolean'],
        ]);

        $cabangIds = $this->extractCabangIds($validated);
        $this->ensureCabangIdsAccessible($cabangIds);
        [$aktif24Jam, $jamMulai, $jamSampai] = $this->resolveJamAktif($validated);

        $diskon = DiskonOtomatis::query()->create([
            'nama' => $validated['nama'],
            'tipe_diskon' => $validated['tipe_diskon'],
            'nilai_diskon' => $validated['nilai_diskon'],
            'minimum_pembelian' => $validated['minimum_pembelian'] ?? 0,
            'cabang_id' => $cabangIds[0] ?? null,
            'aktif_mulai' => $validated['aktif_mulai'],
            'aktif_sampai' => $validated['aktif_sampai'],
            'aktif_24_jam' => $aktif24Jam,
            'jam_mulai' => $jamMulai,
            'jam_sampai' => $jamSampai,
            'hari_aktif' => $validated['hari_aktif'] ?? null,
            'status' => (bool) ($validated['status'] ?? true),
        ]);
        $diskon->cabangs()->sync($cabangIds);
        $diskon->pakets()->sync(collect($validated['paket_ids'] ?? [])->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all());

        return redirect()->route('promosi')->with('success', 'Diskon otomatis berhasil ditambahkan.');
    }

    public function updateDiskonOtomatis(Request $request, DiskonOtomatis $diskonOtomati)
    {
        $this->ensurePromoEntityAccessible(
            $diskonOtomati->cabang_id ? [(int) $diskonOtomati->cabang_id] : [],
            $diskonOtomati->cabangs()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all()
        );

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tipe_diskon' => ['required', 'in:PERSEN,NOMINAL'],
            'nilai_diskon' => ['required', 'numeric', 'min:0'],
            'minimum_pembelian' => ['nullable', 'numeric', 'min:0'],
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif_mulai' => ['required', 'date'],
            'aktif_sampai' => ['required', 'date', 'after_or_equal:aktif_mulai'],
            'aktif_24_jam' => ['nullable', 'boolean'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_sampai' => ['nullable', 'date_format:H:i'],
            'hari_aktif' => ['nullable', 'array'],
            'hari_aktif.*' => ['integer', 'between:1,7'],
            'paket_ids' => ['nullable', 'array'],
            'paket_ids.*' => ['integer', 'exists:paket,id'],
            'status' => ['nullable', 'boolean'],
        ]);

        $cabangIds = $this->extractCabangIds($validated);
        $this->ensureCabangIdsAccessible($cabangIds);
        [$aktif24Jam, $jamMulai, $jamSampai] = $this->resolveJamAktif($validated);

        $diskonOtomati->update([
            'nama' => $validated['nama'],
            'tipe_diskon' => $validated['tipe_diskon'],
            'nilai_diskon' => $validated['nilai_diskon'],
            'minimum_pembelian' => $validated['minimum_pembelian'] ?? 0,
            'cabang_id' => $cabangIds[0] ?? null,
            'aktif_mulai' => $validated['aktif_mulai'],
            'aktif_sampai' => $validated['aktif_sampai'],
            'aktif_24_jam' => $aktif24Jam,
            'jam_mulai' => $jamMulai,
            'jam_sampai' => $jamSampai,
            'hari_aktif' => $validated['hari_aktif'] ?? null,
            'status' => (bool) ($validated['status'] ?? true),
        ]);
        $diskonOtomati->cabangs()->sync($cabangIds);
        $diskonOtomati->pakets()->sync(collect($validated['paket_ids'] ?? [])->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all());

        return redirect()->route('promosi')->with('success', 'Diskon otomatis berhasil diperbarui.');
    }

    public function destroyDiskonOtomatis(DiskonOtomatis $diskonOtomati)
    {
        $this->ensurePromoEntityAccessible(
            $diskonOtomati->cabang_id ? [(int) $diskonOtomati->cabang_id] : [],
            $diskonOtomati->cabangs()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all()
        );

        $promoKode = 'AUTO-' . (int) $diskonOtomati->id;
        $sudahDipakai = PesananPenjualan::query()
            ->whereNotNull('catatan')
            ->where('catatan', 'like', '%Promo dipakai: ' . $promoKode . ' (%')
            ->exists();

        if ($sudahDipakai) {
            if ($diskonOtomati->status) {
                $diskonOtomati->update(['status' => false]);
                return redirect()->route('promosi')->with('success', 'Diskon otomatis sudah dipakai transaksi, tidak bisa dihapus. Status diubah menjadi Non Aktif.');
            }

            return redirect()->route('promosi')->with('success', 'Diskon otomatis sudah dipakai transaksi dan tetap Non Aktif.');
        }

        $diskonOtomati->delete();
        return redirect()->route('promosi')->with('success', 'Diskon otomatis berhasil dihapus.');
    }

    private function scopePromoByAccessibleCabang(Builder $query, array $allowedCabangIds): void
    {
        if (empty($allowedCabangIds)) {
            return;
        }

        $query->where(function ($q) use ($allowedCabangIds) {
            $q->whereHas('cabangs', function ($inner) use ($allowedCabangIds) {
                $inner->whereIn('cabang.id', $allowedCabangIds);
            })->orWhere(function ($inner) use ($allowedCabangIds) {
                $inner->whereDoesntHave('cabangs')
                    ->where(function ($legacy) use ($allowedCabangIds) {
                        $legacy->whereNull('cabang_id')->orWhereIn('cabang_id', $allowedCabangIds);
                    });
            });
        });
    }

    private function applyPromoCabangFilter(Builder $query, int $cabangId): void
    {
        $query->where(function ($q) use ($cabangId) {
            $q->whereHas('cabangs', function ($inner) use ($cabangId) {
                $inner->where('cabang.id', $cabangId);
            })->orWhere(function ($inner) use ($cabangId) {
                $inner->whereDoesntHave('cabangs')
                    ->where(function ($legacy) use ($cabangId) {
                        $legacy->whereNull('cabang_id')->orWhere('cabang_id', $cabangId);
                    });
            });
        });
    }

    private function extractCabangIds(array $validated): array
    {
        return collect($validated['cabang_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function ensureCabangIdsAccessible(array $cabangIds): void
    {
        foreach ($cabangIds as $cabangId) {
            $this->ensureCabangAccessible((int) $cabangId);
        }
    }

    private function ensurePromoEntityAccessible(array $legacyCabangIds, array $multiCabangIds): void
    {
        $allCabangIds = collect(array_merge($legacyCabangIds, $multiCabangIds))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $this->ensureCabangIdsAccessible($allCabangIds);
    }

    private function resolveJamAktif(array $validated): array
    {
        $aktif24Jam = (bool) ($validated['aktif_24_jam'] ?? false);
        $jamMulai = $aktif24Jam ? null : ($validated['jam_mulai'] ?? null);
        $jamSampai = $aktif24Jam ? null : ($validated['jam_sampai'] ?? null);

        if (!$aktif24Jam && (!$jamMulai || !$jamSampai)) {
            throw ValidationException::withMessages([
                'jam_mulai' => ['Isi jam mulai dan jam sampai, atau centang opsi 24 jam.'],
            ]);
        }

        return [$aktif24Jam, $jamMulai, $jamSampai];
    }
}

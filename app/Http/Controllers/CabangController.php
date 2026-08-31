<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\CabangSalesMode;
use App\Models\MetodePembayaran;
use App\Models\Perusahaan;
use App\Models\SalesMode;
use App\Models\TemplateHarga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CabangController extends Controller
{
    public function index(Request $request)
    {
        $query = Cabang::with('perusahaan');

        // Filter by nama
        if ($request->has('nama') && !empty($request->nama)) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }

        // Filter by kode
        if ($request->has('kode') && !empty($request->kode)) {
            $query->where('kode', 'like', '%' . $request->kode . '%');
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '' && in_array($request->status, ['0', '1'])) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = min(100, max(1, (int) $request->input('per_page', 10)));
        $branches = $query->paginate($perPage);

        // Transform data untuk memastikan format sesuai dengan yang diharapkan frontend
        $transformedData = $branches->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'perusahaan_id' => $item->perusahaan_id,
                'nama' => $item->nama,
                'alamat' => $item->alamat,
                'no_hp' => $item->no_hp,
                'struk_footer' => $item->struk_footer,
                'warna_header' => $item->warna_header,
                'allow_minus_stock' => (bool) $item->allow_minus_stock,
                'tutup_kasir_email_enabled' => (bool) $item->tutup_kasir_email_enabled,
                'tutup_kasir_email_recipients' => array_values(array_filter((array) $item->tutup_kasir_email_recipients)),
                'status' => $item->status,
                'perusahaan' => $item->perusahaan ? [
                    'id' => $item->perusahaan->id,
                    'kode' => $item->perusahaan->kode,
                    'nama' => $item->perusahaan->nama
                ] : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'total' => $branches->total(),
            'current_page' => $branches->currentPage(),
            'per_page' => $branches->perPage(),
            'last_page' => $branches->lastPage(),
        ]);
    }

    public function getSalesModeTemplate()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'sales_mode' => SalesMode::query()
                    ->where('status', true)
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama']),
                'template_harga' => TemplateHarga::query()
                    ->where('status', true)
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama']),
                'metode_pembayaran' => MetodePembayaran::query()
                    ->where('status', true)
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama']),
            ],
        ]);
    }

    public function getPerusahaan()
    {
        try {
            $perusahaan = Perusahaan::where('status', true)
                ->select(['id', 'kode', 'nama'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $perusahaan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data perusahaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'perusahaan_id' => 'required|exists:perusahaan,id',
            'nama' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'no_hp' => 'required|string|max:15',
            'struk_footer' => 'nullable|string',
            'warna_header' => 'nullable|string|max:30',
            'allow_minus_stock' => 'nullable|boolean',
            'tutup_kasir_email_enabled' => 'nullable|boolean',
            'tutup_kasir_email_recipients' => 'nullable|array|required_if:tutup_kasir_email_enabled,1',
            'tutup_kasir_email_recipients.*' => 'email',
            'status' => 'required|boolean',
            'sales_modes' => 'nullable|array',
            'sales_modes.*.sales_mode_id' => 'required|exists:sales_mode,id',
            'sales_modes.*.template_harga_id' => 'nullable|exists:template_harga,id',
            'sales_modes.*.status' => 'required|boolean',
            'metode_pembayaran_ids' => 'nullable|array',
            'metode_pembayaran_ids.*' => 'exists:metode_pembayaran,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validated = $validator->validated();
            $salesModes = $validated['sales_modes'] ?? [];
            $metodePembayaranIds = $validated['metode_pembayaran_ids'] ?? [];
            unset($validated['sales_modes']);
            unset($validated['metode_pembayaran_ids']);
            $validated['tutup_kasir_email_enabled'] = (bool) ($validated['tutup_kasir_email_enabled'] ?? false);
            $validated['tutup_kasir_email_recipients'] = $this->normalizeEmailRecipients($validated['tutup_kasir_email_recipients'] ?? []);

            $validated['kode'] = Cabang::generateKode();

            $cabang = DB::transaction(function () use ($validated, $salesModes, $metodePembayaranIds) {
                $cabang = Cabang::create($validated);
                $this->simpanSalesModeCabang($cabang->id, $salesModes);
                $cabang->metodePembayaran()->sync($metodePembayaranIds);
                return $cabang;
            });

            return response()->json([
                'success' => true,
                'message' => 'Cabang berhasil ditambahkan',
                'data' => $cabang->load('perusahaan')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan cabang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $cabang = Cabang::with(['perusahaan', 'salesModes', 'metodePembayaran:id'])->find($id);

        if (!$cabang) {
            return response()->json(['message' => 'Cabang tidak ditemukan'], 404);
        }

        return response()->json([
            'id' => $cabang->id,
            'kode' => $cabang->kode,
            'perusahaan_id' => $cabang->perusahaan_id,
            'nama' => $cabang->nama,
            'alamat' => $cabang->alamat,
            'no_hp' => $cabang->no_hp,
            'struk_footer' => $cabang->struk_footer,
            'warna_header' => $cabang->warna_header,
            'allow_minus_stock' => (bool) $cabang->allow_minus_stock,
            'tutup_kasir_email_enabled' => (bool) $cabang->tutup_kasir_email_enabled,
            'tutup_kasir_email_recipients' => array_values(array_filter((array) $cabang->tutup_kasir_email_recipients)),
            'status' => $cabang->status,
            'sales_modes' => $cabang->salesModes->map(function ($mode) {
                return [
                    'sales_mode_id' => $mode->sales_mode_id,
                    'template_harga_id' => $mode->template_harga_id,
                    'status' => (bool) $mode->status,
                ];
            })->values(),
            'metode_pembayaran_ids' => $cabang->metodePembayaran->pluck('id')->values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $cabang = Cabang::find($id);

        if (!$cabang) {
            return response()->json(['message' => 'Cabang tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'perusahaan_id' => 'required|exists:perusahaan,id',
            'nama' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'no_hp' => 'required|string|max:15',
            'struk_footer' => 'nullable|string',
            'warna_header' => 'nullable|string|max:30',
            'allow_minus_stock' => 'nullable|boolean',
            'tutup_kasir_email_enabled' => 'nullable|boolean',
            'tutup_kasir_email_recipients' => 'nullable|array|required_if:tutup_kasir_email_enabled,1',
            'tutup_kasir_email_recipients.*' => 'email',
            'status' => 'required|boolean',
            'sales_modes' => 'nullable|array',
            'sales_modes.*.sales_mode_id' => 'required|exists:sales_mode,id',
            'sales_modes.*.template_harga_id' => 'nullable|exists:template_harga,id',
            'sales_modes.*.status' => 'required|boolean',
            'metode_pembayaran_ids' => 'nullable|array',
            'metode_pembayaran_ids.*' => 'exists:metode_pembayaran,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validated();
        $salesModes = $validated['sales_modes'] ?? [];
        $metodePembayaranIds = $validated['metode_pembayaran_ids'] ?? [];
        unset($validated['sales_modes']);
        unset($validated['metode_pembayaran_ids']);
        $validated['tutup_kasir_email_enabled'] = (bool) ($validated['tutup_kasir_email_enabled'] ?? false);
        $validated['tutup_kasir_email_recipients'] = $this->normalizeEmailRecipients($validated['tutup_kasir_email_recipients'] ?? []);

        DB::transaction(function () use ($cabang, $validated, $salesModes, $metodePembayaranIds) {
            $cabang->update($validated);
            CabangSalesMode::query()->where('cabang_id', $cabang->id)->delete();
            $this->simpanSalesModeCabang($cabang->id, $salesModes);
            $cabang->metodePembayaran()->sync($metodePembayaranIds);
        });

        return response()->json([
            'message' => 'Cabang berhasil diperbarui',
            'data' => $cabang->load('perusahaan')
        ]);
    }

    public function destroy($id)
    {
        $cabang = Cabang::find($id);

        if (!$cabang) {
            return response()->json(['message' => 'Cabang tidak ditemukan'], 404);
        }

        $dipakaiTransaksi = DB::table('pesanan_penjualan')->where('cabang_id', $cabang->id)->exists()
            || DB::table('pesanan_pembelian')->where('cabang_id', $cabang->id)->exists()
            || DB::table('penerimaan_barang')->where('cabang_id', $cabang->id)->exists()
            || DB::table('faktur_pembelian')->where('cabang_id', $cabang->id)->exists()
            || DB::table('retur_pembelian')->where('cabang_id', $cabang->id)->exists();

        if ($dipakaiTransaksi) {
            if ($cabang->status) {
                $cabang->update(['status' => false]);
                return response()->json(['message' => 'Cabang sudah dipakai transaksi, tidak bisa dihapus. Status diubah menjadi Non Aktif.']);
            }

            return response()->json(['message' => 'Cabang sudah dipakai transaksi dan tetap Non Aktif.']);
        }

        $cabang->delete();

        return response()->json(['message' => 'Cabang berhasil dihapus']);
    }

    public function generateKode()
    {
        try {
            return response()->json([
                'success' => true,
                'kode' => Cabang::generateKode()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate kode',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function simpanSalesModeCabang(int $cabangId, array $salesModes): void
    {
        foreach ($salesModes as $mode) {
            if (!($mode['status'] ?? false)) {
                continue;
            }

            CabangSalesMode::query()->create([
                'cabang_id' => $cabangId,
                'sales_mode_id' => $mode['sales_mode_id'],
                'template_harga_id' => $mode['template_harga_id'] ?? null,
                'status' => true,
            ]);
        }
    }

    private function normalizeEmailRecipients(array $emails): array
    {
        $normalized = collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $normalized;
    }


}

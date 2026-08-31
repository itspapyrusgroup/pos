<?php

namespace App\Http\Controllers;

use App\Models\SalesMode;
use App\Models\PesananPenjualan;
use App\Models\CabangSalesMode;
use Illuminate\Http\Request;

class SalesModeController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesMode::query()->latest('id');
        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $request->nama . '%');
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.pos.sales_mode.index', [
            'salesMode' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'boolean'],
        ]);

        SalesMode::query()->create([
            'kode' => $this->generateKode(),
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? true),
        ]);

        return redirect()->route('sales-mode')->with('success', 'Sales mode berhasil ditambahkan.');
    }

    public function update(Request $request, SalesMode $salesMode)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'boolean'],
        ]);

        $salesMode->update([
            'nama' => $validated['nama'],
            'status' => (bool) ($validated['status'] ?? false),
        ]);

        return redirect()->route('sales-mode')->with('success', 'Sales mode berhasil diperbarui.');
    }

    public function destroy(SalesMode $salesMode)
    {
        $dipakaiTransaksi = PesananPenjualan::query()
            ->where('sales_mode_id', $salesMode->id)
            ->exists();

        $masihDipakaiMappingCabang = CabangSalesMode::query()
            ->where('sales_mode_id', $salesMode->id)
            ->exists();

        if ($dipakaiTransaksi || $masihDipakaiMappingCabang) {
            if ($salesMode->status) {
                $salesMode->update(['status' => false]);
                return redirect()->route('sales-mode')->with('success', 'Sales mode sudah dipakai, tidak bisa dihapus. Status diubah menjadi Non Aktif.');
            }

            return redirect()->route('sales-mode')->with('success', 'Sales mode sudah dipakai dan tetap Non Aktif.');
        }

        $salesMode->delete();
        return redirect()->route('sales-mode')->with('success', 'Sales mode berhasil dihapus.');
    }

    private function generateKode(): string
    {
        $prefix = 'SM-';
        $lastId = (int) SalesMode::query()->max('id');
        return $prefix . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
    }
}

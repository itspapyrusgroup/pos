<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\PesananPenjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanCustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->role || !$user->role->status) {
            abort(403, 'Akses ditolak: role tidak aktif atau tidak terdaftar.');
        }

        if (!$user->hasPermission('laporan.customer.read')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki izin melihat laporan customer.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'max:150'],
            'cabang_id' => ['nullable', 'exists:cabang,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort_by' => ['nullable', 'in:nama,no_hp,email,transaksi_count,total_spending,last_transaction_at'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        $query = PesananPenjualan::query()
            ->from('pesanan_penjualan as pz')
            ->leftJoin('pelanggan as pelanggan', 'pelanggan.id', '=', 'pz.pelanggan_id')
            ->leftJoin('cabang as c', 'c.id', '=', 'pz.cabang_id')
            ->selectRaw(implode(', ', [
                'COALESCE(pelanggan.id, 0) as pelanggan_id',
                'COALESCE(NULLIF(pelanggan.nama, \'\'), pz.customer_name) as nama',
                'COALESCE(NULLIF(pelanggan.no_hp, \'\'), pz.customer_phone) as no_hp',
                'pelanggan.email as email',
                'COUNT(DISTINCT pz.id) as transaksi_count',
                'COALESCE(SUM(pz.total), 0) as total_spending',
                'MAX(pz.created_at) as last_transaction_at',
                "GROUP_CONCAT(DISTINCT c.nama SEPARATOR ', ') as cabangs",
            ]))
            ->groupByRaw('pelanggan.id, pz.customer_phone, pz.customer_name');

        $this->applyCabangScope($query, 'pz.cabang_id');

        if (!empty($validated['cabang_id'])) {
            $query->where('pz.cabang_id', (int) $validated['cabang_id']);
        }

        if (!empty($validated['name'])) {
            $kw = '%' . trim((string) $validated['name']) . '%';
            $query->where(function ($q) use ($kw) {
                $q->where('pelanggan.nama', 'like', $kw)
                    ->orWhere('pz.customer_name', 'like', $kw);
            });
        }
        if (!empty($validated['phone'])) {
            $kw = '%' . trim((string) $validated['phone']) . '%';
            $query->where(function ($q) use ($kw) {
                $q->where('pelanggan.no_hp', 'like', $kw)
                    ->orWhere('pz.customer_phone', 'like', $kw);
            });
        }
        if (!empty($validated['email'])) {
            $kw = '%' . trim((string) $validated['email']) . '%';
            $query->where('pelanggan.email', 'like', $kw);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('pz.created_at', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $query->whereDate('pz.created_at', '<=', $validated['date_to']);
        }

        $sortBy = $validated['sort_by'] ?? 'last_transaction_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';

        // map friendly column names to select aliases/columns
        $columnMap = [
            'nama' => 'nama',
            'no_hp' => 'no_hp',
            'email' => 'email',
            'transaksi_count' => 'transaksi_count',
            'total_spending' => 'total_spending',
            'last_transaction_at' => 'last_transaction_at',
        ];

        $orderColumn = $columnMap[$sortBy] ?? 'last_transaction_at';
        $query->orderBy($orderColumn, $sortDir);

        $rows = $query->paginate(20)->withQueryString();

        return view('pages.laporan.customer', [
            'rows' => $rows,
            'filters' => $validated,
            'cabangs' => $this->accessibleCabangQuery()->get(['id', 'nama']),
        ]);
    }
}

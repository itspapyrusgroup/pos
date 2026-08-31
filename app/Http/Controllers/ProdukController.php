<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::query()->with('kategoriProduk')->latest('id');

        if ($request->filled('nama_item')) {
            $query->where('nama', 'like', '%' . $request->nama_item . '%');
        }

        if ($request->filled('kode_item')) {
            $query->where('kode', 'like', '%' . $request->kode_item . '%');
        }

        if ($request->filled('tipe')) {
            $tipe = strtoupper((string) $request->tipe);
            $query->whereHas('kategoriProduk', function ($builder) use ($tipe) {
                $builder->where('tipe', $tipe);
            });
        }

        if ($request->filled('golongan')) {
            $query->whereHas('kategoriProduk', function ($builder) use ($request) {
                $builder->where('nama', $request->golongan);
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        return view('pages.master.persediaan.barang_jasa.index', [
            'produk' => $query->paginate(25)->withQueryString(),
        ]);
    }
}

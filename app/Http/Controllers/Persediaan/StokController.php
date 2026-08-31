<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\KartuStok;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\StokCabang;
use Illuminate\Http\Request;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $cabangList = $this->accessibleCabangQuery()->get(['id', 'kode', 'nama']);
        $cabangId = (int) ($this->resolveCabangFilter($request) ?: ($cabangList->first()->id ?? 0));
        $tanggal = $request->input('tanggal') ?: now()->toDateString();
        $tanggalAkhir = $tanggal . ' 23:59:59';

        $query = Produk::query()
            ->with('kategoriProduk:id,kode,nama')
            ->whereHas('kategoriProduk', function ($q) {
                $q->where('tipe', 'BARANG');
            })
            ->where('track_stok', true)
            ->orderBy('nama');

        if ($request->filled('nama_barang')) {
            $query->where('nama', 'like', '%' . $request->input('nama_barang') . '%');
        }

        if ($request->filled('golongan_id')) {
            $query->where('kategori_produk_kode', $request->input('golongan_id'));
        }

        if ($cabangId > 0) {
            $query->addSelect([
                'stok_on_hand' => StokCabang::query()
                    ->selectRaw('COALESCE(qty, 0)')
                    ->whereColumn('produk_id', 'produk.id')
                    ->where('cabang_id', $cabangId)
                    ->limit(1),
            ]);
            $query->addSelect([
                'stok_on_order' => StokCabang::query()
                    ->selectRaw('COALESCE(qty_on_order, 0)')
                    ->whereColumn('produk_id', 'produk.id')
                    ->where('cabang_id', $cabangId)
                    ->limit(1),
            ]);
            $query->addSelect([
                'stok_tersedia' => StokCabang::query()
                    ->selectRaw('COALESCE(qty, 0) - COALESCE(qty_on_order, 0)')
                    ->whereColumn('produk_id', 'produk.id')
                    ->where('cabang_id', $cabangId)
                    ->limit(1),
            ]);

            $query->addSelect([
                'stok_per_tanggal' => KartuStok::query()
                    ->select('saldo_akhir')
                    ->whereColumn('produk_id', 'produk.id')
                    ->where('cabang_id', $cabangId)
                    ->where('tanggal_mutasi', '<=', $tanggalAkhir)
                    ->orderByDesc('tanggal_mutasi')
                    ->orderByDesc('id')
                    ->limit(1),
            ]);
        }

        $selectedDate = \Illuminate\Support\Carbon::parse($tanggal)->toDateString();

        $stokProduk = $query->paginate(15)->withQueryString();
        $stokProduk->getCollection()->transform(function ($item) use ($selectedDate) {
            $stokOnHand = (float) ($item->stok_on_hand ?? 0);
            $stokOnOrder = max(0, (float) ($item->stok_on_order ?? 0));
            $stokTersedia = (float) ($item->stok_tersedia ?? ($stokOnHand - $stokOnOrder));
            if ($item->stok_per_tanggal !== null) {
                $stokPerTanggal = (float) $item->stok_per_tanggal;
            } elseif ($selectedDate >= now()->toDateString()) {
                $stokPerTanggal = $stokOnHand;
            } else {
                $stokPerTanggal = 0.0;
            }

            $item->stok_on_hand = $stokOnHand;
            $item->stok_on_order = $stokOnOrder;
            $item->stok_tersedia = $stokTersedia;
            $item->stok_per_tanggal = $stokPerTanggal;
            return $item;
        });

        return view('pages.master.persediaan.stok.index', [
            'stokProduk' => $stokProduk,
            'cabangList' => $cabangList,
            'golonganList' => KategoriProduk::query()->orderBy('nama')->get(['kode', 'nama']),
            'selectedCabangId' => $cabangId,
            'selectedTanggal' => $tanggal,
        ]);
    }
}

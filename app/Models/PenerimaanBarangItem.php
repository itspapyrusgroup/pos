<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanBarangItem extends Model
{
    use HasFactory;

    protected $table = 'penerimaan_barang_item';

    protected $fillable = [
        'penerimaan_barang_id',
        'pesanan_pembelian_item_id',
        'produk_id',
        'qty_terima',
        'catatan',
    ];

    public function penerimaanBarang()
    {
        return $this->belongsTo(PenerimaanBarang::class, 'penerimaan_barang_id');
    }

    public function pesananPembelianItem()
    {
        return $this->belongsTo(PesananPembelianItem::class, 'pesanan_pembelian_item_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function returItems()
    {
        return $this->hasMany(ReturPembelianItem::class, 'penerimaan_barang_item_id');
    }
}

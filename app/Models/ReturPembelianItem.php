<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPembelianItem extends Model
{
    use HasFactory;

    protected $table = 'retur_pembelian_item';

    protected $fillable = [
        'retur_pembelian_id',
        'penerimaan_barang_item_id',
        'produk_id',
        'qty',
        'alasan_retur',
    ];

    public function returPembelian()
    {
        return $this->belongsTo(ReturPembelian::class, 'retur_pembelian_id');
    }

    public function penerimaanBarangItem()
    {
        return $this->belongsTo(PenerimaanBarangItem::class, 'penerimaan_barang_item_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}

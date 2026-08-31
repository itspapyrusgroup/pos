<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SalesMode;
use App\Models\TemplateHarga;

class CabangSalesMode extends Model
{
    use HasFactory;

    protected $table = 'cabang_sales_mode';

    protected $fillable = [
        'cabang_id',
        'sales_mode_id',
        'template_harga_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function salesMode()
    {
        return $this->belongsTo(SalesMode::class, 'sales_mode_id');
    }

    public function templateHarga()
    {
        return $this->belongsTo(TemplateHarga::class, 'template_harga_id');
    }
}

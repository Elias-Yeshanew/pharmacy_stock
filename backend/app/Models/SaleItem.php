<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id', 'medicine_id', 'quantity', 'unit_price', 'discount', 'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function medicine() { return $this->belongsTo(Medicine::class); }
}

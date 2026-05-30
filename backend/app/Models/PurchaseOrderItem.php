<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id', 'medicine_id', 'quantity_ordered', 'quantity_received',
        'unit_price', 'total_price', 'expiry_date', 'batch_number',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function medicine() { return $this->belongsTo(Medicine::class); }
}

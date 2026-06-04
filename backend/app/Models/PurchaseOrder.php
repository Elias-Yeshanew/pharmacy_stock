<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\BelongsToBranch;

class PurchaseOrder extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'order_number', 'supplier_id', 'status', 'order_date', 'expected_date',
        'received_date', 'total_amount', 'notes', 'user_id', 'branch_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
}

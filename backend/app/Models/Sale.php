<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\BelongsToBranch;

class Sale extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'invoice_number', 'customer_name', 'customer_phone', 'subtotal', 'discount',
        'total', 'payment_method', 'status', 'prescription_required',
        'prescription_number', 'notes', 'user_id', 'branch_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'prescription_required' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(SaleItem::class); }
}

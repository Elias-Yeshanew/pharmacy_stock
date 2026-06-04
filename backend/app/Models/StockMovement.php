<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\BelongsToBranch;

class StockMovement extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = [
        'medicine_id', 'branch_id', 'type', 'quantity', 'quantity_before', 'quantity_after',
        'unit_price', 'reference_number', 'batch_number', 'expiry_date', 'notes', 'user_id',
    ];

    protected $casts = ['expiry_date' => 'date', 'unit_price' => 'decimal:2'];

    public function medicine() { return $this->belongsTo(Medicine::class)->withTrashed(); } // load medicine even if soft-deleted
    public function user() { return $this->belongsTo(User::class); }
}

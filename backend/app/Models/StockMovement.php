<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_id', 'type', 'quantity', 'quantity_before', 'quantity_after',
        'unit_price', 'reference_number', 'batch_number', 'expiry_date', 'notes', 'user_id',
    ];

    protected $casts = ['expiry_date' => 'date', 'unit_price' => 'decimal:2'];

    public function medicine() { return $this->belongsTo(Medicine::class); }
    public function user() { return $this->belongsTo(User::class); }
}

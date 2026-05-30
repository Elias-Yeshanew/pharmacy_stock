<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'generic_name', 'barcode', 'sku', 'category_id', 'supplier_id',
        'dosage_form', 'strength', 'unit', 'purchase_price', 'selling_price',
        'stock_quantity', 'reorder_level', 'expiry_date', 'storage_conditions',
        'requires_prescription', 'is_active', 'description',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'expiry_date' => 'date',
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['stock_status', 'days_to_expiry'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0) return 'out_of_stock';
        if ($this->stock_quantity <= $this->reorder_level) return 'low_stock';
        return 'in_stock';
    }

    public function getDaysToExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) return null;
        return now()->diffInDays($this->expiry_date, false);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= reorder_level')->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    public function scopeExpiringSoon($query, $days = 90)
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->whereDate('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now());
    }
}

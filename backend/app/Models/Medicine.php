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
        'reorder_level', 'expiry_date', 'storage_conditions',
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

    protected $with = ['activeBranchStock'];

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

    public function branchStocks()
    {
        return $this->hasMany(MedicineBranch::class);
    }

    public function activeBranchStock()
    {
        return $this->hasOne(MedicineBranch::class)->where('branch_id', \App\Helpers\BranchHelper::getActiveBranchId());
    }

    public function getStockQuantityAttribute()
    {
        if ($this->relationLoaded('activeBranchStock')) {
            return $this->activeBranchStock?->stock_quantity ?? 0;
        }
        return $this->activeBranchStock()->value('stock_quantity') ?? 0;
    }

    public function getReorderLevelAttribute()
    {
        if ($this->relationLoaded('activeBranchStock')) {
            return $this->activeBranchStock?->reorder_level ?? $this->attributes['reorder_level'] ?? 10;
        }
        return $this->activeBranchStock()->value('reorder_level') ?? $this->attributes['reorder_level'] ?? 10;
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
        return $query->whereHas('activeBranchStock', function ($q) {
            $q->whereColumn('stock_quantity', '<=', 'reorder_level')
              ->where('stock_quantity', '>', 0);
        });
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereDoesntHave('activeBranchStock')
            ->orWhereHas('activeBranchStock', function ($q) {
                $q->where('stock_quantity', '<=', 0);
            });
    }

    public function scopeExpiringSoon($query, $days = 90)
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->whereDate('expiry_date', '>=', now())
            ->whereHas('activeBranchStock', function ($q) {
                $q->where('stock_quantity', '>', 0);
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now())
            ->whereHas('activeBranchStock', function ($q) {
                $q->where('stock_quantity', '>', 0);
            });
    }

    public function adjustStockForBranch(
        $branchId,
        $quantity,
        $type,
        $notes = null,
        $batchNumber = null,
        $expiryDate = null,
        $referenceNumber = null,
        $unitPrice = null,
        $userId = null
    ) {
        return \DB::transaction(function () use ($branchId, $quantity, $type, $notes, $batchNumber, $expiryDate, $referenceNumber, $unitPrice, $userId) {
            $pivot = \DB::table('medicine_branch')
                ->where('medicine_id', $this->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            $qtyBefore = $pivot ? $pivot->stock_quantity : 0;

            if (in_array($type, ['out', 'expired', 'adjustment_minus'])) {
                $qtyAfter = $qtyBefore - $quantity;
            } else {
                $qtyAfter = $qtyBefore + $quantity;
            }

            if ($qtyAfter < 0) {
                throw new \Exception("Insufficient stock at this branch.");
            }

            \DB::table('medicine_branch')->updateOrInsert(
                ['medicine_id' => $this->id, 'branch_id' => $branchId],
                [
                    'stock_quantity' => $qtyAfter,
                    'reorder_level' => $pivot ? $pivot->reorder_level : ($this->attributes['reorder_level'] ?? 10),
                    'updated_at' => now()
                ]
            );

            // Record movement
            StockMovement::create([
                'medicine_id' => $this->id,
                'branch_id' => $branchId,
                'type' => in_array($type, ['adjustment_minus', 'adjustment_plus']) ? 'adjustment' : $type,
                'quantity' => $quantity,
                'quantity_before' => $qtyBefore,
                'quantity_after' => $qtyAfter,
                'reference_number' => $referenceNumber,
                'unit_price' => $unitPrice,
                'notes' => $notes,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'user_id' => $userId ?? auth()->id(),
            ]);

            return $qtyAfter;
        });
    }
}

<?php

namespace App\Traits;

use App\Helpers\BranchHelper;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch()
    {
        static::creating(function ($model) {
            if (!$model->branch_id) {
                $model->branch_id = BranchHelper::getActiveBranchId();
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = BranchHelper::getActiveBranchId();
            if ($branchId) {
                $builder->where((new static)->getTable() . '.branch_id', $branchId);
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

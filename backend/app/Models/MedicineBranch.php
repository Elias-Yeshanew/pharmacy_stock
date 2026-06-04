<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineBranch extends Model
{
    protected $table = 'medicine_branch';

    protected $fillable = ['medicine_id', 'branch_id', 'stock_quantity', 'reorder_level'];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

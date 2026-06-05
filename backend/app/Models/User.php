<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'branch_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed'];

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isDispenser(): bool { return $this->role === 'dispenser' || $this->role === 'pharmacist'; }
    public function isSalesManager(): bool { return $this->role === 'sales_manager'; }
    public function isSupplyChainManager(): bool { return $this->role === 'supply_chain_manager'; }
    public function isOwnerOrCeo(): bool { return in_array($this->role, ['owner', 'ceo']); }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales() { return $this->hasMany(Sale::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
}

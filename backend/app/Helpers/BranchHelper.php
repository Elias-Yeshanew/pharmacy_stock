<?php

namespace App\Helpers;

use App\Models\Branch;

class BranchHelper
{
    public static function getActiveBranchId()
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // Non-admin is strictly locked to their branch
        if ($user->role !== 'admin') {
            return $user->branch_id;
        }

        // Admin can switch branches via X-Branch-Id header
        $headerBranchId = request()->header('X-Branch-Id');
        if ($headerBranchId) {
            return (int) $headerBranchId;
        }

        // Fallback: Admin's own branch, or first branch, or null
        return $user->branch_id ?? Branch::first()?->id;
    }
}

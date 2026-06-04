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

        // Check query parameter override first
        $queryBranchId = request()->query('branch_id');
        if ($queryBranchId !== null) {
            if ($queryBranchId === 'all') {
                return null;
            }
            return (int) $queryBranchId;
        }

        // Check header value
        $headerBranchId = request()->header('X-Branch-Id');
        if ($headerBranchId !== null) {
            if ($headerBranchId === 'all') {
                return null;
            }
            return (int) $headerBranchId;
        }

        // Default for Admin is "All Branches" (null)
        return null;
    }
}

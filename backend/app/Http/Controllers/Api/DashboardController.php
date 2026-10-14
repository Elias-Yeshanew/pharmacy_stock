<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $activeBranchId = \App\Helpers\BranchHelper::getActiveBranchId();
        $cacheKey = 'dashboard_data_' . ($activeBranchId ?: 'all') . '_' . ($user ? $user->id : 'guest');

        // Cache dashboard data for 30 seconds to prevent hammering remote cloud database
        return response()->json(Cache::remember($cacheKey, 30, function () use ($user, $activeBranchId) {
            $totalMedicines = Medicine::where('is_active', true)->count();
            $lowStockCount = Medicine::lowStock()->count();
            $outOfStockCount = Medicine::outOfStock()->count();
            $expiringSoonCount = Medicine::expiringSoon(90)->count();
            $expiredCount = Medicine::expired()->count();

            $todaySales = Sale::whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('total');

            $monthSales = Sale::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('status', 'completed')
                ->sum('total');

            $totalInventoryValueQuery = DB::table('medicines')
                ->join('medicine_branch', 'medicines.id', '=', 'medicine_branch.medicine_id')
                ->where('medicines.is_active', true)
                ->whereNull('medicines.deleted_at');

            if ($activeBranchId) {
                $totalInventoryValueQuery->where('medicine_branch.branch_id', $activeBranchId);
            }

            $totalInventoryValue = $totalInventoryValueQuery
                ->selectRaw('SUM(medicine_branch.stock_quantity * medicines.purchase_price) as value')
                ->value('value') ?? 0;

            $recentMovements = StockMovement::with(['medicine:id,name,unit', 'user:id,name'])
                ->latest()
                ->take(10)
                ->get();

            $lowStockMedicines = Medicine::with(['category:id,name'])
                ->lowStock()
                ->take(5)
                ->get();

            $expiringSoon = Medicine::with(['category:id,name'])
                ->expiringSoon(90)
                ->orderBy('expiry_date')
                ->take(5)
                ->get();

            // Monthly sales chart (last 6 months)
            $monthlySales = Sale::where('status', 'completed')
                ->where('created_at', '>=', now()->subMonths(6))
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total) as total, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            // Calculate branch performance summaries in 4 bulk queries instead of 4*N loop queries
            $branchSummaries = [];
            if ($user && in_array($user->role, ['admin', 'owner', 'ceo'])) {
                $branches = Branch::where('is_active', true)->get(['id', 'name']);
                $branchIds = $branches->pluck('id')->all();

                if (!empty($branchIds)) {
                    // Bulk query 1: Today's sales by branch
                    $todaySalesMap = Sale::withoutGlobalScope('branch')
                        ->whereIn('branch_id', $branchIds)
                        ->whereDate('created_at', today())
                        ->where('status', 'completed')
                        ->groupBy('branch_id')
                        ->selectRaw('branch_id, SUM(total) as total')
                        ->pluck('total', 'branch_id');

                    // Bulk query 2: Month's sales by branch
                    $monthSalesMap = Sale::withoutGlobalScope('branch')
                        ->whereIn('branch_id', $branchIds)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->where('status', 'completed')
                        ->groupBy('branch_id')
                        ->selectRaw('branch_id, SUM(total) as total')
                        ->pluck('total', 'branch_id');

                    // Bulk query 3: Inventory values by branch
                    $inventoryMap = DB::table('medicines')
                        ->join('medicine_branch', 'medicines.id', '=', 'medicine_branch.medicine_id')
                        ->whereIn('medicine_branch.branch_id', $branchIds)
                        ->where('medicines.is_active', true)
                        ->whereNull('medicines.deleted_at')
                        ->groupBy('medicine_branch.branch_id')
                        ->selectRaw('medicine_branch.branch_id, SUM(medicine_branch.stock_quantity * medicines.purchase_price) as value')
                        ->pluck('value', 'branch_id');

                    // Bulk query 4: Low stock counts by branch
                    $lowStockMap = DB::table('medicine_branch')
                        ->join('medicines', 'medicines.id', '=', 'medicine_branch.medicine_id')
                        ->whereIn('medicine_branch.branch_id', $branchIds)
                        ->where('medicines.is_active', true)
                        ->whereNull('medicines.deleted_at')
                        ->whereColumn('medicine_branch.stock_quantity', '<=', 'medicine_branch.reorder_level')
                        ->where('medicine_branch.stock_quantity', '>', 0)
                        ->groupBy('medicine_branch.branch_id')
                        ->selectRaw('medicine_branch.branch_id, COUNT(*) as count')
                        ->pluck('count', 'branch_id');

                    foreach ($branches as $branch) {
                        $branchSummaries[] = [
                            'id' => $branch->id,
                            'name' => $branch->name,
                            'today_sales' => round($todaySalesMap[$branch->id] ?? 0, 2),
                            'month_sales' => round($monthSalesMap[$branch->id] ?? 0, 2),
                            'inventory_value' => round($inventoryMap[$branch->id] ?? 0, 2),
                            'low_stock' => (int) ($lowStockMap[$branch->id] ?? 0),
                        ];
                    }
                }
            }

            return [
                'stats' => [
                    'total_medicines' => $totalMedicines,
                    'low_stock' => $lowStockCount,
                    'out_of_stock' => $outOfStockCount,
                    'expiring_soon' => $expiringSoonCount,
                    'expired' => $expiredCount,
                    'today_sales' => round($todaySales, 2),
                    'month_sales' => round($monthSales, 2),
                    'inventory_value' => round($totalInventoryValue, 2),
                ],
                'recent_movements' => $recentMovements,
                'low_stock_medicines' => $lowStockMedicines,
                'expiring_soon' => $expiringSoon,
                'monthly_sales' => $monthlySales,
                'branch_summaries' => $branchSummaries,
            ];
        }));
    }
}

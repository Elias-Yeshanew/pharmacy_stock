<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
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

        $activeBranchId = \App\Helpers\BranchHelper::getActiveBranchId();
        
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

        $recentMovements = StockMovement::with(['medicine', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $lowStockMedicines = Medicine::with(['category'])
            ->lowStock()
            ->take(5)
            ->get();

        $expiringSoon = Medicine::with(['category'])
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

        // Calculate branch performance summaries for Admin, Owner, and CEO
        $branchSummaries = [];
        $user = auth()->user();
        if ($user && in_array($user->role, ['admin', 'owner', 'ceo'])) {
            $branches = \App\Models\Branch::where('is_active', true)->get();
            foreach ($branches as $branch) {
                // Today's sales
                $branchTodaySales = Sale::withoutGlobalScope('branch')
                    ->where('branch_id', $branch->id)
                    ->whereDate('created_at', today())
                    ->where('status', 'completed')
                    ->sum('total');

                // Month's sales
                $branchMonthSales = Sale::withoutGlobalScope('branch')
                    ->where('branch_id', $branch->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', 'completed')
                    ->sum('total');

                // Inventory value
                $branchInventoryValue = DB::table('medicines')
                    ->join('medicine_branch', 'medicines.id', '=', 'medicine_branch.medicine_id')
                    ->where('medicine_branch.branch_id', $branch->id)
                    ->where('medicines.is_active', true)
                    ->whereNull('medicines.deleted_at')
                    ->selectRaw('SUM(medicine_branch.stock_quantity * medicines.purchase_price) as value')
                    ->value('value') ?? 0;

                // Low stock items
                $branchLowStock = Medicine::whereHas('branchStocks', function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id)
                      ->whereColumn('stock_quantity', '<=', 'reorder_level')
                      ->where('stock_quantity', '>', 0);
                })->count();

                $branchSummaries[] = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'today_sales' => round($branchTodaySales, 2),
                    'month_sales' => round($branchMonthSales, 2),
                    'inventory_value' => round($branchInventoryValue, 2),
                    'low_stock' => $branchLowStock,
                ];
            }
        }

        return response()->json([
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
        ]);
    }
}

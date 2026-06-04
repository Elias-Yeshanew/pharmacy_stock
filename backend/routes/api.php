<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\BranchController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Branches
    Route::apiResource('branches', BranchController::class);

    // Medicines
    Route::apiResource('medicines', MedicineController::class);
    Route::post('/medicines/{medicine}/adjust-stock', [MedicineController::class, 'adjustStock']);

    // Categories
    Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class);

    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);

    // Purchase Orders
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{purchaseOrder}/receive',       [PurchaseOrderController::class, 'receive']);
    Route::patch('/purchase-orders/{purchaseOrder}/status',       [PurchaseOrderController::class, 'updateStatus']);

    // Sales
    Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);
});

// Stock Movements (read-only, driven by adjustStock / sales / purchase orders)
Route::middleware('auth:sanctum')->get('/stock-movements', function(\Illuminate\Http\Request $request) {
    $query = \App\Models\StockMovement::with(['medicine','user'])->latest();
    if ($request->search) {
        $query->whereHas('medicine', fn($q) => $q->where('name','like',"%{$request->search}%"))
              ->orWhere('reference_number','like',"%{$request->search}%");
    }
    if ($request->type) $query->where('type', $request->type);
    if ($request->date_from) $query->whereDate('created_at','>=',$request->date_from);
    if ($request->date_to)   $query->whereDate('created_at','<=',$request->date_to);
    return response()->json($query->paginate($request->per_page ?? 20));
});
// Temporary Database Verification Route
Route::get('/db-test', function (\Illuminate\Http\Request $request) {
    try {
        \DB::connection()->getPdo();
        
        if ($request->has('force_fresh')) {
            \Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            $action = 'Database successfully reset, migrated and seeded!';
        }
        // Automaticaly run migrations and seeder if database tables are empty
        elseif (!\Schema::hasTable('users') || \App\Models\User::count() === 0) {
            \Artisan::call('migrate', ['--force' => true]);
            \Artisan::call('db:seed', ['--force' => true]);
            $action = 'Database successfully migrated and seeded!';
        } else {
            $action = 'Database connection active and already populated!';
        }

        $medicinesCount = \App\Models\Medicine::count();
        return response()->json([
            'status' => 'success',
            'message' => $action,
            'database_name' => \DB::connection()->getDatabaseName(),
            'medicines_in_db' => $medicinesCount,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to connect or seed: ' . $e->getMessage()
        ], 500);
    }
});

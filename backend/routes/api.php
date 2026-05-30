<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SupplierController;
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

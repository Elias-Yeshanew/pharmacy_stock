<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $query = Medicine::with(['category', 'supplier'])->where('is_active', true);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('generic_name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('barcode', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->stock_status === 'low_stock') {
            $query->lowStock();
        } elseif ($request->stock_status === 'out_of_stock') {
            $query->outOfStock();
        } elseif ($request->stock_status === 'expiring_soon') {
            $query->expiringSoon();
        } elseif ($request->stock_status === 'expired') {
            $query->expired();
        }

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|unique:medicines',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'dosage_form' => 'required|string',
            'strength' => 'nullable|string',
            'unit' => 'required|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
            'requires_prescription' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $stockQuantity = $validated['stock_quantity'];
        $reorderLevel = $validated['reorder_level'];
        unset($validated['stock_quantity'], $validated['reorder_level']);

        $validated['sku'] = 'MED-' . strtoupper(Str::random(8));
        $validated['reorder_level'] = $reorderLevel;

        return \DB::transaction(function () use ($validated, $stockQuantity, $reorderLevel) {
            $medicine = Medicine::create($validated);

            $activeBranchId = \App\Helpers\BranchHelper::getActiveBranchId();
            if ($activeBranchId) {
                // Initialize the pivot record
                \DB::table('medicine_branch')->insert([
                    'medicine_id' => $medicine->id,
                    'branch_id' => $activeBranchId,
                    'stock_quantity' => 0,
                    'reorder_level' => $reorderLevel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($stockQuantity > 0) {
                    $medicine->adjustStockForBranch(
                        $activeBranchId,
                        $stockQuantity,
                        'in',
                        'Initial stock',
                        null,
                        $validated['expiry_date'] ?? null,
                        null,
                        null,
                        auth()->id()
                    );
                }
            }

            return response()->json($medicine->load(['category', 'supplier', 'activeBranchStock']), 201);
        });
    }

    public function show(Medicine $medicine)
    {
        return response()->json($medicine->load(['category', 'supplier', 'stockMovements.user']));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|unique:medicines,barcode,' . $medicine->id,
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'dosage_form' => 'required|string',
            'strength' => 'nullable|string',
            'unit' => 'required|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
            'requires_prescription' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $reorderLevel = $validated['reorder_level'];
        $medicine->update($validated);

        $activeBranchId = \App\Helpers\BranchHelper::getActiveBranchId();
        if ($activeBranchId) {
            \DB::table('medicine_branch')->updateOrInsert(
                ['medicine_id' => $medicine->id, 'branch_id' => $activeBranchId],
                ['reorder_level' => $reorderLevel, 'updated_at' => now()]
            );
        }

        return response()->json($medicine->load(['category', 'supplier', 'activeBranchStock']));
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->delete();
        return response()->json(['message' => 'Medicine deleted successfully']);
    }

    public function adjustStock(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment,return,expired',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
        ]);

        try {
            $activeBranchId = \App\Helpers\BranchHelper::getActiveBranchId();

            $medicine->adjustStockForBranch(
                $activeBranchId,
                $validated['quantity'],
                $validated['type'],
                $validated['notes'] ?? null,
                $validated['batch_number'] ?? null,
                $validated['expiry_date'] ?? null,
                null,
                null,
                auth()->id()
            );

            return response()->json($medicine->fresh());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}

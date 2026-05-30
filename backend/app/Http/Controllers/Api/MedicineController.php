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

        $validated['sku'] = 'MED-' . strtoupper(Str::random(8));

        $medicine = Medicine::create($validated);

        // Record initial stock movement
        if ($validated['stock_quantity'] > 0) {
            StockMovement::create([
                'medicine_id' => $medicine->id,
                'type' => 'in',
                'quantity' => $validated['stock_quantity'],
                'quantity_before' => 0,
                'quantity_after' => $validated['stock_quantity'],
                'notes' => 'Initial stock',
                'user_id' => auth()->id(),
            ]);
        }

        return response()->json($medicine->load(['category', 'supplier']), 201);
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

        $medicine->update($validated);

        return response()->json($medicine->load(['category', 'supplier']));
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

        $quantityBefore = $medicine->stock_quantity;

        if (in_array($validated['type'], ['out', 'expired'])) {
            if ($medicine->stock_quantity < $validated['quantity']) {
                return response()->json(['message' => 'Insufficient stock'], 422);
            }
            $medicine->stock_quantity -= $validated['quantity'];
        } else {
            $medicine->stock_quantity += $validated['quantity'];
        }

        $medicine->save();

        StockMovement::create([
            'medicine_id' => $medicine->id,
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'quantity_before' => $quantityBefore,
            'quantity_after' => $medicine->stock_quantity,
            'notes' => $validated['notes'] ?? null,
            'batch_number' => $validated['batch_number'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return response()->json($medicine->fresh());
    }
}

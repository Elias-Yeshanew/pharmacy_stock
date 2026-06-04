<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'user'])->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'order_date'    => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'notes'         => 'nullable|string',
            'items'         => 'required|array|min:1',
            'items.*.medicine_id'   => 'required|exists:medicines,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.expiry_date'   => 'nullable|date',
            'items.*.batch_number'  => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $totalAmount = collect($validated['items'])
                ->sum(fn($i) => $i['quantity_ordered'] * $i['unit_price']);

            $order = PurchaseOrder::create([
                'order_number'  => 'PO-' . strtoupper(Str::random(8)),
                'supplier_id'   => $validated['supplier_id'],
                'status'        => 'pending',
                'order_date'    => $validated['order_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'total_amount'  => $totalAmount,
                'notes'         => $validated['notes'] ?? null,
                'user_id'       => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'medicine_id'       => $item['medicine_id'],
                    'quantity_ordered'  => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_price'        => $item['unit_price'],
                    'total_price'       => $item['quantity_ordered'] * $item['unit_price'],
                    'expiry_date'       => $item['expiry_date'] ?? null,
                    'batch_number'      => $item['batch_number'] ?? null,
                ]);
            }

            return response()->json($order->load(['supplier', 'items.medicine', 'user']), 201);
        });
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return response()->json($purchaseOrder->load(['supplier', 'items.medicine', 'user']));
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return response()->json(['message' => 'Order already received'], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id'                => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $purchaseOrder) {
            foreach ($validated['items'] as $itemData) {
                $item = PurchaseOrderItem::findOrFail($itemData['id']);
                $item->update(['quantity_received' => $itemData['quantity_received']]);

                if ($itemData['quantity_received'] > 0) {
                    $medicine = Medicine::findOrFail($item->medicine_id);

                    if ($item->expiry_date) {
                        $medicine->update(['expiry_date' => $item->expiry_date]);
                    }

                    $medicine->adjustStockForBranch(
                        $purchaseOrder->branch_id,
                        $itemData['quantity_received'],
                        'in',
                        'Purchase Order: ' . $purchaseOrder->order_number,
                        $item->batch_number,
                        $item->expiry_date,
                        $purchaseOrder->order_number,
                        $item->unit_price,
                        auth()->id()
                    );
                }
            }

            $purchaseOrder->update([
                'status'        => 'received',
                'received_date' => now()->toDateString(),
            ]);

            return response()->json($purchaseOrder->load(['supplier', 'items.medicine']));
        });
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,ordered,received,cancelled',
        ]);

        $purchaseOrder->update($validated);
        return response()->json($purchaseOrder);
    }
}

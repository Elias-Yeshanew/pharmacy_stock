<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['user', 'items.medicine'])->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return response()->json($query->paginate($request->per_page ?? 20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'payment_method' => 'required|in:cash,card,mobile_money',
            'discount' => 'numeric|min:0',
            'prescription_required' => 'boolean',
            'prescription_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $saleItems = [];

            foreach ($validated['items'] as $item) {
                $medicine = Medicine::lockForUpdate()->findOrFail($item['medicine_id']);

                if ($medicine->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$medicine->name}. Available: {$medicine->stock_quantity}");
                }

                $itemDiscount = $item['discount'] ?? 0;
                $totalPrice = ($item['unit_price'] * $item['quantity']) - $itemDiscount;
                $subtotal += $totalPrice;

                $saleItems[] = [
                    'medicine' => $medicine,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'total_price' => $totalPrice,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $total = $subtotal - $discount;

            $sale = Sale::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(10)),
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
                'prescription_required' => $validated['prescription_required'] ?? false,
                'prescription_number' => $validated['prescription_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => auth()->id(),
            ]);

            foreach ($saleItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'medicine_id' => $item['medicine']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'total_price' => $item['total_price'],
                ]);

                $item['medicine']->adjustStockForBranch(
                    $sale->branch_id,
                    $item['quantity'],
                    'out',
                    'Sale: ' . $sale->invoice_number,
                    null,
                    null,
                    $sale->invoice_number,
                    null,
                    auth()->id()
                );
            }

            return response()->json($sale->load(['items.medicine', 'user']), 201);
        });
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load(['items.medicine', 'user']));
    }
}

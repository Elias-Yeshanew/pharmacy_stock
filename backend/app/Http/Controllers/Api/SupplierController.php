<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withCount('medicines');

        if ($request->search) {
            // Don't cache search results
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
            return response()->json($query->paginate($request->per_page ?? 20));
        }

        // Cache the plain paginated list (page + per_page key) for 5 min
        $cacheKey = 'suppliers_page_' . ($request->per_page ?? 20) . '_' . ($request->page ?? 1);
        return response()->json(
            Cache::remember($cacheKey, 300, fn () => $query->paginate($request->per_page ?? 20))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|unique:suppliers',
            'address'        => 'nullable|string',
            'is_active'      => 'boolean',
        ]);

        $supplier = Supplier::create($validated);
        $this->clearSupplierCache();
        return response()->json($supplier, 201);
    }

    private function clearSupplierCache(): void
    {
        // Clear paginated cache keys (up to 10 pages)
        foreach ([10, 15, 20, 50] as $perPage) {
            for ($p = 1; $p <= 10; $p++) {
                Cache::forget("suppliers_page_{$perPage}_{$p}");
            }
        }
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier->load('medicines'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'address'        => 'nullable|string',
            'is_active'      => 'boolean',
        ]);

        $supplier->update($validated);
        $this->clearSupplierCache();
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->medicines()->count()) {
            return response()->json(['message' => 'Cannot delete supplier with associated medicines'], 422);
        }
        $supplier->delete();
        $this->clearSupplierCache();
        return response()->json(['message' => 'Supplier deleted']);
    }
}

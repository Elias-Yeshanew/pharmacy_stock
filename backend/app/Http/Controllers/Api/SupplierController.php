<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withCount('medicines');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->paginate($request->per_page ?? 20));
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

        return response()->json(Supplier::create($validated), 201);
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
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->medicines()->count()) {
            return response()->json(['message' => 'Cannot delete supplier with associated medicines'], 422);
        }
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted']);
    }
}

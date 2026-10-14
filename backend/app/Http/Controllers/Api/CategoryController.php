<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Cache::remember('all_categories_with_count', 300, function () {
            return Category::withCount('medicines')->get();
        }));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
        ]);
        Cache::forget('all_categories_with_count');
        return response()->json(Category::create($validated), 201);
    }

    public function show(Category $category)
    {
        return response()->json($category->load('medicines'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);
        $category->update($validated);
        Cache::forget('all_categories_with_count');
        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        if ($category->medicines()->count()) {
            return response()->json(['message' => 'Cannot delete category with associated medicines'], 422);
        }
        $category->delete();
        Cache::forget('all_categories_with_count');
        return response()->json(['message' => 'Category deleted']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BranchController extends Controller
{
    public function index()
    {
        // Branches rarely change — cache for 5 minutes to avoid repeated DB calls
        $branches = Cache::remember('all_branches_with_user_count', 300, function () {
            return Branch::withCount(['users'])->get();
        });

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255|unique:branches',
            'address'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'is_active'=> 'boolean',
        ]);

        $branch = Branch::create($validated);
        Cache::forget('all_branches_with_user_count');

        return response()->json($branch, 201);
    }

    public function show(Branch $branch)
    {
        return response()->json($branch->load(['users']));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'address'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'is_active'=> 'boolean',
        ]);

        $branch->update($validated);
        Cache::forget('all_branches_with_user_count');

        return response()->json($branch);
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete branch with associated users.'], 422);
        }
        if ($branch->sales()->count() > 0) {
            return response()->json(['message' => 'Cannot delete branch with associated sales records.'], 422);
        }

        $branch->delete();
        Cache::forget('all_branches_with_user_count');

        return response()->json(['message' => 'Branch deleted successfully.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('branch')->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'role'      => 'required|string|in:admin,dispenser,supply_chain_manager,sales_manager,owner,ceo,pharmacist,cashier',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Clear branch for global roles
        if (in_array($validated['role'], ['admin', 'owner', 'ceo', 'supply_chain_manager', 'sales_manager'])) {
            $validated['branch_id'] = null;
        }

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],
            'branch_id' => $validated['branch_id'],
        ]);

        return response()->json($user->load('branch'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user->load('branch'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password'  => 'nullable|string|min:8',
            'role'      => 'required|string|in:admin,dispenser,supply_chain_manager,sales_manager,owner,ceo,pharmacist,cashier',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Clear branch for global roles
        if (in_array($validated['role'], ['admin', 'owner', 'ceo', 'supply_chain_manager', 'sales_manager'])) {
            $validated['branch_id'] = null;
        }

        $data = [
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'role'      => $validated['role'],
            'branch_id' => $validated['branch_id'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return response()->json($user->load('branch'));
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'You cannot delete your own profile.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}

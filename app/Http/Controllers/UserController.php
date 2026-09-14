<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->role === 'admin' || $user->role === 'manager') {
            $users = User::with('branches')->orderBy('name')->get();
            $branches = \App\Models\Branch::with('company')->orderBy('name')->get();
        } else {
            $users = User::with('branches')->where('id', $user->id)->get();
            $branches = $user->branches()->with('company')->orderBy('name')->get();
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'role' => 'required|string|in:admin,manager,multi branch manager,cashier,user',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        if (!isset($validated['is_active'])) $validated['is_active'] = false;
        
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if ($request->has('branch_ids')) {
            $user->branches()->sync($request->branch_ids);
        }

        return back();
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'is_active' => 'boolean',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'role' => 'required|string|in:admin,manager,multi branch manager,cashier,user',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        if (!isset($validated['is_active'])) $validated['is_active'] = false;

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        
        if ($request->has('branch_ids')) {
            $user->branches()->sync($request->branch_ids);
        } else {
            $user->branches()->detach();
        }

        return back();
    }

    public function destroy(User $user)
    {
        // Prevent deleting oneself
        if (auth()->id() === $user->id) {
            return back()->withErrors(['message' => 'You cannot delete yourself.']);
        }

        $user->delete();
        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * List all users — admin only.
     */
    public function index(): View
    {
        $users = User::orderBy('role')->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    /**
     * Show create user form.
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Store a new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:200',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role'     => 'required|in:admin,manager,cashier',
        ]);

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', "User '{$data['name']}' created as {$data['role']}.");
    }

    /**
     * Show edit form.
     */
    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update user details and role.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Prevent admin from removing their own admin role
        if ($user->id === auth()->id() && $request->role !== 'admin') {
            return back()->with('error', 'You cannot change your own role.');
        }

        $data = $request->validate([
            'name'  => 'required|string|max:200',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:admin,manager,cashier',
        ]);

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' updated.");
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => $data['password']]);

        return redirect()->route('users.index')
            ->with('success', "Password reset for '{$user->name}'.");
    }

    /**
     * Deactivate (soft delete) a user.
     * Cannot delete yourself or the last admin.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $adminCount = User::where('role', 'admin')->count();
        if ($user->role === 'admin' && $adminCount <= 1) {
            return back()->with('error', 'Cannot delete the last admin account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User '{$name}' deleted.");
    }
}

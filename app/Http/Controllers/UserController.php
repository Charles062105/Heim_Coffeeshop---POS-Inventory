<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'role' => ['nullable', 'in:owner,manager,cashier'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $query = User::orderBy('name');
        if ($role = $filters['role'] ?? null) {
            $query->where('role', $role);
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        $users = $query->paginate(20)->withQueryString();
        $roles = ['owner', 'manager', 'cashier'];

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = ['owner', 'manager', 'cashier'];

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:owner,manager,cashier',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 'active',
        ]);

        AuditService::logFromUser($request->user(), 'created_user', 'Users', [
            'new_user' => $user->name, 'role' => $user->role,
        ], $user);

        return redirect()->route('users.index')->with('success', "User \"{$user->name}\" created.");
    }

    public function edit(User $user)
    {
        $roles = ['owner', 'manager', 'cashier'];

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => 'nullable|in:owner,manager,cashier',
        ]);

        $data = ['name' => $request->name, 'email' => $request->email];
        if ($request->filled('role') && auth()->user()->isOwner() && auth()->id() !== $user->id) {
            $data['role'] = $request->role;
        }
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        AuditService::logFromUser($request->user(), 'updated_user', 'Users', [
            'updated_user' => $user->name, 'role' => $user->role,
        ], $user);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function toggle(User $user)
    {
        if ($user->id === request()->user()->id) {
            return back()->with('error', 'You cannot archive your own account.');
        }
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        $status = $user->status === 'active' ? 'unarchived' : 'archived';
        AuditService::logFromUser(request()->user(), 'toggled_user_status', 'Users', [
            'user' => $user->name, 'status' => $user->status,
        ], $user);

        return back()->with('success', "User \"{$user->name}\" is now {$status}.");
    }
}

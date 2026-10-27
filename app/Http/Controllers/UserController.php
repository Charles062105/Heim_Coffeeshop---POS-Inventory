<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'role' => ['nullable', 'in:owner,manager,cashier'],
            'status' => ['nullable', 'in:active,inactive'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
        $query = User::with('branch')->orderBy('name');
        if ($role = $filters['role'] ?? null) {
            $query->where('role', $role);
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($branchId = $filters['branch_id'] ?? null) {
            $query->where('branch_id', $branchId);
        }
        $users = $query->paginate(20)->withQueryString();
        $roles = ['owner', 'manager', 'cashier'];
        $branches = Branch::orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'branches'));
    }

    public function create()
    {
        $roles = ['owner', 'manager', 'cashier'];
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.create', compact('roles', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => 'required|in:owner,manager,cashier',
            'branch_id' => ['required_if:role,manager,cashier', 'nullable', Rule::exists('branches', 'id')->where('is_active', true)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 'active',
            'branch_id' => $request->input('branch_id') ?: Branch::where('is_active', true)->orderBy('id')->value('id'),
        ]);

        AuditService::logFromUser($request->user(), 'created_user', 'Users', [
            'new_user' => $user->name, 'role' => $user->role,
        ], $user);

        return redirect()->route('users.index')->with('success', "User \"{$user->name}\" created.");
    }

    public function edit(User $user)
    {
        $roles = ['owner', 'manager', 'cashier'];
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles', 'branches'));
    }

    public function update(Request $request, User $user)
    {
        $targetRole = $request->input('role', $user->role);
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => 'nullable|in:owner,manager,cashier',
            'branch_id' => [
                'sometimes',
                $targetRole === 'owner' ? 'nullable' : 'required',
                Rule::exists('branches', 'id')->where('is_active', true),
            ],
        ]);

        $data = ['name' => $request->name, 'email' => $request->email];
        if ($request->exists('branch_id')) {
            $data['branch_id'] = $request->input('branch_id');
        }
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

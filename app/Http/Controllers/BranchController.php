<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('users')->orderBy('name')->get();

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('branches', 'code')],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $branch = Branch::create($data + ['is_active' => true]);
        AuditService::logFromUser($request->user(), 'created_branch', 'Branches', [
            'branch' => $branch->name,
            'code' => $branch->code,
        ], $branch);

        return redirect()->route('branches.index')->with('success', "Branch \"{$branch->name}\" created.");
    }

    public function toggle(Request $request, Branch $branch)
    {
        if ($branch->is_active && $branch->users()->where('status', 'active')->exists()) {
            return back()->with('error', 'Reassign or archive this branch’s active accounts before deactivating it.');
        }

        if ($branch->is_active && Branch::where('is_active', true)->count() <= 1) {
            return back()->with('error', 'At least one branch must remain active.');
        }

        $branch->update(['is_active' => ! $branch->is_active]);
        AuditService::logFromUser($request->user(), 'toggled_branch_status', 'Branches', [
            'branch' => $branch->name,
            'is_active' => $branch->is_active,
        ], $branch);

        return back()->with('success', "Branch \"{$branch->name}\" is now ".($branch->is_active ? 'active.' : 'inactive.'));
    }
}

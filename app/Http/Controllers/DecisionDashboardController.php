<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAction;
use App\Models\ProductActionAudit;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ExportService;
use App\Services\ProductAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DecisionDashboardController extends Controller
{
    public function __construct(
        protected ProductAnalyticsService $analyticsService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(! in_array($user->role, ['owner', 'manager']), 403, 'Access denied.');

        if ($request->has('export')) {
            return $this->export($request);
        }

        // Enforce branch isolation for non-owners
        if (! $user->isOwner() && $request->filled('branch_id') && (int) $request->branch_id !== (int) $user->branch_id) {
            abort(403, 'You can only view reports for your assigned branch.');
        }

        $scopedBranchId = $user->isOwner() ? ($request->filled('branch_id') ? (int) $request->branch_id : null) : (int) $user->branch_id;

        $filters = $request->validate([
            'type' => ['nullable', 'in:daily,weekly,monthly,yearly,custom,all'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'channel' => ['nullable', 'in:all,pos,grab'],
            'ranking_by' => ['nullable', 'in:units,revenue'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $type = $filters['type'] ?? 'monthly';
        $date = $filters['date'] ?? now(config('app.business_timezone', 'Asia/Manila'))->toDateString();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $channel = $filters['channel'] ?? 'all';
        $rankingBy = $filters['ranking_by'] ?? 'units';
        $categoryId = $filters['category_id'] ?? null;
        $productId = $filters['product_id'] ?? null;

        $dateWindows = $this->analyticsService->resolveDateWindows($type, $date, $from, $to);
        $productPerformance = $this->analyticsService->getProductPerformance($filters, $dateWindows, $scopedBranchId);

        // Apply text search on products if specified
        if (! empty($filters['search'])) {
            $search = strtolower(trim($filters['search']));
            $productPerformance = $productPerformance->filter(function ($item) use ($search) {
                return str_contains(strtolower($item->name), $search) || str_contains(strtolower($item->category_name), $search);
            })->values();
        }

        $branchSummary = $this->analyticsService->getBranchPerformanceSummary($filters, $dateWindows, $scopedBranchId);
        $reconciliation = $this->analyticsService->getFinancialReconciliationWarnings($filters, $dateWindows, $scopedBranchId);
        $insights = $this->analyticsService->generateInsights($productPerformance, $branchSummary, $dateWindows, $scopedBranchId);

        // Slice key groups for UI cards and tables
        $topProducts = $productPerformance->where('units_sold', '>', 0)->take(8);
        $leastProducts = $productPerformance->where('units_sold', '>', 0)->sortBy('units_sold')->take(8);
        $zeroSalesProducts = $productPerformance->where('units_sold', 0);
        $increasingProducts = $productPerformance->where('trend', 'increasing')->where('units_sold', '>', 0);
        $decreasingProducts = $productPerformance->where('trend', 'decreasing');
        $highDemandLowStockProducts = $productPerformance->where('has_low_stock', true)->where('units_sold', '>', 0);

        // Query tracked Product Actions
        $productActionsQuery = ProductAction::with(['product', 'branch', 'assignedUser'])
            ->when($scopedBranchId, fn ($q) => $q->where(fn ($sub) => $sub->where('branch_id', $scopedBranchId)->orWhereNull('branch_id')))
            ->latest('id');

        $openActions = (clone $productActionsQuery)
            ->whereNotIn('status', ['completed', 'rejected'])
            ->take(10)
            ->get();

        $completedActions = (clone $productActionsQuery)
            ->where('status', 'completed')
            ->take(8)
            ->get();

        // Branches, categories, staff list for filters and modal assignment
        $branches = Branch::where('is_active', true)->get();
        $categories = Category::where('status', 'active')->get();
        $productsList = Product::where('status', 'active')->orderBy('name')->get();
        $assignableUsers = User::where('status', 'active')
            ->when($scopedBranchId, fn ($q) => $q->where(fn ($sub) => $sub->where('branch_id', $scopedBranchId)->orWhereNull('branch_id')))
            ->whereIn('role', ['owner', 'manager'])
            ->orderBy('name')
            ->get();

        return view('reports.decision-dashboard', compact(
            'type',
            'date',
            'from',
            'to',
            'channel',
            'rankingBy',
            'categoryId',
            'productId',
            'scopedBranchId',
            'dateWindows',
            'productPerformance',
            'branchSummary',
            'reconciliation',
            'insights',
            'topProducts',
            'leastProducts',
            'zeroSalesProducts',
            'increasingProducts',
            'decreasingProducts',
            'highDemandLowStockProducts',
            'openActions',
            'completedActions',
            'branches',
            'categories',
            'productsList',
            'assignableUsers'
        ));
    }

    public function insights(Request $request)
    {
        $user = $request->user();
        abort_if(! in_array($user->role, ['owner', 'manager']), 403);

        $scopedBranchId = $user->isOwner() ? ($request->filled('branch_id') ? (int) $request->branch_id : null) : (int) $user->branch_id;
        $filters = $request->all();
        $type = $filters['type'] ?? 'monthly';
        $date = $filters['date'] ?? now(config('app.business_timezone', 'Asia/Manila'))->toDateString();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $dateWindows = $this->analyticsService->resolveDateWindows($type, $date, $from, $to);
        $productPerformance = $this->analyticsService->getProductPerformance($filters, $dateWindows, $scopedBranchId);
        $branchSummary = $this->analyticsService->getBranchPerformanceSummary($filters, $dateWindows, $scopedBranchId);
        $insights = $this->analyticsService->generateInsights($productPerformance, $branchSummary, $dateWindows, $scopedBranchId);

        return response()->json([
            'success' => true,
            'insights' => $insights,
            'reporting_period' => $dateWindows['label'],
        ]);
    }

    public function storeAction(Request $request)
    {
        $user = $request->user();
        abort_if(! in_array($user->role, ['owner', 'manager']), 403);

        $validated = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'finding_category' => ['required', 'string', 'in:High sales,Low sales,Zero sales,Declining sales,Increasing sales,Low ingredient stock,High inventory with low sales,Branch performance difference,Insufficient data'],
            'evidence_summary' => ['required', 'string', 'max:2000'],
            'recommended_action' => ['required', 'string', 'max:2000'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'reporting_period' => ['nullable', 'string', 'max:255'],
            'period_start' => ['nullable', 'date_format:Y-m-d'],
            'period_end' => ['nullable', 'date_format:Y-m-d'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'owner_decision' => ['nullable', 'string', 'in:Needs Review,Approved,Rejected,In Progress,Completed,Deferred'],
        ]);

        if (! $user->isOwner() && ! empty($validated['branch_id']) && (int) $validated['branch_id'] !== (int) $user->branch_id) {
            abort(403, 'You can only create actions for your assigned branch.');
        }

        $branchId = $user->isOwner() ? ($validated['branch_id'] ?? null) : $user->branch_id;
        $decision = $user->isOwner() ? ($validated['owner_decision'] ?? 'Needs Review') : 'Needs Review';
        $status = match ($decision) {
            'Approved' => 'approved',
            'Rejected' => 'rejected',
            'Deferred' => 'deferred',
            'In Progress' => 'in_progress',
            'Completed' => 'completed',
            default => 'needs_review',
        };

        $assignedUser = ! empty($validated['assigned_to_user_id']) ? User::find($validated['assigned_to_user_id']) : null;

        $action = DB::transaction(function () use ($validated, $user, $branchId, $decision, $status, $assignedUser) {
            $created = ProductAction::create([
                'action_code' => ProductAction::generateActionCode(),
                'branch_id' => $branchId,
                'product_id' => $validated['product_id'] ?? null,
                'source_reference' => $validated['source_reference'] ?? null,
                'finding_category' => $validated['finding_category'],
                'evidence_summary' => $validated['evidence_summary'],
                'reporting_period' => $validated['reporting_period'] ?? null,
                'period_start' => $validated['period_start'] ?? null,
                'period_end' => $validated['period_end'] ?? null,
                'recommended_action' => $validated['recommended_action'],
                'owner_decision' => $decision,
                'assigned_to_user_id' => $assignedUser?->id,
                'assigned_to_name' => $assignedUser?->name,
                'due_date' => $validated['due_date'] ?? null,
                'status' => $status,
                'created_by_user_id' => $user->id,
                'created_by_name' => $user->name,
                'updated_by_user_id' => $user->id,
                'updated_by_name' => $user->name,
            ]);

            ProductActionAudit::create([
                'product_action_id' => $created->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'event' => 'created',
                'new_decision' => $decision,
                'new_status' => $status,
                'notes' => 'Product action documented from business analytics.',
            ]);

            return $created;
        });

        AuditService::logFromUser($user, 'product_action_created', 'Analytics', [
            'action_code' => $action->action_code,
            'category' => $action->finding_category,
            'product_id' => $action->product_id,
            'decision' => $action->owner_decision,
        ], $action);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Action #{$action->action_code} logged successfully.",
                'action' => $action,
            ], 201);
        }

        return back()->with('success', "Action #{$action->action_code} logged successfully.");
    }

    public function recordDecision(Request $request, ProductAction $action)
    {
        $user = $request->user();
        abort_if(! $user->isOwner(), 403, 'Only the business Owner can record executive decisions on product actions.');

        $validated = $request->validate([
            'owner_decision' => ['required', 'string', 'in:Needs Review,Approved,Rejected,In Progress,Completed,Deferred'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $prevDecision = $action->owner_decision;
        $prevStatus = $action->status;
        $newDecision = $validated['owner_decision'];
        $newStatus = match ($newDecision) {
            'Approved' => 'approved',
            'Rejected' => 'rejected',
            'Deferred' => 'deferred',
            'In Progress' => 'in_progress',
            'Completed' => 'completed',
            default => 'needs_review',
        };

        $assignedUser = ! empty($validated['assigned_to_user_id']) ? User::find($validated['assigned_to_user_id']) : $action->assignedUser;

        DB::transaction(function () use ($action, $user, $validated, $newDecision, $newStatus, $assignedUser, $prevDecision, $prevStatus) {
            $action->update([
                'owner_decision' => $newDecision,
                'status' => $newStatus,
                'assigned_to_user_id' => $assignedUser?->id,
                'assigned_to_name' => $assignedUser?->name,
                'due_date' => $validated['due_date'] ?? $action->due_date,
                'updated_by_user_id' => $user->id,
                'updated_by_name' => $user->name,
                'completed_at' => $newDecision === 'Completed' ? ($action->completed_at ?? now()) : $action->completed_at,
            ]);

            ProductActionAudit::create([
                'product_action_id' => $action->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'event' => 'decision_recorded',
                'previous_decision' => $prevDecision,
                'new_decision' => $newDecision,
                'previous_status' => $prevStatus,
                'new_status' => $newStatus,
                'notes' => $validated['decision_notes'] ?? "Owner set decision to {$newDecision}.",
            ]);
        });

        AuditService::logFromUser($user, 'product_action_decision_recorded', 'Analytics', [
            'action_code' => $action->action_code,
            'previous_decision' => $prevDecision,
            'new_decision' => $newDecision,
            'assigned_to' => $assignedUser?->name,
        ], $action);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Decision updated to '{$newDecision}' for #{$action->action_code}.",
                'action' => $action->fresh(['audits']),
            ]);
        }

        return back()->with('success', "Decision updated to '{$newDecision}' for #{$action->action_code}.");
    }

    public function updateStatus(Request $request, ProductAction $action)
    {
        $user = $request->user();
        abort_if(! in_array($user->role, ['owner', 'manager']), 403);

        // Non-owners can only update actions within their assigned branch
        if (! $user->isOwner() && $action->branch_id && (int) $action->branch_id !== (int) $user->branch_id) {
            abort(403, 'You can only update actions for your assigned branch.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:in_progress,completed,needs_review'],
            'completion_notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_period' => ['nullable', 'string', 'max:255'],
            'follow_up_start' => ['nullable', 'date_format:Y-m-d'],
            'follow_up_end' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($validated['status'] === 'completed' && blank($validated['completion_notes'])) {
            throw ValidationException::withMessages([
                'completion_notes' => 'Completion notes documenting the executed operational changes are required.',
            ]);
        }

        $prevStatus = $action->status;
        $newStatus = $validated['status'];
        $prevDecision = $action->owner_decision;
        $newDecision = $newStatus === 'completed' ? 'Completed' : ($prevDecision === 'Approved' ? 'In Progress' : $prevDecision);

        DB::transaction(function () use ($action, $user, $validated, $newStatus, $newDecision, $prevStatus, $prevDecision) {
            $action->update([
                'status' => $newStatus,
                'owner_decision' => $newDecision,
                'completion_notes' => $validated['completion_notes'] ?? $action->completion_notes,
                'completed_at' => $newStatus === 'completed' ? now() : $action->completed_at,
                'follow_up_period' => $validated['follow_up_period'] ?? $action->follow_up_period,
                'follow_up_start' => $validated['follow_up_start'] ?? $action->follow_up_start,
                'follow_up_end' => $validated['follow_up_end'] ?? $action->follow_up_end,
                'updated_by_user_id' => $user->id,
                'updated_by_name' => $user->name,
            ]);

            ProductActionAudit::create([
                'product_action_id' => $action->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'event' => $newStatus === 'completed' ? 'completed' : 'status_updated',
                'previous_decision' => $prevDecision,
                'new_decision' => $newDecision,
                'previous_status' => $prevStatus,
                'new_status' => $newStatus,
                'notes' => $validated['completion_notes'] ?? "Status moved to {$newStatus}.",
            ]);
        });

        AuditService::logFromUser($user, 'product_action_status_updated', 'Analytics', [
            'action_code' => $action->action_code,
            'status' => $newStatus,
            'notes' => $validated['completion_notes'] ?? null,
        ], $action);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Action #{$action->action_code} updated to '{$newStatus}'.",
                'action' => $action->fresh(['audits']),
            ]);
        }

        return back()->with('success', "Action #{$action->action_code} updated to '{$newStatus}'.");
    }

    public function showAction(ProductAction $action)
    {
        $user = auth()->user();
        if (! $user->isOwner() && $action->branch_id && (int) $action->branch_id !== (int) $user->branch_id) {
            abort(403, 'Access denied.');
        }

        $action->load(['product.sizes.recipe.recipeIngredients.ingredient', 'branch', 'assignedUser', 'audits.user']);
        $followUp = $this->analyticsService->evaluateActionFollowUp($action);

        return view('reports.action-show', compact('action', 'followUp'));
    }

    public function followUp(ProductAction $action)
    {
        $user = auth()->user();
        if (! $user->isOwner() && $action->branch_id && (int) $action->branch_id !== (int) $user->branch_id) {
            abort(403, 'Access denied.');
        }

        $action->load(['product.sizes.recipe.recipeIngredients.ingredient', 'branch']);
        $evaluation = $this->analyticsService->evaluateActionFollowUp($action);

        return response()->json([
            'success' => true,
            'action_code' => $action->action_code,
            'evaluation' => $evaluation,
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        abort_if(! in_array($user->role, ['owner', 'manager']), 403);

        $scopedBranchId = $user->isOwner() ? ($request->filled('branch_id') ? (int) $request->branch_id : null) : (int) $user->branch_id;
        $filters = $request->all();
        $type = $filters['type'] ?? 'monthly';
        $date = $filters['date'] ?? now(config('app.business_timezone', 'Asia/Manila'))->toDateString();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $dateWindows = $this->analyticsService->resolveDateWindows($type, $date, $from, $to);
        $performance = $this->analyticsService->getProductPerformance($filters, $dateWindows, $scopedBranchId);

        $filename = 'product-performance-'.now(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d').'.csv';
        $headers = [
            'Product ID',
            'Product Name',
            'Category',
            'Units Sold',
            'Net Revenue (PHP)',
            'Previous Units',
            'Previous Net Revenue (PHP)',
            'Volume Change',
            'Growth (%)',
            'Trend',
            'Stock Alert',
        ];

        $rows = $performance->map(fn ($p) => [
            $p->id,
            $p->name,
            $p->category_name,
            $p->units_sold,
            number_format($p->net_revenue, 2, '.', ''),
            $p->prev_units_sold,
            number_format($p->prev_net_revenue, 2, '.', ''),
            $p->unit_change,
            $p->unit_change_percent !== null ? $p->unit_change_percent.'%' : 'New',
            ucfirst($p->trend),
            $p->has_low_stock ? 'LOW STOCK' : 'OK',
        ]);

        return ExportService::streamCsv($filename, $headers, $rows);
    }
}

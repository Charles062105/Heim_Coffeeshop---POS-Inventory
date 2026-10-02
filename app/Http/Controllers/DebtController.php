<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DebtController extends Controller
{
    /**
     * List all debts with summary stats.
     */
    public function index(Request $request)
    {
        // Refresh overdue statuses on page load
        Debt::refreshOverdueStatuses();

        $query = Debt::with(['order', 'payments'])->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$search}%"));
            });
        }

        $debts = $query->paginate(20)->withQueryString();

        $stats = [
            'total_balance' => Debt::whereIn('status', ['pending', 'partially_paid', 'overdue'])->sum('balance'),
            'total_pending' => Debt::where('status', 'pending')->count(),
            'total_overdue' => Debt::where('status', 'overdue')->count(),
            'total_paid' => Debt::where('status', 'paid')->count(),
        ];

        return view('debts.index', compact('debts', 'stats'));
    }

    /**
     * Show a single debt with payment history.
     */
    public function show(Debt $debt)
    {
        $debt->load(['order.orderItems.product', 'order.orderItems.size', 'payments']);

        return view('debts.show', compact('debt'));
    }

    /**
     * Record a payment against a debt.
     */
    public function recordPayment(Request $request, Debt $debt)
    {
        $shift = CashierShift::activeForUser($request->user()->id);
        if (! $shift) {
            return back()->with('error', 'Start a cashier shift before collecting a debt payment.');
        }

        if ($debt->status === 'paid') {
            return back()->with('error', 'This debt has already been fully paid.');
        }

        $validated = $request->validate([
            'amount' => "required|numeric|min:0.01|max:{$debt->balance}",
            'payment_method' => 'required|in:cash,online,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'payment_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $debt, $request) {
            $shift = CashierShift::lockActiveForUser($request->user()->id);
            $payment = DebtPayment::create([
                'debt_id' => $debt->id,
                'shift_id' => $shift->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $request->user()?->name ?? 'Staff',
            ]);

            // Recalculate balance and status
            $debt->recalculate();

            AuditService::logFromUser($request->user(), 'debt_payment_recorded', 'Debt', [
                'debt_id' => $debt->id,
                'customer_name' => $debt->customer_name,
                'amount' => $validated['amount'],
                'new_balance' => $debt->fresh()->balance,
                'payment_id' => $payment->id,
            ], $debt);
        });

        return redirect()->route('debts.show', $debt)
            ->with('success', 'Payment of ₱'.number_format($validated['amount'], 2).' recorded successfully.');
    }

    /**
     * Mark a debt as paid manually (full write-off).
     */
    public function markPaid(Request $request, Debt $debt)
    {
        if ($debt->status === 'paid') {
            return back()->with('error', 'Debt is already marked as paid.');
        }

        // Require manager/owner authorization for manual write-off if balance > 0
        if ((float) $debt->balance > 0) {
            $authorized = false;
            $user = $request->user();
            if ($user?->canAuthorize()) {
                $authorized = true;
            } elseif ($request->filled('authorizer_email') && $request->filled('authorizer_password')) {
                $mgr = User::where('email', $request->authorizer_email)->where('status', 'active')->first();
                if ($mgr && Hash::check($request->authorizer_password, $mgr->password) && $mgr->canAuthorize()) {
                    $authorized = true;
                }
            }

            if (! $authorized) {
                return back()->with('error', 'Manager or owner authorization is required to write off remaining balance.');
            }
        }

        DB::transaction(function () use ($debt, $request) {
            $debt->update([
                'status' => 'paid',
                'amount_paid' => max((float) $debt->amount_paid, (float) $debt->original_amount),
                'balance' => 0,
            ]);
            AuditService::logFromUser($request->user(), 'debt_marked_paid', 'Debt', [
                'debt_id' => $debt->id,
                'customer_name' => $debt->customer_name,
                'amount_paid' => (float) $debt->fresh()->amount_paid,
                'balance' => 0,
            ], $debt);
        });

        return redirect()->route('debts.show', $debt)
            ->with('success', 'Debt marked as fully paid.');
    }
}

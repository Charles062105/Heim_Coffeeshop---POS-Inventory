<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Order;
use App\Models\ShiftCashMovement;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    private const DENOMINATIONS = [
        '1000' => 1000,
        '500' => 500,
        '200' => 200,
        '100' => 100,
        '50' => 50,
        '20' => 20,
        '10' => 10,
        '5' => 5,
        '1' => 1,
        '0.25' => 0.25,
        '0.10' => 0.10,
        '0.05' => 0.05,
    ];

    public function current(Request $request)
    {
        $shift = CashierShift::activeForUser($request->user()->id);

        if (! $shift) {
            return response()->json(['active' => false]);
        }

        $summary = $shift->cashSummary();
        $openOrders = $this->openOrderCount($shift);

        return response()->json([
            'active' => true,
            'shift' => [
                'id' => $shift->id,
                'cashier_name' => $shift->cashier_name,
                'shift_date' => $shift->shift_date->format('M d, Y'),
                'start_time' => $shift->localStartTime()->format('h:i A'),
                'started_at' => $shift->localStartTime()->toIso8601String(),
                'beginning_cash' => (float) $shift->beginning_cash,
                'unresolved_orders' => $openOrders,
                'non_cash_summary' => [
                    'online_sales' => $summary['online_sales'],
                    'debt_online_collections' => $summary['debt_online_collections'],
                    'grab_sales' => $summary['grab_sales'],
                    'grab_settlements' => $summary['grab_settlements'],
                    'pay_later_charged' => $summary['pay_later_charged'],
                    'dine_in_sales' => $summary['dine_in_sales'],
                    'take_out_sales' => $summary['take_out_sales'],
                    'void_count' => $summary['void_count'],
                    'void_amount' => $summary['void_amount'],
                ],
            ],
        ]);
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'beginning_cash' => 'required|numeric|min:0',
        ]);
        $user = $request->user();
        $startedAt = now();
        $shiftDate = $startedAt->copy()
            ->timezone(config('app.business_timezone', 'Asia/Manila'))
            ->toDateString();

        if (CashierShift::activeForUser($user->id)) {
            return $this->errorResponse($request, 'You already have an active shift. Close it before starting another.', 422);
        }

        try {
            $shift = DB::transaction(fn () => CashierShift::create([
                'user_id' => $user->id,
                'open_user_id' => $user->id,
                'cashier_name' => $user->name,
                'shift_date' => $shiftDate,
                'start_time' => $startedAt,
                'beginning_cash' => (float) $validated['beginning_cash'],
                'cash_sales' => 0,
                'cash_refunds' => 0,
                'cash_voids' => 0,
                'expected_cash' => (float) $validated['beginning_cash'],
                'status' => 'open',
            ]));
        } catch (QueryException $exception) {
            if (! in_array($exception->getCode(), ['23000', '23505', '19'], true)) {
                throw $exception;
            }

            return $this->errorResponse($request, 'You already have an open shift. Close it before starting another.', 422);
        }

        AuditService::logFromUser($user, 'shift_started', 'POS', [
            'shift_id' => $shift->id,
            'cashier' => $shift->cashier_name,
            'started_at' => $shift->localStartTime()->toIso8601String(),
            'beginning_cash' => (float) $shift->beginning_cash,
        ], $shift);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift started successfully.',
                'shift' => $this->localizedShiftPayload($shift),
            ]);
        }

        return back()->with('success', 'Shift started successfully! Beginning cash: ₱'.number_format((float) $shift->beginning_cash, 2));
    }

    public function previewEnd(Request $request)
    {
        $shift = CashierShift::activeForUser($request->user()->id);
        if (! $shift) {
            return response()->json(['message' => 'No active shift found.'], 404);
        }

        $actualCash = $this->validateCount($request);
        $summary = $shift->cashSummary();
        $expectedCash = $this->expectedCash($shift, $summary);

        return response()->json([
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'difference' => round($actualCash - $expectedCash, 2),
            'summary' => $summary,
        ]);
    }

    public function end(Request $request)
    {
        $shift = CashierShift::activeForUser($request->user()->id);
        if (! $shift) {
            return $this->errorResponse($request, 'No active shift found.', 404);
        }

        $actualCash = $this->validateCount($request);
        $summary = $shift->cashSummary();
        $expectedCash = $this->expectedCash($shift, $summary);
        $difference = round($actualCash - $expectedCash, 2);

        $openOrderCount = $this->openOrderCount($shift);
        $user = $request->user();
        $override = $user->canAuthorize();
        if ($openOrderCount > 0) {
            $override = $override || $this->validAuthorizer($request);
            if (! $override || blank($request->input('override_reason')) || mb_strlen(trim($request->input('override_reason'))) < 10) {
                return $this->errorResponse(
                    $request,
                    "There are {$openOrderCount} held or unpaid ticket(s). Settle them first or obtain manager approval with a reason.",
                    422,
                    ['open_orders' => $openOrderCount, 'manager_approval_required' => true]
                );
            }
        }

        if ($difference !== 0.0 && blank($request->input('comment'))) {
            return $this->errorResponse($request, 'Add a note explaining the cash difference before closing the shift.', 422);
        }

        return $this->closeShift($request, $shift, $actualCash, $override);
    }

    public function closeOther(Request $request, CashierShift $shift)
    {
        abort_unless($request->user()->canAuthorize(), 403);

        $validated = $request->validate([
            'actual_cash' => 'nullable|numeric|min:0|required_without:denomination_count',
            'denomination_count' => 'nullable|array|required_without:actual_cash',
            'denomination_count.*' => 'required|integer|min:0',
            'comment' => 'nullable|string|max:500',
            'override_reason' => 'nullable|string|min:10|max:500',
        ]);
        if (! $shift->isOpen()) {
            return back()->with('error', 'Only an open shift can be closed.');
        }

        $actualCash = isset($validated['denomination_count'])
            ? $this->sumDenominations($validated['denomination_count'])
            : (float) $validated['actual_cash'];
        $openOrderCount = $this->openOrderCount($shift);
        if ($openOrderCount > 0 && blank($validated['override_reason'] ?? null)) {
            return back()->withErrors([
                'override_reason' => "A reason is required to close this shift with {$openOrderCount} held or unpaid ticket(s).",
            ])->withInput();
        }

        $shift = $this->persistClose(
            $shift,
            $request->user(),
            $actualCash,
            $validated['comment'] ?? null,
            $validated['denomination_count'] ?? null,
            true,
            $validated['override_reason'] ?? null
        );
        $difference = (float) $shift->difference;

        AuditService::logFromUser($request->user(), 'shift_closed_by_manager', 'POS', [
            'shift_id' => $shift->id,
            'cashier' => $shift->cashier_name,
            'open_orders' => $this->openOrderCount($shift),
            'override_reason' => $validated['override_reason'] ?? null,
            'difference' => $difference,
        ], $shift);

        return back()->with('success', "Shift #{$shift->id} for {$shift->cashier_name} was closed.");
    }

    public function review(Request $request, CashierShift $shift)
    {
        abort_unless($request->user()->canAuthorize(), 403);
        if ($shift->status === 'reviewed') {
            return back()->with('error', 'This shift has already been reviewed.');
        }
        if (! $shift->isClosed()) {
            return back()->with('error', 'Only closed shifts can be reviewed.');
        }

        $validated = $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);
        $shift->update([
            'status' => 'reviewed',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $validated['review_note'] ?? null,
        ]);

        AuditService::logFromUser($request->user(), 'shift_reviewed', 'POS', [
            'shift_id' => $shift->id,
            'cashier' => $shift->cashier_name,
            'difference' => (float) $shift->difference,
            'review_note' => $validated['review_note'] ?? null,
        ], $shift);

        return back()->with('success', "Shift #{$shift->id} marked as reviewed.");
    }

    public function adjustment(Request $request, CashierShift $shift)
    {
        abort_unless($request->user()->canAuthorize(), 403);
        if (! $shift->isClosed()) {
            return back()->with('error', 'Only closed shifts can receive a correction adjustment.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|min:10|max:500',
        ]);
        $movement = ShiftCashMovement::create([
            'shift_id' => $shift->id,
            'recorded_by' => $request->user()->id,
            'type' => 'adjustment',
            'amount' => round((float) $validated['amount'], 2),
            'reason' => trim($validated['reason']),
        ]);

        AuditService::logFromUser($request->user(), 'shift_adjustment_recorded', 'POS', [
            'shift_id' => $shift->id,
            'movement_id' => $movement->id,
            'amount' => (float) $movement->amount,
            'reason' => $movement->reason,
            'original_difference' => (float) $shift->difference,
        ], $movement);

        return back()->with('success', 'Adjustment recorded in the audit trail. The closed shift figures were not changed.');
    }

    private function validateCount(Request $request): float
    {
        $validated = $request->validate([
            'actual_cash' => 'nullable|numeric|min:0|required_without:denomination_count',
            'denomination_count' => 'nullable|array|required_without:actual_cash',
            'denomination_count.*' => 'required|integer|min:0',
            'comment' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'override_reason' => 'nullable|string|min:10|max:500',
            'authorizer_email' => 'nullable|email',
            'authorizer_password' => 'nullable|string',
        ]);

        return isset($validated['denomination_count'])
            ? $this->sumDenominations($validated['denomination_count'])
            : (float) $validated['actual_cash'];
    }

    private function sumDenominations(array $counts): float
    {
        foreach ($counts as $denomination => $count) {
            if (! isset(self::DENOMINATIONS[$denomination])) {
                throw ValidationException::withMessages([
                    'denomination_count' => "Unsupported cash denomination: {$denomination}.",
                ]);
            }
        }

        $total = 0;
        foreach (self::DENOMINATIONS as $denomination => $value) {
            $total += $value * (int) ($counts[$denomination] ?? 0);
        }

        return round($total, 2);
    }

    private function closeShift(
        Request $request,
        CashierShift $shift,
        float $actualCash,
        bool $override
    ) {
        $comment = $request->input('comment', $request->input('notes'));
        $shift = $this->persistClose(
            $shift,
            $request->user(),
            $actualCash,
            $comment,
            $request->input('denomination_count'),
            $override,
            $request->input('override_reason')
        );
        $summary = $shift->cashSummary();
        $difference = (float) $shift->difference;
        $overrideUsed = $override && $this->openOrderCount($shift) > 0;

        AuditService::logFromUser($request->user(), 'shift_ended', 'POS', [
            'shift_id' => $shift->id,
            'beginning_cash' => (float) $shift->beginning_cash,
            'cash_sales' => (float) $shift->cash_sales,
            'debt_cash_collections' => $summary['debt_cash_collections'],
            'debt_online_collections' => $summary['debt_online_collections'],
            'cash_refunds' => (float) $shift->cash_refunds,
            'cash_voids' => (float) $shift->cash_voids,
            'expected_cash' => (float) $shift->expected_cash,
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'notes' => $comment,
            'manager_override' => $overrideUsed,
            'override_reason' => $request->input('override_reason'),
        ], $shift);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift ended successfully.',
                'shift' => $this->localizedShiftPayload($shift),
                'summary' => $summary,
            ]);
        }

        return back()->with('success', 'Shift closed. Variance: '.($difference >= 0 ? '+' : '').'₱'.number_format($difference, 2).'.');
    }

    private function persistClose(
        CashierShift $shift,
        User $closedBy,
        float $actualCash,
        ?string $comment,
        ?array $denominationCount,
        bool $override,
        ?string $overrideReason
    ): CashierShift {
        return DB::transaction(function () use ($shift, $closedBy, $actualCash, $comment, $denominationCount, $override, $overrideReason) {
            $lockedShift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);
            if (! $lockedShift->isOpen()) {
                throw ValidationException::withMessages(['shift' => 'This shift has already been closed.']);
            }

            $openOrderCount = $this->openOrderCount($lockedShift);
            if ($openOrderCount > 0 && (
                ! $override
                || blank($overrideReason)
                || mb_strlen(trim($overrideReason)) < 10
            )) {
                throw ValidationException::withMessages([
                    'override_reason' => "A reason and manager approval are required to close this shift with {$openOrderCount} held or unpaid ticket(s).",
                ]);
            }

            $summary = $lockedShift->cashSummary();
            $expectedCash = $this->expectedCash($lockedShift, $summary);
            $difference = round($actualCash - $expectedCash, 2);
            if ($difference !== 0.0 && blank($comment)) {
                throw ValidationException::withMessages([
                    'comment' => 'A note is required when the counted cash differs from expected cash.',
                ]);
            }

            $lockedShift->update([
                'end_time' => now(),
                'open_user_id' => null,
                'closed_by' => $closedBy->id,
                'cash_sales' => $summary['cash_sales'],
                'debt_cash_collections' => $summary['debt_cash_collections'],
                'cash_refunds' => $summary['cash_refunds'],
                'cash_voids' => $summary['cash_voids'],
                'online_sales' => $summary['online_sales'],
                'debt_online_collections' => $summary['debt_online_collections'],
                'grab_sales' => $summary['grab_sales'],
                'grab_settlements' => $summary['grab_settlements'],
                'pay_later_charged' => $summary['pay_later_charged'],
                'void_count' => $summary['void_count'],
                'void_amount' => $summary['void_amount'],
                'dine_in_sales' => $summary['dine_in_sales'],
                'take_out_sales' => $summary['take_out_sales'],
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'denomination_count' => $denominationCount,
                'notes' => $comment,
                'status' => 'closed',
            ]);

            return $lockedShift->fresh();
        });
    }

    private function expectedCash(CashierShift $shift, array $summary): float
    {
        return round(
            (float) $shift->beginning_cash
            + $summary['cash_sales']
            + $summary['debt_cash_collections']
            - $summary['cash_refunds']
            - $summary['cash_voids'],
            2
        );
    }

    private function localizedShiftPayload(CashierShift $shift): array
    {
        $payload = $shift->toArray();
        $startTime = $shift->localStartTime();
        $endTime = $shift->localEndTime();
        $payload['shift_date'] = $shift->shift_date?->toDateString();
        $payload['start_time'] = $startTime?->format('Y-m-d H:i:s');
        $payload['end_time'] = $endTime?->format('Y-m-d H:i:s');
        $payload['started_at'] = $startTime?->toIso8601String();
        $payload['ended_at'] = $endTime?->toIso8601String();

        return $payload;
    }

    private function openOrderCount(CashierShift $shift): int
    {
        return Order::where('shift_id', $shift->id)
            ->whereIn('status', ['held', 'pending', 'partially_paid'])
            ->count();
    }

    private function validAuthorizer(Request $request): bool
    {
        if (! $request->filled('authorizer_email') || ! $request->filled('authorizer_password')) {
            return false;
        }
        $authorizer = User::where('email', $request->input('authorizer_email'))
            ->where('status', 'active')
            ->first();

        return $authorizer
            && Hash::check($request->input('authorizer_password'), $authorizer->password)
            && $authorizer->canAuthorize();
    }

    private function errorResponse(Request $request, string $message, int $status, array $extra = [])
    {
        if ($request->wantsJson()) {
            return response()->json(array_merge(['message' => $message], $extra), $status);
        }

        return back()->withErrors(['shift' => $message])->withInput();
    }
}

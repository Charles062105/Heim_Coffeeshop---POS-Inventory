<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->canAuthorize()
            || ($this->belongsToAssignedBranch($user, $order)
                && ($user->isCashier() || strcasecmp($user->name, $order->cashier_name) === 0));
    }

    public function recordPayment(User $user, Order $order): bool
    {
        return $user->canAuthorize()
            || ($user->isCashier()
                && $this->belongsToAssignedBranch($user, $order));
    }

    private function belongsToAssignedBranch(User $user, Order $order): bool
    {
        return $user->branch_id !== null
            && $order->branch_id !== null
            && (int) $user->branch_id === (int) $order->branch_id;
    }
}

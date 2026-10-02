<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->canAuthorize()
            || $user->isCashier()
            || strcasecmp($user->name, $order->cashier_name) === 0;
    }

    public function recordPayment(User $user, Order $order): bool
    {
        return $user->canAuthorize()
            || ($user->isCashier() && strcasecmp($user->name, $order->cashier_name) === 0);
    }
}

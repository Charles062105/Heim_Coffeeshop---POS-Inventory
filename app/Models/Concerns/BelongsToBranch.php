<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            $user = Auth::user();

            if ($user && ! $user->isOwner()) {
                $query->where(
                    $query->getModel()->qualifyColumn('branch_id'),
                    $user->branch_id
                );
            }
        });

        static::creating(function ($model) {
            if ($model->branch_id) {
                return;
            }

            $model->branch_id = Auth::user()?->branch_id
                ?? Branch::query()->where('is_active', true)->orderBy('id')->value('id');
        });
    }
}

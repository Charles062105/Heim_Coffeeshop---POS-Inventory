<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditService
{
    /**
     * Write an audit log entry.
     *
     * @param  string  $action  e.g. 'completed_order', 'refund_authorized', 'stock_in'
     * @param  string  $module  e.g. 'POS', 'Inventory', 'Refunds', 'Reports'
     * @param  string  $actorName  Name of the person performing the action
     * @param  string  $actorRole  Role of the person
     * @param  array|null  $details  Any additional context (JSON-serializable)
     * @param  mixed|null  $reference  Eloquent model instance (polymorphic reference)
     * @param  int|null  $actorUserId  FK to users table if available
     */
    public static function log(
        string $action,
        string $module,
        string $actorName,
        string $actorRole,
        array $details = [],
        mixed $reference = null,
        ?int $actorUserId = null,
        ?string $reason = null,
        ?string $ipAddress = null
    ): AuditLog {
        $resolvedReason = $reason ?? (is_array($details) && isset($details['reason']) ? (string) $details['reason'] : null);

        $entry = new AuditLog;
        $entry->actor_name = $actorName;
        $entry->actor_role = $actorRole;
        $entry->actor_user_id = $actorUserId;
        $entry->action = $action;
        $entry->module = $module;
        $entry->details = $details;
        $entry->reason = $resolvedReason;
        $entry->ip_address = $ipAddress ?? request()->ip();

        if ($reference && method_exists($reference, 'getMorphClass')) {
            $entry->reference_type = $reference->getMorphClass();
            $entry->reference_id = $reference->getKey();
        }

        $entry->save();

        return $entry;
    }

    /**
     * Convenience: log from authenticated user.
     */
    public static function logFromUser(
        User $user,
        string $action,
        string $module,
        array $details = [],
        mixed $reference = null,
        ?string $reason = null,
        ?string $ipAddress = null
    ): AuditLog {
        return static::log(
            action: $action,
            module: $module,
            actorName: $user->name,
            actorRole: $user->role,
            details: $details,
            reference: $reference,
            actorUserId: $user->id,
            reason: $reason,
            ipAddress: $ipAddress,
        );
    }
}

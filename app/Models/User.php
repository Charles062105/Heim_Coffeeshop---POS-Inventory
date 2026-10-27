<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->branch_id ??= Branch::where('is_active', true)->orderBy('id')->value('id');
        });
    }

    protected $fillable = [
        'name', 'email', 'password', 'role', 'status', 'branch_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Role helpers
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function canAuthorize(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }

    public function canManageInventory(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }

    public function canExportOrPrint(): bool
    {
        return in_array($this->role, ['owner', 'manager']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Relationships
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedProductActions()
    {
        return $this->hasMany(ProductAction::class, 'assigned_to_user_id');
    }

    public function createdProductActions()
    {
        return $this->hasMany(ProductAction::class, 'created_by_user_id');
    }
}

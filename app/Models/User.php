<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'status',
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
}

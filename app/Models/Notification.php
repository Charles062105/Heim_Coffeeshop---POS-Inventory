<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'title', 'message', 'target_role', 'ingredient_id',
        'is_resolved', 'resolved_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeUnreadForUser($query, User $user)
    {
        return $query->whereNull('read_at')
            ->whereDoesntHave('reads', fn ($reads) => $reads->where('user_id', $user->id));
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeForRole($query, string $role)
    {
        $visibleRoles = match ($role) {
            'owner' => ['owner', 'manager', 'all'],
            'manager' => ['manager', 'all'],
            default => ['all'],
        };

        return $query->whereIn('target_role', $visibleRoles);
    }
}

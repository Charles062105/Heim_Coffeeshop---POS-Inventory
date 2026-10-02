<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_name', 'actor_role', 'actor_user_id',
        'action', 'module', 'reference_id', 'reference_type',
        'details', 'reason', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function actorUser()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function reference()
    {
        return $this->morphTo();
    }
}

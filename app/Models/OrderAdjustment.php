<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'action', 'reason', 'authorized_by', 'authorized_role',
        'authorized_user_id', 'before_state', 'after_state',
    ];

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function authorizedUser()
    {
        return $this->belongsTo(User::class, 'authorized_user_id');
    }
}

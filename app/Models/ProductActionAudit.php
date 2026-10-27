<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductActionAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_action_id',
        'user_id',
        'user_name',
        'user_role',
        'event',
        'previous_decision',
        'new_decision',
        'previous_status',
        'new_status',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function productAction()
    {
        return $this->belongsTo(ProductAction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

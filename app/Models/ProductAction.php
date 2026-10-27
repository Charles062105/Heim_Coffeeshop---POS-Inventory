<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAction extends Model
{
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'action_code',
        'branch_id',
        'product_id',
        'source_reference',
        'finding_category',
        'evidence_summary',
        'reporting_period',
        'period_start',
        'period_end',
        'recommended_action',
        'owner_decision',
        'assigned_to_user_id',
        'assigned_to_name',
        'due_date',
        'status',
        'completion_notes',
        'completed_at',
        'follow_up_period',
        'follow_up_start',
        'follow_up_end',
        'result_metrics',
        'created_by_user_id',
        'created_by_name',
        'updated_by_user_id',
        'updated_by_name',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'follow_up_start' => 'date',
            'follow_up_end' => 'date',
            'result_metrics' => 'array',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function audits()
    {
        return $this->hasMany(ProductActionAudit::class)->latest('id');
    }

    public function isApproved(): bool
    {
        return $this->owner_decision === 'Approved';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->owner_decision === 'Completed';
    }

    public static function generateActionCode(): string
    {
        $prefix = 'ACT-'.now(config('app.business_timezone', 'Asia/Manila'))->format('Ym').'-';

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $prefix.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            if (! static::where('action_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.substr(uniqid(), -4);
    }
}

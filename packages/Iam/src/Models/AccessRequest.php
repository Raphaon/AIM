<?php

namespace Aim\Iam\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'approver_id',
        'status',
        'roles',
        'permissions',
        'justification',
        'requested_expires_at',
        'decision_at',
        'decision_note',
    ];

    protected $casts = [
        'roles' => 'array',
        'permissions' => 'array',
        'requested_expires_at' => 'datetime',
        'decision_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DENIED = 'denied';
    public const STATUS_CANCELLED = 'cancelled';

    public function requester(): BelongsTo
    {
        $userModel = config('iam.user_model');

        return $this->belongsTo($userModel, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        $userModel = config('iam.user_model');

        return $this->belongsTo($userModel, 'approver_id');
    }
}

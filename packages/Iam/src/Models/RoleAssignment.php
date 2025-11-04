<?php

namespace Aim\Iam\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends Model
{
    protected $table = 'role_user';

    protected $fillable = [
        'role_id',
        'user_id',
        'assigned_by',
        'expires_at',
        'assignment_note',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $timestamps = true;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function user(): BelongsTo
    {
        $userModel = config('iam.user_model');

        return $this->belongsTo($userModel);
    }

    public function assigner(): BelongsTo
    {
        $userModel = config('iam.user_model');

        return $this->belongsTo($userModel, 'assigned_by');
    }
}

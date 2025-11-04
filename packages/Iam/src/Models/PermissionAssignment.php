<?php

namespace Aim\Iam\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionAssignment extends Model
{
    protected $table = 'permission_user';

    protected $fillable = [
        'permission_id',
        'user_id',
        'assigned_by',
        'expires_at',
        'assignment_note',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $timestamps = true;

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
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

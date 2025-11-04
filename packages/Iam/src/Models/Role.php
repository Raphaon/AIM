<?php

namespace Aim\Iam\Models;

use Aim\Iam\Models\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        $userModel = config('iam.user_model');

        return $this->belongsToMany($userModel, 'role_user')
            ->withTimestamps()
            ->withPivot(['assigned_by', 'expires_at', 'assignment_note']);
    }
}

<?php

namespace Aim\Iam\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        $userModel = config('iam.user_model');

        return $this->belongsToMany($userModel, 'permission_user')->withTimestamps();
    }
}

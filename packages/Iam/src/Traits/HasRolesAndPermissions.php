<?php

namespace Aim\Iam\Traits;

use Aim\Iam\Models\Permission;
use Aim\Iam\Models\PermissionAssignment;
use Aim\Iam\Models\Role;
use Aim\Iam\Models\RoleAssignment;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps()
            ->withPivot(['assigned_by', 'expires_at', 'assignment_note']);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->withTimestamps()
            ->withPivot(['assigned_by', 'expires_at', 'assignment_note']);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class, 'user_id');
    }

    public function permissionAssignments(): HasMany
    {
        return $this->hasMany(PermissionAssignment::class, 'user_id');
    }

    public function hasRole(string $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->filter(fn ($related) => $related->name === $role && $this->pivotAssignmentIsActive($related->pivot->expires_at ?? null))
                ->isNotEmpty();
        }

        return $this->roles()
            ->where('name', $role)
            ->where(function ($query): void {
                $this->appendActivePivotConstraint($query, 'role_user');
            })
            ->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles
                ->filter(fn ($related) => in_array($related->name, $roles, true) && $this->pivotAssignmentIsActive($related->pivot->expires_at ?? null))
                ->isNotEmpty();
        }

        return $this->roles()
            ->whereIn('name', $roles)
            ->where(function ($query): void {
                $this->appendActivePivotConstraint($query, 'role_user');
            })
            ->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->relationLoaded('permissions')) {
            $hasDirect = $this->permissions
                ->filter(fn ($related) => $related->name === $permission && $this->pivotAssignmentIsActive($related->pivot->expires_at ?? null))
                ->isNotEmpty();

            if ($hasDirect) {
                return true;
            }
        }

        if ($this->permissions()
            ->where('name', $permission)
            ->where(function ($query): void {
                $this->appendActivePivotConstraint($query, 'permission_user');
            })
            ->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permission): void {
            $query->where('name', $permission);
        })->where(function ($query): void {
            $this->appendActivePivotConstraint($query, 'role_user');
        })->exists();
    }

    public function assignRole($roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $roles = collect($roles)->flatten();
        $roleIds = Role::whereIn('name', $roles)->pluck('id');

        $this->roles()->syncWithoutDetaching($roleIds);
    }

    public function syncRoles($roles): void
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $roles = collect($roles)->flatten();
        $roleIds = Role::whereIn('name', $roles)->pluck('id');

        $this->roles()->sync($roleIds);
    }

    public function givePermissionTo($permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $permissions = collect($permissions)->flatten();
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');

        $this->permissions()->syncWithoutDetaching($permissionIds);
    }

    public function syncPermissions($permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $permissions = collect($permissions)->flatten();
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');

        $this->permissions()->sync($permissionIds);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function pivotAssignmentIsActive(?string $expiresAt): bool
    {
        if ($expiresAt === null) {
            return true;
        }

        return Carbon::parse($expiresAt)->isFuture();
    }

    protected function appendActivePivotConstraint($query, string $pivotTable): void
    {
        $query->where(function ($inner) use ($pivotTable): void {
            $inner->whereNull($pivotTable . '.expires_at')
                ->orWhere($pivotTable . '.expires_at', '>', now());
        });
    }
}


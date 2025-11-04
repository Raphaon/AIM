<?php

namespace Aim\Iam\Traits;

use Aim\Iam\Models\Permission;
use Aim\Iam\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRolesAndPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $role);
        }

        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->relationLoaded('permissions') && $this->permissions->contains('name', $permission)) {
            return true;
        }

        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($permission): void {
            $query->where('name', $permission);
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
}


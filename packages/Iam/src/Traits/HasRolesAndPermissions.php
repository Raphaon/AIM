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
        return $this->roles->contains('name', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->permissions->contains('name', $permission)) {
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
}

<?php

namespace Aim\Iam\Services;

use Aim\Iam\Models\Permission;
use Aim\Iam\Models\Role;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GovernanceService
{
    public function assignRoles(
        Authenticatable $user,
        array $roles,
        ?Authenticatable $assignedBy = null,
        ?Carbon $expiresAt = null,
        ?string $note = null
    ): void {
        if (empty($roles)) {
            return;
        }

        if ($this->containsDetailedAssignments($roles)) {
            $this->assignRolesWithDetails($user, $roles, $assignedBy);

            return;
        }

        $roleIds = Role::whereIn('name', $roles)->pluck('id');
        $pivotData = $this->buildPivotPayload($roleIds, $assignedBy, $expiresAt, $note);

        if (method_exists($user, 'roles')) {
            $user->roles()->syncWithoutDetaching($pivotData);
        }
    }

    public function assignRolesWithDetails(Authenticatable $user, array $assignments, ?Authenticatable $assignedBy = null): void
    {
        $payload = [];

        foreach ($assignments as $assignment) {
            if (! is_array($assignment) || empty($assignment['name'])) {
                continue;
            }

            $roleId = Role::where('name', $assignment['name'])->value('id');

            if (! $roleId) {
                continue;
            }

            $payload[$roleId] = $this->buildDetailedPayload($assignedBy, $assignment, $assignment['assignment_note'] ?? $assignment['note'] ?? null);
        }

        if (! empty($payload) && method_exists($user, 'roles')) {
            $user->roles()->syncWithoutDetaching($payload);
        }
    }

    public function assignPermissions(
        Authenticatable $user,
        array $permissions,
        ?Authenticatable $assignedBy = null,
        ?Carbon $expiresAt = null,
        ?string $note = null
    ): void {
        if (empty($permissions)) {
            return;
        }

        if ($this->containsDetailedAssignments($permissions)) {
            $this->assignPermissionsWithDetails($user, $permissions, $assignedBy);

            return;
        }

        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $pivotData = $this->buildPivotPayload($permissionIds, $assignedBy, $expiresAt, $note);

        if (method_exists($user, 'permissions')) {
            $user->permissions()->syncWithoutDetaching($pivotData);
        }
    }

    public function assignPermissionsWithDetails(Authenticatable $user, array $assignments, ?Authenticatable $assignedBy = null): void
    {
        $payload = [];

        foreach ($assignments as $assignment) {
            if (! is_array($assignment) || empty($assignment['name'])) {
                continue;
            }

            $permissionId = Permission::where('name', $assignment['name'])->value('id');

            if (! $permissionId) {
                continue;
            }

            $payload[$permissionId] = $this->buildDetailedPayload($assignedBy, $assignment, $assignment['assignment_note'] ?? $assignment['note'] ?? null);
        }

        if (! empty($payload) && method_exists($user, 'permissions')) {
            $user->permissions()->syncWithoutDetaching($payload);
        }
    }

    /**
     * @return array{roles: int, permissions: int}
     */
    public function removeExpiredAssignments(): array
    {
        $rolesRemoved = (int) \DB::table('role_user')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $permissionsRemoved = (int) \DB::table('permission_user')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        return [
            'roles' => $rolesRemoved,
            'permissions' => $permissionsRemoved,
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    protected function buildPivotPayload(Collection $ids, ?Authenticatable $assignedBy, ?Carbon $expiresAt, ?string $note): array
    {
        $payload = [];

        foreach ($ids as $id) {
            $payload[$id] = $this->buildSimplePayload($assignedBy, $expiresAt, $note);
        }

        return $payload;
    }

    protected function containsDetailedAssignments(array $entries): bool
    {
        return ! empty($entries) && is_array(reset($entries));
    }

    protected function buildSimplePayload(?Authenticatable $assignedBy, ?Carbon $expiresAt, ?string $note): array
    {
        $payload = [];

        if ($assignedBy) {
            $payload['assigned_by'] = $assignedBy->getAuthIdentifier();
        }

        if ($expiresAt !== null) {
            $payload['expires_at'] = $expiresAt;
        }

        if ($note !== null) {
            $payload['assignment_note'] = $note;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $assignment
     */
    protected function buildDetailedPayload(?Authenticatable $assignedBy, array $assignment, ?string $fallbackNote): array
    {
        $payload = [];

        if ($assignedBy) {
            $payload['assigned_by'] = $assignedBy->getAuthIdentifier();
        }

        if (array_key_exists('expires_at', $assignment)) {
            $expires = $assignment['expires_at'];

            if ($expires !== null && ! $expires instanceof \DateTimeInterface) {
                $expires = Carbon::parse($expires);
            }

            $payload['expires_at'] = $expires;
        }

        if (array_key_exists('assignment_note', $assignment)) {
            $payload['assignment_note'] = $assignment['assignment_note'];
        } elseif (array_key_exists('note', $assignment)) {
            $payload['assignment_note'] = $assignment['note'];
        } elseif ($fallbackNote !== null) {
            $payload['assignment_note'] = $fallbackNote;
        }

        return $payload;
    }
}

<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Models\Permission;
use Aim\Iam\Models\Role;
use Aim\Iam\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $guardMiddleware = ['auth:' . config('iam.api_guard', 'sanctum')];

        $this->middleware(array_merge($guardMiddleware, ['permission:roles.view']))->only(['index', 'show']);
        $this->middleware(array_merge($guardMiddleware, ['permission:roles.create']))->only(['store']);
        $this->middleware(array_merge($guardMiddleware, ['permission:roles.update']))->only(['update']);
        $this->middleware(array_merge($guardMiddleware, ['permission:roles.delete']))->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $roles = Role::with('permissions')->paginate($request->integer('per_page') ?: config('iam.pagination.per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(collect($data)->only(['name', 'display_name', 'description'])->toArray());
        if (! empty($data['permissions'] ?? [])) {
            $role->permissions()->sync(Permission::whereIn('name', $data['permissions'])->pluck('id'));
        }

        $fresh = $role->load('permissions');
        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'roles.create', Role::class, $role->getKey(), [], $fresh->toArray());

        return response()->json([
            'status' => 'success',
            'data' => $fresh,
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $role->load('permissions'),
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $oldValues = $role->only(['name', 'display_name', 'description']);
        $role->fill(collect($data)->only(['name', 'display_name', 'description'])->toArray());
        $role->save();

        if (array_key_exists('permissions', $data)) {
            $role->permissions()->sync(Permission::whereIn('name', $data['permissions'] ?? [])->pluck('id'));
        }

        $fresh = $role->load('permissions');
        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'roles.update', Role::class, $role->getKey(), $oldValues, $fresh->toArray());

        return response()->json([
            'status' => 'success',
            'data' => $fresh,
        ]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        $oldValues = $role->toArray();
        $id = $role->getKey();
        $role->delete();

        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'roles.delete', Role::class, $id, $oldValues, []);

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully.',
        ]);
    }
}

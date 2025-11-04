<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Models\Permission;
use Aim\Iam\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $guardMiddleware = ['auth:' . config('iam.api_guard', 'sanctum')];

        $this->middleware(array_merge($guardMiddleware, ['permission:permissions.view']))->only(['index', 'show']);
        $this->middleware(array_merge($guardMiddleware, ['permission:permissions.create']))->only(['store']);
        $this->middleware(array_merge($guardMiddleware, ['permission:permissions.update']))->only(['update']);
        $this->middleware(array_merge($guardMiddleware, ['permission:permissions.delete']))->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $permissions = Permission::paginate($request->integer('per_page') ?: config('iam.pagination.per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $permissions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:permissions,name'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $permission = Permission::create($data);
        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'permissions.create', Permission::class, $permission->getKey(), [], $permission->toArray());

        return response()->json([
            'status' => 'success',
            'data' => $permission,
        ], 201);
    }

    public function show(Permission $permission): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150', Rule::unique('permissions', 'name')->ignore($permission->id)],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $oldValues = $permission->only(['name', 'display_name', 'description']);
        $permission->fill($data);
        $permission->save();

        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'permissions.update', Permission::class, $permission->getKey(), $oldValues, $permission->toArray());

        return response()->json([
            'status' => 'success',
            'data' => $permission,
        ]);
    }

    public function destroy(Request $request, Permission $permission): JsonResponse
    {
        $oldValues = $permission->toArray();
        $id = $permission->getKey();
        $permission->delete();

        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'permissions.delete', Permission::class, $id, $oldValues, []);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission deleted successfully.',
        ]);
    }
}

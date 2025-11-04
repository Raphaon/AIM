<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $guardMiddleware = ['auth:' . config('iam.api_guard', 'sanctum')];

        $this->middleware(array_merge($guardMiddleware, ['permission:users.view']))->only(['index', 'show']);
        $this->middleware(array_merge($guardMiddleware, ['permission:users.create']))->only(['store']);
        $this->middleware(array_merge($guardMiddleware, ['permission:users.update']))->only(['update']);
        $this->middleware(array_merge($guardMiddleware, ['permission:users.delete']))->only(['destroy']);
    }

    protected function model(): string
    {
        return config('iam.user_model');
    }

    public function index(Request $request): JsonResponse
    {
        $model = $this->model();
        $query = $model::query()->with(['roles', 'permissions']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($email = $request->string('email')->toString()) {
            $query->where('email', 'like', "%{$email}%");
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($role = $request->string('role')->toString()) {
            $query->whereHas('roles', function ($builder) use ($role): void {
                $builder->where('name', $role);
            });
        }

        $users = $query->paginate($request->integer('per_page') ?: config('iam.pagination.per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $model = $this->model();
        /** @var Model $user */
        $user = new $model();
        $table = $user->getTable();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique($table)],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:active,suspended'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->fill(collect($data)->only(['name', 'email', 'phone', 'status'])->toArray());
        $user->password = $data['password'];
        $user->status = $user->status ?? 'active';
        $user->save();

        if (! empty($data['roles'] ?? [])) {
            $user->syncRoles($data['roles']);
        }

        if (! empty($data['permissions'] ?? [])) {
            $user->syncPermissions($data['permissions']);
        }

        $fresh = $user->fresh()->load(['roles', 'permissions']);

        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'users.create', $model, $fresh->getKey(), [], $fresh->toArray());

        return response()->json([
            'status' => 'success',
            'data' => $fresh,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $model = $this->model();
        $user = $model::with(['roles', 'permissions'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $model = $this->model();
        $user = $model::with(['roles', 'permissions'])->findOrFail($id);
        $table = $user->getTable();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique($table)->ignore($user->getKey())],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:active,suspended'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $oldValues = $user->only(array_keys($data));

        $user->fill(collect($data)->only(['name', 'email', 'phone', 'status'])->toArray());

        if (isset($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        if (array_key_exists('roles', $data)) {
            $user->syncRoles($data['roles'] ?? []);
        }

        if (array_key_exists('permissions', $data)) {
            $user->syncPermissions($data['permissions'] ?? []);
        }

        $fresh = $user->fresh()->load(['roles', 'permissions']);

        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'users.update', $model, $fresh->getKey(), $oldValues, $fresh->toArray());

        return response()->json([
            'status' => 'success',
            'data' => $fresh,
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $model = $this->model();
        $user = $model::findOrFail($id);
        $oldValues = $user->toArray();
        $user->delete();

        $this->auditLogger->log($request->user(config('iam.api_guard', 'sanctum')), 'users.delete', $model, $id, $oldValues, []);

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }
}

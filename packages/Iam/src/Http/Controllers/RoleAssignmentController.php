<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Models\RoleAssignment;
use Aim\Iam\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class RoleAssignmentController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $guardMiddleware = ['auth:' . config('iam.api_guard', 'sanctum')];

        $this->middleware(array_merge($guardMiddleware, ['permission:role.assignments.view']))->only('index');
        $this->middleware(array_merge($guardMiddleware, ['permission:role.assignments.update']))->only('update');
        $this->middleware(array_merge($guardMiddleware, ['permission:role.assignments.delete']))->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['nullable', 'integer'],
            'role_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,expired'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $request->string('status')->toString();

        $assignments = RoleAssignment::query()
            ->with(['user', 'role', 'assigner'])
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('role_id'), fn ($query) => $query->where('role_id', $request->integer('role_id')))
            ->when($status === 'active', function ($query): void {
                $query->where(function ($q): void {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            })
            ->when($status === 'expired', function ($query): void {
                $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
            })
            ->latest()
            ->paginate($request->integer('per_page') ?: config('iam.pagination.per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $assignments,
        ]);
    }

    public function update(Request $request, RoleAssignment $roleAssignment): JsonResponse
    {
        $data = $request->validate([
            'expires_at' => ['nullable', 'date', 'after:now'],
            'assignment_note' => ['nullable', 'string', 'max:255'],
        ]);

        $oldValues = $roleAssignment->only(['expires_at', 'assignment_note']);

        $updates = [];

        if (array_key_exists('expires_at', $data)) {
            $updates['expires_at'] = $data['expires_at'] ? Carbon::parse($data['expires_at']) : null;
        }

        if (array_key_exists('assignment_note', $data)) {
            $updates['assignment_note'] = $data['assignment_note'];
        }

        if (! empty($updates)) {
            $roleAssignment->fill($updates)->save();
        }

        $this->auditLogger->log(
            $request->user(config('iam.api_guard', 'sanctum')),
            'role.assignments.update',
            RoleAssignment::class,
            $roleAssignment->getKey(),
            $oldValues,
            $roleAssignment->only(['expires_at', 'assignment_note'])
        );

        return response()->json([
            'status' => 'success',
            'data' => $roleAssignment->load(['user', 'role', 'assigner']),
        ]);
    }

    public function destroy(Request $request, RoleAssignment $roleAssignment): JsonResponse
    {
        $oldValues = $roleAssignment->toArray();
        $assignmentId = $roleAssignment->getKey();
        $roleAssignment->delete();

        $this->auditLogger->log(
            $request->user(config('iam.api_guard', 'sanctum')),
            'role.assignments.delete',
            RoleAssignment::class,
            $assignmentId,
            $oldValues,
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Role assignment removed successfully.',
        ]);
    }
}

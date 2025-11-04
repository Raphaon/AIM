<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Models\AuditLog;
use Aim\Iam\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $guardMiddleware = ['auth:' . config('iam.api_guard', 'sanctum')];

        $this->middleware(array_merge($guardMiddleware, ['permission:audit.logs.view']))->only(['index', 'show']);
        $this->middleware(array_merge($guardMiddleware, ['permission:audit.logs.delete']))->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string'],
            'model' => ['nullable', 'string'],
            'ip' => ['nullable', 'ip'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = AuditLog::query()->with('user')->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->string('action')->toString() . '%');
        }

        if ($request->filled('model')) {
            $query->where('model', $request->string('model')->toString());
        }

        if ($request->filled('ip')) {
            $query->where('ip_address', $request->string('ip')->toString());
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', Carbon::parse($request->string('from')->toString())->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', Carbon::parse($request->string('to')->toString())->endOfDay());
        }

        $logs = $query->paginate($request->integer('per_page') ?: config('iam.pagination.per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $auditLog->load('user'),
        ]);
    }

    public function destroy(Request $request, AuditLog $auditLog): JsonResponse
    {
        $oldValues = $auditLog->toArray();
        $id = $auditLog->getKey();
        $auditLog->delete();

        $this->auditLogger->log(
            $request->user(config('iam.api_guard', 'sanctum')),
            'audit.logs.delete',
            AuditLog::class,
            $id,
            $oldValues,
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Audit log entry deleted successfully.',
        ]);
    }
}

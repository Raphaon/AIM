<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Models\AccessRequest;
use Aim\Iam\Services\AuditLogger;
use Aim\Iam\Services\GovernanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AccessRequestController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly GovernanceService $governanceService
    ) {
        $guardMiddleware = ['auth:' . config('iam.api_guard', 'sanctum')];

        $this->middleware($guardMiddleware)->only(['index', 'show', 'store', 'approve', 'deny', 'cancel']);
        $this->middleware(array_merge($guardMiddleware, ['permission:access.requests.view']))->only(['index', 'show']);
        $this->middleware(array_merge($guardMiddleware, ['permission:access.requests.approve']))->only(['approve', 'deny']);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in([
                AccessRequest::STATUS_PENDING,
                AccessRequest::STATUS_APPROVED,
                AccessRequest::STATUS_DENIED,
                AccessRequest::STATUS_CANCELLED,
            ])],
            'requester_id' => ['nullable', 'integer'],
            'approver_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = AccessRequest::query()->with(['requester', 'approver'])->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($requester = $request->integer('requester_id')) {
            $query->where('requester_id', $requester);
        }

        if ($approver = $request->integer('approver_id')) {
            $query->where('approver_id', $approver);
        }

        $requests = $query->paginate($request->integer('per_page') ?: config('iam.pagination.per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'justification' => ['nullable', 'string'],
            'requested_expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        if (empty($data['requested_expires_at']) && ($defaultDuration = config('iam.governance.default_assignment_duration_days'))) {
            $data['requested_expires_at'] = now()->addDays((int) $defaultDuration)->toDateTimeString();
        }

        if (empty($data['roles']) && empty($data['permissions'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You must request at least one role or permission.',
            ], 422);
        }

        $user = $request->user(config('iam.api_guard', 'sanctum'));

        $accessRequest = AccessRequest::create([
            'requester_id' => $user->getAuthIdentifier(),
            'roles' => $data['roles'] ?? [],
            'permissions' => $data['permissions'] ?? [],
            'justification' => $data['justification'] ?? null,
            'requested_expires_at' => $data['requested_expires_at'] ?? null,
            'status' => AccessRequest::STATUS_PENDING,
        ]);

        $this->auditLogger->log(
            $user,
            'access.requests.submit',
            AccessRequest::class,
            $accessRequest->getKey(),
            [],
            $accessRequest->toArray()
        );

        return response()->json([
            'status' => 'success',
            'data' => $accessRequest->load(['requester', 'approver']),
        ], 201);
    }

    public function show(AccessRequest $accessRequest): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $accessRequest->load(['requester', 'approver']),
        ]);
    }

    public function approve(Request $request, AccessRequest $accessRequest): JsonResponse
    {
        if ($accessRequest->status !== AccessRequest::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending requests can be approved.',
            ], 422);
        }

        $data = $request->validate([
            'decision_note' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $approver = $request->user(config('iam.api_guard', 'sanctum'));
        $expiresAt = isset($data['expires_at'])
            ? Carbon::parse($data['expires_at'])
            : $accessRequest->requested_expires_at;

        $requester = $accessRequest->requester;

        if ($requester) {
            $this->governanceService->assignRoles($requester, $accessRequest->roles ?? [], $approver, $expiresAt, $data['decision_note'] ?? null);
            $this->governanceService->assignPermissions($requester, $accessRequest->permissions ?? [], $approver, $expiresAt, $data['decision_note'] ?? null);
        }

        $oldValues = $accessRequest->toArray();

        $accessRequest->fill([
            'status' => AccessRequest::STATUS_APPROVED,
            'approver_id' => $approver?->getAuthIdentifier(),
            'decision_at' => now(),
            'decision_note' => $data['decision_note'] ?? null,
        ])->save();

        $accessRequest->refresh();

        $this->auditLogger->log(
            $approver,
            'access.requests.approve',
            AccessRequest::class,
            $accessRequest->getKey(),
            $oldValues,
            $accessRequest->toArray()
        );

        return response()->json([
            'status' => 'success',
            'data' => $accessRequest->load(['requester', 'approver']),
        ]);
    }

    public function deny(Request $request, AccessRequest $accessRequest): JsonResponse
    {
        if ($accessRequest->status !== AccessRequest::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending requests can be denied.',
            ], 422);
        }

        $data = $request->validate([
            'decision_note' => ['nullable', 'string'],
        ]);

        $approver = $request->user(config('iam.api_guard', 'sanctum'));

        $oldValues = $accessRequest->toArray();

        $accessRequest->fill([
            'status' => AccessRequest::STATUS_DENIED,
            'approver_id' => $approver?->getAuthIdentifier(),
            'decision_at' => now(),
            'decision_note' => $data['decision_note'] ?? null,
        ])->save();

        $accessRequest->refresh();

        $this->auditLogger->log(
            $approver,
            'access.requests.deny',
            AccessRequest::class,
            $accessRequest->getKey(),
            $oldValues,
            $accessRequest->toArray()
        );

        return response()->json([
            'status' => 'success',
            'data' => $accessRequest->load(['requester', 'approver']),
        ]);
    }

    public function cancel(Request $request, AccessRequest $accessRequest): JsonResponse
    {
        $user = $request->user(config('iam.api_guard', 'sanctum'));

        if ($accessRequest->status !== AccessRequest::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $canManage = method_exists($user, 'hasPermission') && $user->hasPermission('access.requests.manage');

        if ($user->getAuthIdentifier() !== $accessRequest->requester_id && ! $canManage) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not allowed to cancel this request.',
            ], 403);
        }

        $oldValues = $accessRequest->toArray();

        $accessRequest->fill([
            'status' => AccessRequest::STATUS_CANCELLED,
            'decision_at' => now(),
        ])->save();

        $accessRequest->refresh();

        $this->auditLogger->log(
            $user,
            'access.requests.cancel',
            AccessRequest::class,
            $accessRequest->getKey(),
            $oldValues,
            $accessRequest->toArray()
        );

        return response()->json([
            'status' => 'success',
            'data' => $accessRequest->load(['requester', 'approver']),
        ]);
    }
}

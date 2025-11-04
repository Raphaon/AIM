<?php

use Aim\Iam\Http\Controllers\AccessRequestController;
use Aim\Iam\Http\Controllers\AuditLogController;
use Aim\Iam\Http\Controllers\AuthController;
use Aim\Iam\Http\Controllers\EmailVerificationController;
use Aim\Iam\Http\Controllers\PasswordResetController;
use Aim\Iam\Http\Controllers\PermissionController;
use Aim\Iam\Http\Controllers\RoleAssignmentController;
use Aim\Iam\Http\Controllers\RoleController;
use Aim\Iam\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('iam.route_prefix', 'api/iam'))
    ->middleware(['api'])
    ->group(function (): void {
        Route::post('auth/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('iam.auth.login');

        Route::post('auth/password/email', [PasswordResetController::class, 'sendResetLinkEmail'])
            ->name('iam.auth.password.email');
        Route::post('auth/password/reset', [PasswordResetController::class, 'reset'])
            ->name('iam.auth.password.reset');

        Route::get('auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:' . config('iam.verification.throttle', 6) . ',' . config('iam.verification.decay', 1)])
            ->name('verification.verify');

        Route::middleware(['auth:' . config('iam.api_guard', 'sanctum')])->group(function (): void {
            Route::post('auth/logout', [AuthController::class, 'logout'])
                ->name('iam.auth.logout');
            Route::get('auth/profile', [AuthController::class, 'profile'])
                ->name('iam.auth.profile');
            Route::put('auth/profile', [AuthController::class, 'updateProfile'])
                ->name('iam.auth.updateProfile');
            Route::put('auth/password', [AuthController::class, 'updatePassword'])
                ->name('iam.auth.updatePassword');
            Route::post('auth/email/verification-notification', [EmailVerificationController::class, 'send'])
                ->name('iam.auth.email.send');

            Route::apiResource('users', UserController::class);
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('permissions', PermissionController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy']);

            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('iam.audit-logs.index');
            Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('iam.audit-logs.show');
            Route::delete('audit-logs/{auditLog}', [AuditLogController::class, 'destroy'])->name('iam.audit-logs.destroy');

            Route::get('access-requests', [AccessRequestController::class, 'index'])->name('iam.access-requests.index');
            Route::post('access-requests', [AccessRequestController::class, 'store'])->name('iam.access-requests.store');
            Route::get('access-requests/{accessRequest}', [AccessRequestController::class, 'show'])->name('iam.access-requests.show');
            Route::post('access-requests/{accessRequest}/approve', [AccessRequestController::class, 'approve'])->name('iam.access-requests.approve');
            Route::post('access-requests/{accessRequest}/deny', [AccessRequestController::class, 'deny'])->name('iam.access-requests.deny');
            Route::post('access-requests/{accessRequest}/cancel', [AccessRequestController::class, 'cancel'])->name('iam.access-requests.cancel');

            Route::get('role-assignments', [RoleAssignmentController::class, 'index'])->name('iam.role-assignments.index');
            Route::put('role-assignments/{roleAssignment}', [RoleAssignmentController::class, 'update'])->name('iam.role-assignments.update');
            Route::delete('role-assignments/{roleAssignment}', [RoleAssignmentController::class, 'destroy'])->name('iam.role-assignments.destroy');
        });
    });

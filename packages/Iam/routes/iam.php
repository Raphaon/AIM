<?php

use Aim\Iam\Http\Controllers\AuthController;
use Aim\Iam\Http\Controllers\PermissionController;
use Aim\Iam\Http\Controllers\RoleController;
use Aim\Iam\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('iam.route_prefix', 'api/iam'))
    ->middleware(['api'])
    ->group(function (): void {
        Route::post('auth/login', [AuthController::class, 'login'])->name('iam.auth.login');
        Route::post('auth/logout', [AuthController::class, 'logout'])
            ->middleware('auth:' . config('iam.api_guard', 'sanctum'))
            ->name('iam.auth.logout');
        Route::get('auth/profile', [AuthController::class, 'profile'])
            ->middleware('auth:' . config('iam.api_guard', 'sanctum'))
            ->name('iam.auth.profile');
        Route::put('auth/profile', [AuthController::class, 'updateProfile'])
            ->middleware('auth:' . config('iam.api_guard', 'sanctum'))
            ->name('iam.auth.updateProfile');
        Route::put('auth/password', [AuthController::class, 'updatePassword'])
            ->middleware('auth:' . config('iam.api_guard', 'sanctum'))
            ->name('iam.auth.updatePassword');

        Route::middleware(['auth:' . config('iam.api_guard', 'sanctum')])->group(function (): void {
            Route::apiResource('users', UserController::class);
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('permissions', PermissionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        });
    });

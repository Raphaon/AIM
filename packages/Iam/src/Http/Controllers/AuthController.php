<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Services\AuditLogger;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    protected function guard(): StatefulGuard
    {
        return Auth::guard(config('iam.guard', 'web'));
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->guard()->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        /** @var \Illuminate\Contracts\Auth\Authenticatable&\Aim\Iam\Traits\HasRolesAndPermissions $user */
        $user = $this->guard()->user();

        if (isset($user->status) && $user->status !== 'active') {
            $this->guard()->logout();

            throw ValidationException::withMessages([
                'email' => ['The user account is not active.'],
            ]);
        }

        if (method_exists($user, 'forceFill')) {
            $user->forceFill([
                'last_login_at' => now(),
                'login_count' => ($user->login_count ?? 0) + 1,
            ])->save();
        }

        $token = null;
        if (method_exists($user, 'createToken')) {
            $token = $user->createToken($credentials['device_name'] ?? 'iam-api')->plainTextToken;
        }

        $this->auditLogger->log($user, 'auth.login', get_class($user), $user->getKey(), [], []);

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|null $user */
        $user = $request->user(config('iam.api_guard', 'sanctum'));

        if ($user && method_exists($user, 'currentAccessToken')) {
            $user->currentAccessToken()?->delete();
        }

        $this->guard()->logout();

        $this->auditLogger->log($user, 'auth.logout', $user ? get_class($user) : 'user', $user?->getAuthIdentifier(), [], []);

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(config('iam.api_guard', 'sanctum')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user(config('iam.api_guard', 'sanctum'));

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique($user->getTable())->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $oldValues = $user->only(array_keys($data));
        $user->fill($data);
        $user->save();

        $this->auditLogger->log($user, 'auth.profile.update', get_class($user), $user->getKey(), $oldValues, $data);

        return response()->json([
            'status' => 'success',
            'data' => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user(config('iam.api_guard', 'sanctum'));

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        $this->auditLogger->log($user, 'auth.password.update', get_class($user), $user->getKey(), [], []);

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.',
        ]);
    }
}

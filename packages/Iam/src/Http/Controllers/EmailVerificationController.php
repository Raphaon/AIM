<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Services\AuditLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $guardMiddleware = ['auth:' . config('iam.api_guard', 'sanctum')];

        $this->middleware(array_merge($guardMiddleware, ['throttle:' . config('iam.verification.throttle', 6) . ',' . config('iam.verification.decay', 1)]))
            ->only('send');
    }

    public function send(Request $request): JsonResponse
    {
        $user = $request->user(config('iam.api_guard', 'sanctum'));

        if (! $user instanceof MustVerifyEmail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email verification is not supported for this user.',
            ], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        $this->auditLogger->log(
            $user,
            'auth.email.verification-link-requested',
            get_class($user),
            $user->getKey(),
            [],
            []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Verification email sent successfully.',
        ]);
    }

    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $userModel = config('iam.user_model');

        /** @var MustVerifyEmail&\Illuminate\Database\Eloquent\Model|null $user */
        $user = $userModel::findOrFail($id);

        if (! $user instanceof MustVerifyEmail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email verification is not supported for this user.',
            ], 400);
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification hash.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email already verified.',
            ]);
        }

        $oldValues = ['email_verified_at' => $user->getAttribute('email_verified_at')];

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            $user->forceFill(['remember_token' => Str::random(60)])->save();

            $fresh = $user->fresh();
            $this->auditLogger->log(
                $user,
                'auth.email.verified',
                get_class($user),
                $user->getKey(),
                $oldValues,
                ['email_verified_at' => $fresh->getAttribute('email_verified_at')]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully.',
        ]);
    }
}

<?php

namespace Aim\Iam\Http\Controllers;

use Aim\Iam\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $this->middleware('throttle:3,1')->only(['sendResetLinkEmail', 'reset']);
    }

    protected function broker(): PasswordBroker
    {
        return Password::broker(config('iam.passwords', config('auth.defaults.passwords')));
    }

    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = $this->broker()->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            $userModel = config('iam.user_model');
            $user = $userModel::where('email', $request->string('email'))->first();

            if ($user) {
                $this->auditLogger->log(
                    $user,
                    'auth.password.reset-link-requested',
                    $userModel,
                    $user->getKey(),
                    [],
                    []
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => __('A password reset link has been sent if the email exists in our records.'),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $resetUser = null;

        $status = $this->broker()->reset(
            $credentials,
            function (CanResetPassword $user, string $password) use (&$resetUser): void {
                $resetUser = $user;

                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            if ($resetUser) {
                $this->auditLogger->log(
                    $resetUser,
                    'auth.password.reset',
                    $resetUser::class,
                    $resetUser->getKey(),
                    [],
                    []
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => __('Password reset successfully.'),
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}

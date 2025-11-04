<?php

return [
    'guard' => env('IAM_GUARD', 'web'),
    'api_guard' => env('IAM_API_GUARD', 'sanctum'),
    'user_model' => env('IAM_USER_MODEL', App\\Models\\User::class),
    'route_prefix' => env('IAM_ROUTE_PREFIX', 'api/iam'),
    'pagination' => [
        'per_page' => env('IAM_PAGINATION_PER_PAGE', 15),
    ],
    'passwords' => env('IAM_PASSWORD_BROKER', config('auth.defaults.passwords')),
    'verification' => [
        'expire' => env('IAM_VERIFICATION_EXPIRE', 60),
        'throttle' => env('IAM_VERIFICATION_THROTTLE', 6),
        'decay' => env('IAM_VERIFICATION_THROTTLE_DECAY', 1),
        'auto_send' => env('IAM_VERIFICATION_AUTO_SEND', true),
    ],
    'audit' => [
        'enabled' => env('IAM_AUDIT_ENABLED', true),
        'purge_after_days' => env('IAM_AUDIT_PURGE_AFTER_DAYS'),
    ],
    'governance' => [
        'default_assignment_duration_days' => env('IAM_GOVERNANCE_DEFAULT_ASSIGNMENT_DURATION_DAYS'),
    ],
];

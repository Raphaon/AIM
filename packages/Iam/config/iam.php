<?php

return [
    'guard' => env('IAM_GUARD', 'web'),
    'api_guard' => env('IAM_API_GUARD', 'sanctum'),
    'user_model' => env('IAM_USER_MODEL', App\Models\User::class),
    'route_prefix' => env('IAM_ROUTE_PREFIX', 'api/iam'),
    'pagination' => [
        'per_page' => env('IAM_PAGINATION_PER_PAGE', 15),
    ],
    'audit' => [
        'enabled' => env('IAM_AUDIT_ENABLED', true),
    ],
];

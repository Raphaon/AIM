<?php

namespace Aim\Iam\Services;

use Aim\Iam\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(?Authenticatable $user, string $action, string $model, ?int $modelId, array $oldValues = [], array $newValues = []): void
    {
        if (! config('iam.audit.enabled', true)) {
            return;
        }

        /** @var Request|null $request */
        $request = app()->bound('request') ? request() : null;

        if ($days = config('iam.audit.purge_after_days')) {
            AuditLog::where('created_at', '<', now()->subDays((int) $days))->delete();
        }

        AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}

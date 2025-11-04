<?php

namespace Aim\Iam\Console\Commands;

use Aim\Iam\Services\AuditLogger;
use Aim\Iam\Services\GovernanceService;
use Illuminate\Console\Command;

class ExpireAssignmentsCommand extends Command
{
    protected $signature = 'iam:expire-assignments';

    protected $description = 'Remove expired role and permission assignments';

    public function __construct(private readonly GovernanceService $governanceService, private readonly AuditLogger $auditLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $removed = $this->governanceService->removeExpiredAssignments();

        if ($removed['roles'] > 0) {
            $this->auditLogger->log(null, 'role.assignments.expired', 'role_user', null, [], ['removed' => $removed['roles']]);
        }

        if ($removed['permissions'] > 0) {
            $this->auditLogger->log(null, 'permission.assignments.expired', 'permission_user', null, [], ['removed' => $removed['permissions']]);
        }

        $this->info("Expired role assignments removed: {$removed['roles']}");
        $this->info("Expired permission assignments removed: {$removed['permissions']}");

        return self::SUCCESS;
    }
}

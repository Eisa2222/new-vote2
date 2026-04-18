<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use Spatie\Permission\Models\Role;

final class DeleteRoleAction
{
    /** Roles we never allow deleting — the system relies on them. */
    private const PROTECTED_ROLES = ['super_admin', 'committee', 'campaign_manager', 'auditor'];

    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(Role $role): void
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            throw new \DomainException(__('This role is built-in and cannot be deleted.'));
        }
        if ($role->users()->exists()) {
            throw new \DomainException(__('This role is still assigned to one or more users.'));
        }

        $this->log->execute('roles.deleted', $role);
        $role->delete();
    }
}

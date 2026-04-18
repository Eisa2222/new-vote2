<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class UpdateRoleAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    /**
     * @param  string[]  $permissionNames
     */
    public function execute(Role $role, array $permissionNames): Role
    {
        return DB::transaction(function () use ($role, $permissionNames) {
            $role->syncPermissions(Permission::whereIn('name', $permissionNames)->get());
            $this->log->execute('roles.updated', $role, ['permissions' => $permissionNames]);
            return $role->fresh();
        });
    }
}

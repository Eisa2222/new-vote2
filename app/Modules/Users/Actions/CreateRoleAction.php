<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class CreateRoleAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    /**
     * @param  string[]  $permissionNames
     */
    public function execute(string $roleName, array $permissionNames = []): Role
    {
        return DB::transaction(function () use ($roleName, $permissionNames) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(Permission::whereIn('name', $permissionNames)->get());
            $this->log->execute('roles.created', $role, ['permissions' => $permissionNames]);
            return $role;
        });
    }
}

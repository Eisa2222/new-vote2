<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;
use App\Modules\Users\Support\AssignableRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UpdateUserAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    /**
     * @param  array{name:string,email:string,password?:string|null,status?:string|null,roles?:string[]}  $data
     */
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->name   = $data['name'];
            $user->email  = $data['email'];
            $user->status = $data['status'] ?? $user->status ?? 'active';

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();

            // Security C-1 — privilege-escalation guard. Without this
            // any account with `users.manage` could grant itself or
            // any peer the super_admin role and take over the platform.
            // AssignableRoles enforces: actor can't grant roles they
            // don't hold, can't edit their own roles, and only a
            // super_admin can grant or revoke super_admin.
            $safeRoles = AssignableRoles::filter(
                Auth::user(),
                $user,
                $data['roles'] ?? [],
            );
            $user->syncRoles($safeRoles);

            $this->log->execute('users.updated', $user, ['roles' => $safeRoles]);
            return $user->fresh();
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;
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
            $user->syncRoles($data['roles'] ?? []);

            $this->log->execute('users.updated', $user, ['roles' => $data['roles'] ?? []]);
            return $user->fresh();
        });
    }
}

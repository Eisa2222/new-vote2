<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    /**
     * @param  array{name:string,email:string,password:string,status?:string|null,roles?:string[]}  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'status'   => $data['status'] ?? 'active',
            ]);
            $user->syncRoles($data['roles'] ?? []);

            $this->log->execute('users.created', $user, ['roles' => $data['roles'] ?? []]);
            return $user;
        });
    }
}

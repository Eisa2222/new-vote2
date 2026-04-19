<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class CreateUserAction
{
    public function __construct(private readonly LogActivityAction $log) {}


    public function execute(array $data): array
    {
        $hasPassword = ! empty($data['password']);

        $result = DB::transaction(function () use ($data, $hasPassword) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $hasPassword? Hash::make($data['password']) : Hash::make(Str::random(48)),
                'status'   => $data['status'] ?? 'active',
            ]);
            $user->syncRoles($data['roles'] ?? []);

            $this->log->execute('users.created', $user, [
                'roles'   => $data['roles'] ?? [],
                'invited' => ! $hasPassword,
            ]);

            return $user;
        });

        // Send the invite AFTER the transaction commits so the token
        // row the broker creates isn't rolled back on an outer failure.
        if (! $hasPassword) {
            Password::sendResetLink(['email' => $result->email]);
        }

        return ['user' => $result, 'invited' => ! $hasPassword];
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SportsSeeder::class,
            RolesPermissionsSeeder::class,
        ]);

        $this->seedUser('Super Admin', 'admin@sfpa.sa', 'password', 'super_admin');
        $this->seedUser('عضو اللجنة',   'committee@sfpa.sa', 'password', 'committee');
        $this->seedUser('منشئ الحملات', 'manager@sfpa.sa',   'password', 'campaign_manager');
    }

    private function seedUser(string $name, string $email, string $password, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => Hash::make($password),
                'status'   => 'active',
            ],
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}

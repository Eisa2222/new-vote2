<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class DeleteUserAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(User $user): void
    {
        if ($user->id === Auth::id()) {
            throw new \DomainException(__('You cannot delete your own account.'));
        }

        $lastSuperAdmin = $user->hasRole('super_admin')
            && User::role('super_admin')->count() <= 1;
        if ($lastSuperAdmin) {
            throw new \DomainException(__('Cannot remove the last super admin.'));
        }

        $this->log->execute('users.deleted', $user, ['email' => $user->email]);
        $user->delete();
    }
}

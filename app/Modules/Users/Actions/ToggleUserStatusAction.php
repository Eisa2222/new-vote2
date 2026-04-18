<?php

declare(strict_types=1);

namespace App\Modules\Users\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class ToggleUserStatusAction
{
    public function __construct(private readonly LogActivityAction $log) {}

    public function execute(User $user): User
    {
        if ($user->id === Auth::id()) {
            throw new \DomainException(__('You cannot deactivate your own account.'));
        }

        $next = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $next]);

        $this->log->execute('users.toggled', $user, ['status' => $next]);
        return $user->fresh();
    }
}

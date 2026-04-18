<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Lands the admin on the screen that matters most for their role.
 * Centralising this lets us remove the closure that used to live in
 * routes/web.php and keeps the redirect logic testable.
 */
final class AdminLandingController extends Controller
{
    /**
     * Role-aware default landing for /admin.
     *  - committee       → results queue (they approve)
     *  - campaign_manager→ campaigns board (they publish)
     *  - everyone else   → full dashboard
     */
    public function __invoke(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user?->hasRole('committee') && ! $user->can('users.manage')) {
            return redirect()->route('admin.campaigns.index');
        }

        if ($user?->hasRole('campaign_manager') && ! $user->can('users.manage')) {
            return redirect()->route('admin.campaigns.index');
        }

        return view('admin.dashboard');
    }
}

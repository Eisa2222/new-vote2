<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Queries\DashboardData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Role-aware default landing for /admin.
 *  - committee        → campaigns queue (they approve)
 *  - campaign_manager → campaigns board (they publish)
 *  - everyone else    → full dashboard with system-wide metrics
 *
 * Pulled out of routes/web.php to keep route definitions closure-free
 * and make the redirect logic testable.
 */
final class AdminLandingController extends Controller
{
    public function __invoke(DashboardData $dashboardData): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user?->hasRole('committee') && ! $user->can('users.manage')) {
            return redirect()->route('admin.campaigns.index');
        }

        if ($user?->hasRole('campaign_manager') && ! $user->can('users.manage')) {
            return redirect()->route('admin.campaigns.index');
        }

        return view('admin.dashboard', $dashboardData->fetch());
    }
}

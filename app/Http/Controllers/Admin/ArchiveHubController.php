<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Clubs\Models\Club;
use App\Modules\Players\Models\Player;
use Illuminate\Contracts\View\View;

/**
 * One-screen overview of everything currently in the archive
 * (soft-deleted). Each tile links to the per-module archive page
 * so the admin can restore / permanently-delete items in context.
 *
 * This page doesn't mutate anything — it's a router for the user's
 * attention. Actual restore / forceDelete stays in each module's
 * own controller.
 */
final class ArchiveHubController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        // Every tile is gated on the relevant permission AND on the
        // model actually supporting soft-deletes. If the trait is
        // missing the count is silently 0.
        $tiles = [
            [
                'key'       => 'users',
                'label'     => __('Users'),
                'icon'      => '👥',
                'count'     => $this->trashedCount(User::class),
                'href'      => route('admin.users.archive'),
                'visible'   => (bool) $user?->can('users.manage'),
                'color'     => 'from-brand-700 to-brand-500',
            ],
            [
                'key'       => 'clubs',
                'label'     => __('Clubs'),
                'icon'      => '🏟️',
                'count'     => $this->trashedCount(Club::class),
                'href'      => route('admin.clubs.archive'),
                'visible'   => (bool) $user?->can('clubs.viewAny'),
                'color'     => 'from-blue-600 to-cyan-500',
            ],
            [
                'key'       => 'players',
                'label'     => __('Players'),
                'icon'      => '🧍',
                'count'     => $this->trashedCount(Player::class),
                'href'      => route('admin.players.archive'),
                'visible'   => (bool) $user?->can('players.viewAny'),
                'color'     => 'from-emerald-600 to-green-500',
            ],
            [
                'key'       => 'campaigns',
                'label'     => __('Campaigns'),
                'icon'      => '🗳️',
                'count'     => $this->trashedCount(Campaign::class),
                'href'      => route('admin.campaigns.archiveList'),
                'visible'   => (bool) $user?->can('campaigns.viewAny'),
                'color'     => 'from-amber-500 to-orange-500',
            ],
        ];

        $tiles = array_values(array_filter($tiles, fn ($t) => $t['visible']));

        return view('admin.archive.index', compact('tiles'));
    }

    /**
     * Count soft-deleted rows for a model class. Defensive against a
     * model that may not have SoftDeletes yet — returns 0 instead of
     * throwing, so the hub stays usable during rollout.
     */
    private function trashedCount(string $modelClass): int
    {
        if (! method_exists($modelClass, 'onlyTrashed')) {
            return 0;
        }
        try {
            return $modelClass::onlyTrashed()->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Campaigns\Policies;

use App\Models\User;
use App\Modules\Campaigns\Models\Campaign;

final class CampaignPolicy
{
    public function viewAny(User $u): bool { return $u->can('campaigns.viewAny'); }
    public function view(User $u, Campaign $c): bool { return $u->can('campaigns.viewAny'); }
    public function create(User $u): bool { return $u->can('campaigns.create'); }
    public function update(User $u, Campaign $c): bool { return $u->can('campaigns.update'); }
    public function publish(User $u, Campaign $c): bool { return $u->can('campaigns.publish'); }
    public function close(User $u, Campaign $c): bool { return $u->can('campaigns.close'); }
    public function archive(User $u, Campaign $c): bool { return $u->can('campaigns.archive'); }
    public function viewStats(User $u, Campaign $c): bool { return $u->can('campaigns.viewAny'); }
    public function manageCategories(User $u, Campaign $c): bool { return $u->can('campaigns.update'); }
}

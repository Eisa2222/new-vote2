<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Clubs\Models\Club;
use Spatie\Permission\Models\Role;

function makeAuditor(): User
{
    seedRolesAndPermissions();
    Role::findOrCreate('auditor', 'web')->syncPermissions([
        'clubs.viewAny', 'players.viewAny', 'campaigns.viewAny', 'results.view',
    ]);
    $u = User::factory()->create();
    $u->assignRole('auditor');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    return $u;
}

it('auditor can view clubs list but not create', function () {
    $this->actingAs(makeAuditor())
        ->get('/admin/clubs')->assertOk();
    $this->actingAs(makeAuditor())
        ->get('/admin/clubs/create')->assertForbidden();
});

it('auditor cannot manage users', function () {
    $this->actingAs(makeAuditor())
        ->get('/admin/users')->assertForbidden();
});

it('auditor cannot create campaigns via API', function () {
    $this->actingAs(makeAuditor(), 'sanctum')
        ->postJson('/api/v1/campaigns', ['title_ar'=>'x','title_en'=>'x'])
        ->assertForbidden();
});

it('user with no role cannot access any admin page', function () {
    $u = User::factory()->create();
    $this->actingAs($u)
        ->get('/admin/clubs')->assertForbidden();
});

it('auditor cannot approve results', function () {
    seedRolesAndPermissions();
    $c = \App\Modules\Campaigns\Models\Campaign::create([
        'title_ar'=>'x','title_en'=>'x','type'=>'individual_award',
        'start_at'=>now(),'end_at'=>now()->addDay(),'status'=>'draft',
    ]);
    $r = \App\Modules\Results\Models\CampaignResult::create([
        'campaign_id' => $c->id, 'status' => 'calculated',
    ]);
    $this->actingAs(makeAuditor(), 'sanctum')
        ->postJson("/api/v1/results/{$r->id}/approve")
        ->assertStatus(403);
});

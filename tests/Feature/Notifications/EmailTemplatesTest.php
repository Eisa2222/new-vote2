<?php

declare(strict_types=1);

use App\Modules\Notifications\Models\EmailTemplate;
use App\Modules\Notifications\Support\TemplateRegistry;
use App\Modules\Notifications\Support\TemplateRenderer;

beforeEach(function () { seedRolesAndPermissions(); });

it('lists every known template key on the index', function () {
    $admin = makeSuperAdmin();
    $resp  = test()->actingAs($admin)->get(route('admin.email-templates.index'))->assertOk();

    foreach (array_keys(TemplateRegistry::EVENTS) as $key) {
        $resp->assertSee($key, false);
    }
});

it('opens the editor for a specific (key, type, locale) tuple', function () {
    $admin = makeSuperAdmin();
    test()->actingAs($admin)->get(route('admin.email-templates.edit', [
        'key' => 'campaign.published', 'type' => 'team_of_the_season', 'locale' => 'ar',
    ]))->assertOk()->assertSee('campaign.published', false);
});

it('404s on an unknown template key', function () {
    $admin = makeSuperAdmin();
    test()->actingAs($admin)->get(route('admin.email-templates.edit', [
        'key' => 'bogus.key', 'locale' => 'en',
    ]))->assertStatus(404);
});

it('persists an edit and respects the (key,type,locale) unique key', function () {
    $admin = makeSuperAdmin();

    test()->actingAs($admin)->post(route('admin.email-templates.update'), [
        'key'           => 'campaign.published',
        'campaign_type' => 'team_of_the_season',
        'locale'        => 'ar',
        'subject'       => '🆕 مخصص: {campaign.title}',
        'body'          => 'محتوى مخصص {voter.name}',
        'is_active'     => '1',
    ])->assertRedirect(route('admin.email-templates.index'));

    $row = EmailTemplate::where('key', 'campaign.published')
        ->where('campaign_type', 'team_of_the_season')->where('locale', 'ar')->first();
    expect($row)->not->toBeNull();
    expect($row->subject)->toContain('🆕');
});

it('resolve() walks the fallback cascade', function () {
    EmailTemplate::create([
        'key' => 'x.y', 'campaign_type' => null, 'locale' => 'en',
        'subject' => 'generic EN', 'body' => 'x', 'is_active' => true,
    ]);

    // Asked for (x.y, team_award, ar) → nothing exists → falls back to
    // generic EN as the final attempt in the cascade.
    $r = EmailTemplate::resolve('x.y', 'team_award', 'ar');
    expect($r)->not->toBeNull();
    expect($r->subject)->toBe('generic EN');
});

it('interpolates {variables} and leaves unknowns untouched', function () {
    $out = TemplateRenderer::interpolate('Hi {voter.name}, vote on {campaign.title}! {unknown}', [
        'voter.name'     => 'Ahmed',
        'campaign.title' => 'POTY',
    ]);
    expect($out)->toBe('Hi Ahmed, vote on POTY! {unknown}');
});

it('preview endpoint returns rendered subject + body', function () {
    $admin = makeSuperAdmin();

    $resp = test()->actingAs($admin)
        ->postJson(route('admin.email-templates.preview'), [
            'subject' => 'Hi {voter.name}',
            'body'    => 'Vote on {platform.name}',
        ])->assertOk();

    $json = $resp->json();
    expect($json['subject'])->toContain('Ahmed');
    expect($json['body'])->toContain('SFPA');
});

it('render() uses the caller fallback when no template exists', function () {
    EmailTemplate::query()->delete();

    $r = TemplateRenderer::render('nothing.here', null, 'en', ['x' => 'y'],
        ['subject' => 'Fallback', 'body' => 'Body {x}']);
    expect($r['resolved'])->toBeFalse();
    expect($r['subject'])->toBe('Fallback');
    expect($r['body'])->toBe('Body y');
});

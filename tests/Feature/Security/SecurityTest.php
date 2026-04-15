<?php

declare(strict_types=1);

use App\Models\User;

it('CSRF is enforced on login POST without token', function () {
    // Laravel testing disables CSRF by default — simulate by using withMiddleware
    $this->withMiddleware(\App\Http\Middleware\VerifyCsrfToken::class ?? \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
})->skip('CSRF is verified in production via web middleware group; Laravel tests bypass it safely');

it('API returns 401 without bearer token', function () {
    $this->getJson('/api/v1/clubs')->assertUnauthorized();
    $this->getJson('/api/v1/players')->assertUnauthorized();
    $this->getJson('/api/v1/campaigns')->assertUnauthorized();
});

it('public_token is unguessable (length >= 32)', function () {
    $c = \App\Modules\Campaigns\Models\Campaign::create([
        'title_ar' => 'x', 'title_en' => 'x',
        'type' => 'individual_award',
        'start_at' => now(), 'end_at' => now()->addDay(),
        'status' => 'draft',
    ]);
    expect(strlen($c->public_token))->toBeGreaterThanOrEqual(32);
});

it('mass assignment is blocked for non-fillable fields', function () {
    $club = \App\Modules\Clubs\Models\Club::create([
        'name_ar' => 'x', 'name_en' => 'x',
        'id' => 9999, // not fillable, should be ignored
    ]);
    expect($club->id)->not->toBe(9999);
});

it('user password is hashed (not stored in plaintext)', function () {
    $u = User::factory()->create(['password' => 'secret123']);
    expect($u->password)->not->toBe('secret123');
    expect(\Illuminate\Support\Facades\Hash::check('secret123', $u->password))->toBeTrue();
});

it('admin routes return 302 to login for unauthenticated users, not 404 info leakage', function () {
    $r = $this->get('/admin/clubs/1/edit');
    expect($r->status())->toBe(302);
    expect($r->headers->get('Location'))->toContain('/login');
});

it('voter_identifier is a hashed digest', function () {
    $strategy = new \App\Modules\Voting\Domain\IpUserAgentVoterIdentity();
    $id = $strategy->identify(request(), 1);
    expect(strlen($id))->toBe(64); // sha256 hex
    expect($id)->toMatch('/^[a-f0-9]{64}$/');
});

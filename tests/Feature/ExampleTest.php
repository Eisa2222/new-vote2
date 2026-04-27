<?php

test('root redirects guests to login', function () {
    // Behaviour change: home is no longer the public campaigns
    // directory — bare-domain visitors are sent to /login. Voters
    // arrive via /vote/club/{token} deep links so they bypass /.
    $this->get('/')->assertRedirect(route('login'));
});

test('root redirects authenticated admins to the admin landing', function () {
    seedRolesAndPermissions();
    $this->actingAs(makeSuperAdmin())
        ->get('/')
        ->assertRedirect(route('admin.landing'));
});

test('login page renders', function () {
    $this->get('/login')->assertOk()->assertSee('SFPA', false);
});

test('health endpoint is public', function () {
    $this->get('/up')->assertOk();
});

<?php

test('root redirects to public campaigns listing', function () {
    $this->get('/')->assertRedirect(route('public.campaigns'));
});

test('login page renders', function () {
    $this->get('/login')->assertOk()->assertSee('SFPA', false);
});

test('health endpoint is public', function () {
    $this->get('/up')->assertOk();
});

<?php

test('root redirects to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('login page renders', function () {
    $this->get('/login')->assertOk()->assertSee('SFPA', false);
});

test('health endpoint is public', function () {
    $this->get('/up')->assertOk();
});

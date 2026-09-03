<?php

it('redirects home to the dashboard', function () {
    $this->get('/')->assertRedirect(route('dashboard'));
});

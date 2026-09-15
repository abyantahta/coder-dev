<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthPageTest extends TestCase
{
    public function test_login_page_is_reachable(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect();
    }
}

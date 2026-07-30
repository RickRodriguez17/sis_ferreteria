<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_disabled(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_new_users_cannot_register(): void
    {
        $this->get('/register')->assertNotFound();
    }
}

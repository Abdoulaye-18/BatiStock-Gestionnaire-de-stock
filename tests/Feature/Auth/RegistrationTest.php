<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_inscription_publique_est_desactivee(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Pirate',
            'email' => 'pirate@example.com',
            'password' => 'MotDePasse#2026',
            'password_confirmation' => 'MotDePasse#2026',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'pirate@example.com']);
    }
}
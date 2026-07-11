<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Public self-registration is intentionally disabled: accounts
     * are created by administrators from the Users module.
     */
    public function test_register_page_is_gone(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_register_submission_is_gone(): void
    {
        $this->post('/register', [
            'first_name' => 'Amina',
            'last_name' => 'Berrada',
            'email' => 'amina@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
    }
}

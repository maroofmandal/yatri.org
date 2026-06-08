<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Traveler',
            'email' => 'traveler@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Test Traveler',
            'email' => 'traveler@example.com',
            'role' => 'user',
        ]);
        $this->assertNotNull(User::where('email', 'traveler@example.com')->first()?->password);
    }
}

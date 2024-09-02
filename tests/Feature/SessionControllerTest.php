<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_in_with_valid_credentials()
    {
        // Create a user
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Attempt login
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'token',
                 ]);

        // Optionally, you can check that the user is authenticated
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials()
    {
        // Attempt login with invalid credentials
        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'invalidpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'error' => 'Invalid credentials',
                 ]);
    }

    /** @test */
    public function it_logs_out_authenticated_user()
    {
        // Create a user
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Authenticate the user
        $token = Auth::guard('api')->attempt([
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Attempt logout
        $response = $this->withToken($token)->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Logged out successfully',
                 ]);

        // Optionally, check that the user is no longer authenticated
        $this->assertGuest();
    }
}

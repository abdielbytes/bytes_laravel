<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;


class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_resets_password()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Generate a password reset token
        $token = Password::createToken($user);

        // Reset password
        $response = $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'token' => $token,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Your password has been reset!']);
    }
}

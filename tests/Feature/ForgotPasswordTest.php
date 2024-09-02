<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;


class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_password_reset_link()
    {
        // Create a user
        $user = User::factory()->create();

        // Send reset link request
        $response = $this->postJson('/api/password/email', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'We have e-mailed your password reset link!']);
    }
}

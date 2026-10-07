<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_request_password_reset_token()
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com',
            'is_active' => true
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'john@example.com'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token'
            ])
            ->assertJsonPath('message', 'Password reset token has been sent. Please check your email.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'john@example.com'
        ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_request_password_reset_for_inactive_employee()
    {
        $employee = Employee::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'inactive@example.com'
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset token has been sent. Please check your email.');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'inactive@example.com'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_is_required_for_forgot_password()
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Email address is required.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_format_for_forgot_password()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'invalid-email'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Please provide a valid email address.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_exists_for_forgot_password()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'No employee found with this email address.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_replaces_existing_token_when_requesting_new_one()
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com'
        ]);

        // First request
        $response1 = $this->postJson('/api/auth/forgot-password', [
            'email' => 'john@example.com'
        ]);
        $token1 = $response1->json('token');

        // Second request
        $response2 = $this->postJson('/api/auth/forgot-password', [
            'email' => 'john@example.com'
        ]);
        $token2 = $response2->json('token');

        $this->assertNotEquals($token1, $token2);

        // Should only have one record in the database
        $tokenCount = DB::table('password_reset_tokens')
            ->where('email', 'john@example.com')
            ->count();
        $this->assertEquals(1, $tokenCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_reset_password_with_valid_token()
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        // Request reset token
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'john@example.com'
        ]);
        $token = $response->json('token');

        // Reset password
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password has been successfully reset.');

        // Verify password was changed
        $employee->refresh();
        $this->assertTrue(Hash::check('newpassword123', $employee->password));
        $this->assertFalse(Hash::check('oldpassword', $employee->password));

        // Verify token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'john@example.com'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cannot_reset_password_with_invalid_token()
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid or expired password reset token.');

        // Verify password was not changed
        $employee->refresh();
        $this->assertTrue(Hash::check('oldpassword', $employee->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cannot_reset_password_with_no_token_record()
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => 'some-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid or expired password reset token.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cannot_reset_password_with_expired_token()
    {
        $employee = Employee::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $token = 'test-token';

        // Create an expired token (older than 60 minutes)
        $expiredTime = now()->subMinutes(61);
        DB::table('password_reset_tokens')->insert([
            'email' => 'john@example.com',
            'token' => Hash::make($token),
            'created_at' => $expiredTime
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid or expired password reset token.');

        // Verify expired token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'john@example.com'
        ]);

        // Verify password was not changed
        $employee->refresh();
        $this->assertTrue(Hash::check('oldpassword', $employee->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_required_fields_for_reset_password()
    {
        $response = $this->postJson('/api/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'token', 'password']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_format_for_reset_password()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'invalid-email',
            'token' => 'some-token',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Please provide a valid email address.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_exists_for_reset_password()
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'nonexistent@example.com',
            'token' => 'some-token',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'No employee found with this email address.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_password_minimum_length_for_reset()
    {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => 'some-token',
            'password' => '123',
            'password_confirmation' => '123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Password must be at least 6 characters long.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_password_confirmation_for_reset()
    {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => 'some-token',
            'password' => 'password123',
            'password_confirmation' => 'different-password'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Password confirmation does not match.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_reset_password_for_inactive_employee()
    {
        $employee = Employee::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => false
        ]);

        // Request reset token
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'inactive@example.com'
        ]);
        $token = $response->json('token');

        // Reset password
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'inactive@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password has been successfully reset.');

        // Verify password was changed
        $employee->refresh();
        $this->assertTrue(Hash::check('newpassword123', $employee->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_stores_token_securely_in_database()
    {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'john@example.com'
        ]);

        $plainToken = $response->json('token');

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('email', 'john@example.com')
            ->first();

        // Token should be hashed in database
        $this->assertNotEquals($plainToken, $tokenRecord->token);
        $this->assertTrue(Hash::check($plainToken, $tokenRecord->token));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_response_structure_for_forgot_password()
    {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'john@example.com'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token'
            ])
            ->assertJsonCount(2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_response_structure_for_reset_password()
    {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        // Request reset token
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'john@example.com'
        ]);
        $token = $response->json('token');

        // Reset password
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'john@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message'
            ])
            ->assertJsonCount(1);
    }
}

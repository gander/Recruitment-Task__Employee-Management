<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_login_with_valid_credentials_and_active_account()
    {
        $employee = Employee::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'employee' => [
                    'id',
                    'full_name',
                    'email',
                    'phone',
                    'position',
                    'average_annual_salary',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('employee.email', 'test@example.com')
            ->assertJsonPath('employee.is_active', true);

        $this->assertNotEmpty($response->json('token'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cannot_login_with_invalid_email()
    {
        Employee::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cannot_login_with_invalid_password()
    {
        Employee::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cannot_login_with_inactive_account()
    {
        Employee::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Account is inactive. Please contact administrator.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_required_email_field()
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Email address is required.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Please provide a valid email address.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_required_password_field()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Password is required.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_password_minimum_length()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Password must be at least 6 characters.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_both_email_and_password_when_missing()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_proper_employee_data_on_successful_login()
    {
        $employee = Employee::factory()->create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+48123456789',
            'position' => 'front-end',
            'average_annual_salary' => 75000.00,
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('employee.full_name', 'John Doe')
            ->assertJsonPath('employee.email', 'john@example.com')
            ->assertJsonPath('employee.phone', '+48123456789')
            ->assertJsonPath('employee.position', 'front-end')
            ->assertJsonPath('employee.average_annual_salary', '75000.00')
            ->assertJsonPath('employee.is_active', true);

        $this->assertNotNull($response->json('employee.created_at'));
        $this->assertNotNull($response->json('employee.updated_at'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_personal_access_token_on_successful_login()
    {
        $employee = Employee::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => Employee::class,
            'tokenable_id' => $employee->id,
            'name' => 'api-token',
        ]);
    }
}

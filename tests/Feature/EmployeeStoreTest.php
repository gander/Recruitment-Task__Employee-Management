<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser()
    {
        $user = Employee::factory()->create(['is_active' => true]);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_employee_with_same_addresses()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+48123456789',
            'average_annual_salary' => 75000.50,
            'position' => 'front-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'residential_address_apartment_number' => '5',
            'different_correspondence_address' => false,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'employee' => [
                    'id',
                    'full_name',
                    'email',
                    'phone',
                    'position',
                    'average_annual_salary',
                    'residential_address_country',
                    'residential_address_postal_code',
                    'residential_address_city',
                    'residential_address_house_number',
                    'residential_address_apartment_number',
                    'different_correspondence_address',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonPath('message', 'Employee created successfully')
            ->assertJsonPath('employee.full_name', 'John Doe')
            ->assertJsonPath('employee.email', 'john.doe@example.com')
            ->assertJsonPath('employee.position', 'front-end')
            ->assertJsonPath('employee.is_active', true)
            ->assertJsonPath('employee.different_correspondence_address', false);

        $this->assertDatabaseHas('employees', [
            'email' => 'john.doe@example.com',
            'full_name' => 'John Doe',
            'position' => 'front-end',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_employee_with_different_correspondence_address()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'position' => 'back-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => true,
            'correspondence_address_country' => 'Germany',
            'correspondence_address_postal_code' => '10115',
            'correspondence_address_city' => 'Berlin',
            'correspondence_address_house_number' => '22',
            'correspondence_address_apartment_number' => '10',
            'is_active' => false,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(201)
            ->assertJsonPath('employee.different_correspondence_address', true)
            ->assertJsonPath('employee.correspondence_address_country', 'Germany')
            ->assertJsonPath('employee.correspondence_address_city', 'Berlin')
            ->assertJsonPath('employee.is_active', false);

        $this->assertDatabaseHas('employees', [
            'email' => 'jane.smith@example.com',
            'correspondence_address_country' => 'Germany',
            'correspondence_address_city' => 'Berlin',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication()
    {
        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'front-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_required_fields()
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/employees', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'full_name',
                'email',
                'position',
                'password',
                'residential_address_country',
                'residential_address_postal_code',
                'residential_address_city',
                'residential_address_house_number',
                'different_correspondence_address'
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_uniqueness()
    {
        $this->authenticatedUser();

        Employee::factory()->create(['email' => 'existing@example.com']);

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'existing@example.com',
            'position' => 'front-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'This email address is already registered.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_position_enum()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'invalid-position',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['position'])
            ->assertJsonPath('errors.position.0', 'Position must be one of: front-end, back-end, pm, designer, tester.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_password_minimum_length()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'front-end',
            'password' => '123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Password must be at least 6 characters.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_correspondence_address_when_different_is_true()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'front-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => true,
            // Missing correspondence address fields
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'correspondence_address_country',
                'correspondence_address_postal_code',
                'correspondence_address_city',
                'correspondence_address_house_number'
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_require_correspondence_address_when_different_is_false()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'front-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
            // No correspondence address fields provided
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(201);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_hashes_password_before_saving()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'front-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(201);

        $employee = Employee::where('email', 'john.doe@example.com')->first();
        $this->assertNotEquals('password123', $employee->password);
        $this->assertTrue(password_verify('password123', $employee->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_defaults_is_active_to_false_when_not_provided()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'front-end',
            'password' => 'password123',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
            // is_active not provided
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(201)
            ->assertJsonPath('employee.is_active', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_average_annual_salary_format()
    {
        $this->authenticatedUser();

        $employeeData = [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'position' => 'front-end',
            'password' => 'password123',
            'average_annual_salary' => 'not-a-number',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'different_correspondence_address' => false,
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['average_annual_salary']);
    }
}

<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser()
    {
        $user = Employee::factory()->create(['is_active' => true]);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_employee_basic_information()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'Original Name',
            'email' => 'original@example.com',
            'position' => 'front-end',
        ]);

        $updateData = [
            'full_name' => 'Updated Name',
            'email' => 'updated@example.com',
            'position' => 'back-end',
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('message', 'Employee updated successfully')
            ->assertJsonPath('employee.full_name', 'Updated Name')
            ->assertJsonPath('employee.email', 'updated@example.com')
            ->assertJsonPath('employee.position', 'back-end');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Updated Name',
            'email' => 'updated@example.com',
            'position' => 'back-end',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_employee_addresses()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'residential_address_country' => 'Poland',
            'residential_address_city' => 'Warsaw',
            'different_correspondence_address' => false,
        ]);

        $updateData = [
            'residential_address_country' => 'Germany',
            'residential_address_city' => 'Berlin',
            'residential_address_house_number' => '25B',
            'different_correspondence_address' => true,
            'correspondence_address_country' => 'France',
            'correspondence_address_city' => 'Paris',
            'correspondence_address_postal_code' => '75001',
            'correspondence_address_house_number' => '10',
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('employee.residential_address_country', 'Germany')
            ->assertJsonPath('employee.residential_address_city', 'Berlin')
            ->assertJsonPath('employee.different_correspondence_address', true)
            ->assertJsonPath('employee.correspondence_address_country', 'France')
            ->assertJsonPath('employee.correspondence_address_city', 'Paris');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_disable_correspondence_address()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'different_correspondence_address' => true,
            'correspondence_address_country' => 'Germany',
            'correspondence_address_city' => 'Berlin',
        ]);

        $updateData = [
            'different_correspondence_address' => false,
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('employee.different_correspondence_address', false)
            ->assertJsonPath('employee.correspondence_address_country', null)
            ->assertJsonPath('employee.correspondence_address_city', null);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_password()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $updateData = [
            'password' => 'newpassword123',
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk();

        $employee->refresh();
        $this->assertTrue(password_verify('newpassword123', $employee->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_is_active_status()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create(['is_active' => false]);

        $updateData = [
            'is_active' => true,
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('employee.is_active', true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_partial_data()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'position' => 'front-end',
        ]);

        $updateData = [
            'phone' => '+48999888777',
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('employee.phone', '+48999888777')
            ->assertJsonPath('employee.full_name', 'John Doe') // Should remain unchanged
            ->assertJsonPath('employee.email', 'john@example.com'); // Should remain unchanged
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication()
    {
        $employee = Employee::factory()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'full_name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_404_for_non_existent_employee()
    {
        $this->authenticatedUser();

        $response = $this->putJson('/api/employees/999', [
            'full_name' => 'Updated Name',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Employee not found');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_email_uniqueness_excluding_current_employee()
    {
        $this->authenticatedUser();

        $employee1 = Employee::factory()->create(['email' => 'employee1@example.com']);
        $employee2 = Employee::factory()->create(['email' => 'employee2@example.com']);

        // Should fail - trying to use another employee's email
        $response = $this->putJson("/api/employees/{$employee1->id}", [
            'email' => 'employee2@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Should succeed - using the same employee's email
        $response = $this->putJson("/api/employees/{$employee1->id}", [
            'email' => 'employee1@example.com',
        ]);

        $response->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_position_enum()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'position' => 'invalid-position',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['position'])
            ->assertJsonPath('errors.position.0', 'Position must be one of: front-end, back-end, pm, designer, tester.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_password_minimum_length()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Password must be at least 6 characters.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_correspondence_address_when_different_is_true()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'different_correspondence_address' => true,
            // Missing correspondence address fields
        ]);

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

        $employee = Employee::factory()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'different_correspondence_address' => false,
            'full_name' => 'Updated Name',
        ]);

        $response->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_average_annual_salary_format()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'average_annual_salary' => 'not-a-number',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['average_annual_salary']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_all_fields_at_once()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $updateData = [
            'full_name' => 'Complete Update',
            'email' => 'complete@example.com',
            'phone' => '+48111222333',
            'average_annual_salary' => 95000.50,
            'position' => 'designer',
            'password' => 'newpassword123',
            'residential_address_country' => 'Spain',
            'residential_address_postal_code' => '28001',
            'residential_address_city' => 'Madrid',
            'residential_address_house_number' => '50',
            'residential_address_apartment_number' => '12',
            'different_correspondence_address' => true,
            'correspondence_address_country' => 'Italy',
            'correspondence_address_postal_code' => '00118',
            'correspondence_address_city' => 'Rome',
            'correspondence_address_house_number' => '25',
            'correspondence_address_apartment_number' => '7',
            'is_active' => true,
        ];

        $response = $this->putJson("/api/employees/{$employee->id}", $updateData);

        $response->assertOk()
            ->assertJsonPath('employee.full_name', 'Complete Update')
            ->assertJsonPath('employee.email', 'complete@example.com')
            ->assertJsonPath('employee.position', 'designer')
            ->assertJsonPath('employee.residential_address_country', 'Spain')
            ->assertJsonPath('employee.correspondence_address_country', 'Italy')
            ->assertJsonPath('employee.is_active', true);

        $employee->refresh();
        $this->assertTrue(password_verify('newpassword123', $employee->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_updated_employee_data()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'full_name' => 'Updated Name',
        ]);

        $response->assertOk()
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
                    'correspondence_address_country',
                    'correspondence_address_postal_code',
                    'correspondence_address_city',
                    'correspondence_address_house_number',
                    'correspondence_address_apartment_number',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }
}

<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeShowTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser()
    {
        $user = Employee::factory()->create(['is_active' => true]);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_show_employee_details()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+48123456789',
            'position' => 'front-end',
            'average_annual_salary' => 75000.50,
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'residential_address_apartment_number' => '5',
            'different_correspondence_address' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonStructure([
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
            ])
            ->assertJsonPath('employee.full_name', 'John Doe')
            ->assertJsonPath('employee.email', 'john.doe@example.com')
            ->assertJsonPath('employee.phone', '+48123456789')
            ->assertJsonPath('employee.position', 'front-end')
            ->assertJsonPath('employee.average_annual_salary', '75000.50')
            ->assertJsonPath('employee.residential_address_country', 'Poland')
            ->assertJsonPath('employee.residential_address_city', 'Warsaw')
            ->assertJsonPath('employee.different_correspondence_address', false)
            ->assertJsonPath('employee.is_active', true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_show_employee_with_different_correspondence_address()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'Jane Smith',
            'residential_address_country' => 'Poland',
            'residential_address_city' => 'Warsaw',
            'different_correspondence_address' => true,
            'correspondence_address_country' => 'Germany',
            'correspondence_address_postal_code' => '10115',
            'correspondence_address_city' => 'Berlin',
            'correspondence_address_house_number' => '22',
            'correspondence_address_apartment_number' => '10',
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('employee.different_correspondence_address', true)
            ->assertJsonPath('employee.residential_address_country', 'Poland')
            ->assertJsonPath('employee.residential_address_city', 'Warsaw')
            ->assertJsonPath('employee.correspondence_address_country', 'Germany')
            ->assertJsonPath('employee.correspondence_address_city', 'Berlin')
            ->assertJsonPath('employee.correspondence_address_house_number', '22');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_all_address_fields_including_nulls()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'different_correspondence_address' => false,
            'correspondence_address_country' => null,
            'correspondence_address_postal_code' => null,
            'correspondence_address_city' => null,
            'correspondence_address_house_number' => null,
            'correspondence_address_apartment_number' => null,
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('employee.different_correspondence_address', false)
            ->assertJsonPath('employee.correspondence_address_country', null)
            ->assertJsonPath('employee.correspondence_address_city', null);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication()
    {
        $employee = Employee::factory()->create();

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_404_for_non_existent_employee()
    {
        $this->authenticatedUser();

        $response = $this->getJson('/api/employees/99999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Employee not found');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_employee_without_sensitive_data()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk();

        $responseData = $response->json();
        $this->assertArrayNotHasKey('password', $responseData['employee']);
        $this->assertArrayNotHasKey('remember_token', $responseData['employee']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_timestamps()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('employee.created_at', $employee->created_at->toISOString())
            ->assertJsonPath('employee.updated_at', $employee->updated_at->toISOString());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_data_types()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'average_annual_salary' => 85000.75,
            'is_active' => true,
            'different_correspondence_address' => false,
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk();

        $employeeData = $response->json('employee');
        $this->assertIsInt($employeeData['id']);
        $this->assertIsString($employeeData['full_name']);
        $this->assertIsString($employeeData['email']);
        $this->assertIsString($employeeData['average_annual_salary']);
        $this->assertIsBool($employeeData['is_active']);
        $this->assertIsBool($employeeData['different_correspondence_address']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_inactive_employee()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('employee.is_active', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_string_id_parameter()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('employee.id', $employee->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_employee_with_all_position_types()
    {
        $this->authenticatedUser();

        $positions = ['front-end', 'back-end', 'pm', 'designer', 'tester'];

        foreach ($positions as $position) {
            $employee = Employee::factory()->create(['position' => $position]);

            $response = $this->getJson("/api/employees/{$employee->id}");

            $response->assertOk()
                ->assertJsonPath('employee.position', $position);
        }
    }
}

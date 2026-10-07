<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser()
    {
        $user = Employee::factory()->create(['is_active' => true]);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_delete_employee()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Employee deleted successfully');

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
            'email' => 'john.doe@example.com'
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_employee_with_all_address_data()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'Jane Smith',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-123',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '15A',
            'residential_address_apartment_number' => '5',
            'different_correspondence_address' => true,
            'correspondence_address_country' => 'Germany',
            'correspondence_address_postal_code' => '10115',
            'correspondence_address_city' => 'Berlin',
            'correspondence_address_house_number' => '22',
            'correspondence_address_apartment_number' => '10',
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Employee deleted successfully');

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication()
    {
        $employee = Employee::factory()->create();

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(401);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_404_for_non_existent_employee()
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/employees/99999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Employee not found');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_response_structure()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'message'
            ])
            ->assertJsonCount(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_string_id_parameter()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_inactive_employee()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Employee deleted successfully');

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_employee_with_all_position_types()
    {
        $this->authenticatedUser();

        $positions = ['front-end', 'back-end', 'pm', 'designer', 'tester'];

        foreach ($positions as $position) {
            $employee = Employee::factory()->create(['position' => $position]);

            $response = $this->deleteJson("/api/employees/{$employee->id}");

            $response->assertOk()
                ->assertJsonPath('message', 'Employee deleted successfully');

            $this->assertDatabaseMissing('employees', [
                'id' => $employee->id
            ]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_employee_completely()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'Test Employee',
            'email' => 'test@example.com',
            'phone' => '+48123456789',
            'position' => 'front-end',
            'average_annual_salary' => 75000.50,
        ]);

        $employeeId = $employee->id;
        $employeeEmail = $employee->email;

        $response = $this->deleteJson("/api/employees/{$employeeId}");

        $response->assertOk();

        $this->assertDatabaseMissing('employees', [
            'id' => $employeeId,
            'email' => $employeeEmail,
            'full_name' => 'Test Employee'
        ]);

        $this->assertNull(Employee::find($employeeId));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_cannot_delete_same_employee_twice()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();
        $employeeId = $employee->id;

        $firstResponse = $this->deleteJson("/api/employees/{$employeeId}");
        $firstResponse->assertOk();

        $secondResponse = $this->deleteJson("/api/employees/{$employeeId}");
        $secondResponse->assertStatus(404)
            ->assertJsonPath('message', 'Employee not found');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_employee_with_minimal_data()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create([
            'full_name' => 'Minimal Employee',
            'email' => 'minimal@example.com',
            'position' => 'tester',
            'residential_address_country' => 'Poland',
            'residential_address_postal_code' => '00-001',
            'residential_address_city' => 'Warsaw',
            'residential_address_house_number' => '1',
            'different_correspondence_address' => false,
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Employee deleted successfully');

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id
        ]);
    }
}

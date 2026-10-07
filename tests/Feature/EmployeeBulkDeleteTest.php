<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeBulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser()
    {
        $user = Employee::factory()->create(['is_active' => true]);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_bulk_delete_multiple_employees()
    {
        $this->authenticatedUser();

        $employees = Employee::factory()->count(3)->create();
        $employeeIds = $employees->pluck('id')->toArray();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertOk()
            ->assertJsonPath('message', '3 employees deleted successfully')
            ->assertJsonPath('deleted_count', 3)
            ->assertJsonPath('deleted_ids', $employeeIds);

        foreach ($employeeIds as $id) {
            $this->assertDatabaseMissing('employees', ['id' => $id]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_delete_single_employee_via_bulk_endpoint()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create(['full_name' => 'John Doe']);

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => [$employee->id]
        ]);

        $response->assertOk()
            ->assertJsonPath('message', '1 employees deleted successfully')
            ->assertJsonPath('deleted_count', 1)
            ->assertJsonPath('deleted_ids', [$employee->id]);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_employees_with_all_address_data()
    {
        $this->authenticatedUser();

        $employees = Employee::factory()->count(2)->create([
            'residential_address_country' => 'Poland',
            'residential_address_city' => 'Warsaw',
            'different_correspondence_address' => true,
            'correspondence_address_country' => 'Germany',
            'correspondence_address_city' => 'Berlin',
        ]);

        $employeeIds = $employees->pluck('id')->toArray();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertOk()
            ->assertJsonPath('deleted_count', 2);

        foreach ($employeeIds as $id) {
            $this->assertDatabaseMissing('employees', ['id' => $id]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication()
    {
        $employees = Employee::factory()->count(2)->create();
        $employeeIds = $employees->pluck('id')->toArray();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertStatus(401);

        foreach ($employeeIds as $id) {
            $this->assertDatabaseHas('employees', ['id' => $id]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_required_employee_ids()
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/employees/bulk', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids'])
            ->assertJsonPath('errors.employee_ids.0', 'Employee IDs are required.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_employee_ids_is_array()
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => 'not-an-array'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids'])
            ->assertJsonPath('errors.employee_ids.0', 'Employee IDs must be provided as an array.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_minimum_one_employee_id()
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => []
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids'])
            ->assertJsonPath('errors.employee_ids.0', 'Employee IDs are required.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_maximum_100_employee_ids()
    {
        $this->authenticatedUser();

        $employeeIds = range(1, 101);

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids'])
            ->assertJsonPath('errors.employee_ids.0', 'Maximum 100 employees can be deleted at once.');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_employee_ids_are_integers()
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => ['invalid', 'string']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids.0', 'employee_ids.1']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_employee_ids_are_positive()
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => [0, -1]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids.0', 'employee_ids.1']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_employee_ids_exist_in_database()
    {
        $this->authenticatedUser();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => [99999, 99998]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids.0', 'employee_ids.1']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_mixed_existing_and_non_existing_ids()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => [$employee->id, 99999]
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids.1']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_delete_employees_with_different_positions()
    {
        $this->authenticatedUser();

        $positions = ['front-end', 'back-end', 'pm', 'designer', 'tester'];
        $employees = collect($positions)->map(function ($position) {
            return Employee::factory()->create(['position' => $position]);
        });

        $employeeIds = $employees->pluck('id')->toArray();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertOk()
            ->assertJsonPath('deleted_count', 5);

        foreach ($employeeIds as $id) {
            $this->assertDatabaseMissing('employees', ['id' => $id]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_delete_both_active_and_inactive_employees()
    {
        $this->authenticatedUser();

        $activeEmployee = Employee::factory()->create(['is_active' => true]);
        $inactiveEmployee = Employee::factory()->create(['is_active' => false]);

        $employeeIds = [$activeEmployee->id, $inactiveEmployee->id];

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertOk()
            ->assertJsonPath('deleted_count', 2);

        $this->assertDatabaseMissing('employees', ['id' => $activeEmployee->id]);
        $this->assertDatabaseMissing('employees', ['id' => $inactiveEmployee->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_response_structure()
    {
        $this->authenticatedUser();

        $employees = Employee::factory()->count(2)->create();
        $employeeIds = $employees->pluck('id')->toArray();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'deleted_count',
                'deleted_ids'
            ])
            ->assertJsonCount(3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_duplicate_employee_ids()
    {
        $this->authenticatedUser();

        $employee = Employee::factory()->create();
        $duplicateIds = [$employee->id, $employee->id, $employee->id];

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $duplicateIds
        ]);

        $response->assertOk()
            ->assertJsonPath('deleted_count', 1)
            ->assertJsonPath('deleted_ids', [$employee->id]);

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_maximum_allowed_employees()
    {
        $this->authenticatedUser();

        $employees = Employee::factory()->count(100)->create();
        $employeeIds = $employees->pluck('id')->toArray();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertOk()
            ->assertJsonPath('deleted_count', 100);

        foreach ($employeeIds as $id) {
            $this->assertDatabaseMissing('employees', ['id' => $id]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_employees_completely_with_all_data()
    {
        $this->authenticatedUser();

        $employees = Employee::factory()->count(3)->create([
            'full_name' => 'Test Employee',
            'email' => function () {
                return 'test' . rand(1000, 9999) . '@example.com';
            },
            'phone' => '+48123456789',
            'position' => 'front-end',
            'average_annual_salary' => 75000.50,
        ]);

        $employeeIds = $employees->pluck('id')->toArray();
        $employeeEmails = $employees->pluck('email')->toArray();

        $response = $this->deleteJson('/api/employees/bulk', [
            'employee_ids' => $employeeIds
        ]);

        $response->assertOk();

        foreach ($employeeIds as $index => $id) {
            $this->assertDatabaseMissing('employees', [
                'id' => $id,
                'email' => $employeeEmails[$index],
                'full_name' => 'Test Employee'
            ]);
            $this->assertNull(Employee::find($id));
        }
    }
}

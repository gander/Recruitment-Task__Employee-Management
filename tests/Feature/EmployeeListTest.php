<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeListTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_list_employees()
    {
        Employee::factory()->count(5)->create();

        $response = $this->getJson('/api/employees');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                ]
            ])
            ->assertJsonCount(5, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_employees_by_full_name()
    {
        Employee::factory()->create(['full_name' => 'John Doe']);
        Employee::factory()->create(['full_name' => 'Anna Smith']);

        $response = $this->getJson('/api/employees?filter[full_name]=John');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'John Doe');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_employees_by_full_name_with_non_ascii_letters()
    {
        Employee::factory()->create(['full_name' => 'Zażółć Gęślą']);
        Employee::factory()->create(['full_name' => 'Anna Smith']);

        $response = $this->getJson('/api/employees?filter[full_name]=' . rawurlencode('Zażółć'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Zażółć Gęślą');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_matches_full_name_case_insensitively_for_ascii_letters()
    {
        Employee::factory()->create(['full_name' => 'John Doe']);

        $this->getJson('/api/employees?filter[full_name]=jOHN')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_employees_by_email()
    {
        Employee::factory()->create(['email' => 'john@example.com']);
        Employee::factory()->create(['email' => 'anna@example.com']);

        $response = $this->getJson('/api/employees?filter[email]=john@example.com');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'john@example.com');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_employees_by_position()
    {
        Employee::factory()->create(['position' => 'front-end']);
        Employee::factory()->create(['position' => 'back-end']);

        $response = $this->getJson('/api/employees?filter[position]=front-end');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.position', 'front-end');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_filter_employees_by_active_status()
    {
        Employee::factory()->create(['is_active' => true]);
        Employee::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/employees?filter[is_active]=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_sort_employees_by_full_name()
    {
        Employee::factory()->create(['full_name' => 'Zoe Wilson']);
        Employee::factory()->create(['full_name' => 'Adam Smith']);

        $response = $this->getJson('/api/employees?sort=full_name');

        $response->assertOk()
            ->assertJsonPath('data.0.full_name', 'Adam Smith')
            ->assertJsonPath('data.1.full_name', 'Zoe Wilson');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_sort_employees_by_created_at_descending()
    {
        $older = Employee::factory()->create(['created_at' => now()->subDay()]);
        $newer = Employee::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/employees?sort=-created_at');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_supports_pagination()
    {
        Employee::factory()->count(20)->create();

        $response = $this->getJson('/api/employees?page[size]=5&page[number]=2');

        $response->assertOk()
            ->assertJsonCount(5, 'data');

        // Check if pagination data exists in any format
        $responseData = $response->json();
        $this->assertArrayHasKey('data', $responseData);
        $this->assertCount(5, $responseData['data']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_list_when_no_employees_exist()
    {
        $response = $this->getJson('/api/employees');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_invalid_filter_gracefully()
    {
        Employee::factory()->count(3)->create();

        $response = $this->getJson('/api/employees?filter[invalid_field]=test');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_invalid_sort_gracefully()
    {
        Employee::factory()->count(3)->create();

        $response = $this->getJson('/api/employees?sort=invalid_field');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }
}

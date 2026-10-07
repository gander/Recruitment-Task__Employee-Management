<?php

namespace Tests\Unit;

use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    public function test_password_and_remember_token_are_hidden_from_serialization(): void
    {
        $employee = new Employee(['full_name' => 'Jan', 'password' => 'secret1']);
        $employee->remember_token = 'abc';

        $array = $employee->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertSame('Jan', $array['full_name']);
    }

    public function test_password_is_hashed_on_assignment(): void
    {
        $employee = new Employee(['password' => 'secret1']);

        $this->assertNotSame('secret1', $employee->password);
        $this->assertTrue(Hash::check('secret1', $employee->password));
    }

    public function test_attributes_are_cast(): void
    {
        $employee = new Employee([
            'average_annual_salary' => 1234.5,
            'is_active' => 1,
            'different_correspondence_address' => 0,
        ]);

        $this->assertSame('1234.50', $employee->average_annual_salary);
        $this->assertTrue($employee->is_active);
        $this->assertFalse($employee->different_correspondence_address);
    }

    public function test_rules_cover_every_fillable_attribute_except_password(): void
    {
        $fillable = array_diff((new Employee)->getFillable(), ['password']);

        $this->assertEqualsCanonicalizing($fillable, array_keys(Employee::rules()));
    }

    public function test_correspondence_address_is_required_only_when_different(): void
    {
        $rules = collect(Employee::rules())->except('email')->all();
        $base = [
            'full_name' => 'Jan', 'position' => 'pm',
            'residential_address_country' => 'PL', 'residential_address_postal_code' => '00-001',
            'residential_address_city' => 'Warszawa', 'residential_address_house_number' => '1',
        ];

        $same = Validator::make($base + ['different_correspondence_address' => false], $rules);
        $different = Validator::make($base + ['different_correspondence_address' => true], $rules);

        $this->assertTrue($same->passes());
        $this->assertEqualsCanonicalizing([
            'correspondence_address_country', 'correspondence_address_postal_code',
            'correspondence_address_city', 'correspondence_address_house_number',
        ], $different->errors()->keys());
    }

    public function test_position_must_be_one_of_known_values(): void
    {
        $rules = ['position' => Employee::rules()['position']];

        $this->assertTrue(Validator::make(['position' => 'tester'], $rules)->passes());
        $this->assertTrue(Validator::make(['position' => 'ceo'], $rules)->fails());
    }

    public function test_salary_allows_at_most_two_decimals(): void
    {
        $rules = ['average_annual_salary' => Employee::rules()['average_annual_salary']];

        $this->assertTrue(Validator::make(['average_annual_salary' => '100.25'], $rules)->passes());
        $this->assertTrue(Validator::make(['average_annual_salary' => '100.255'], $rules)->fails());
    }
}

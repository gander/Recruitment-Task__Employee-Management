<?php

namespace Tests\Unit;

use App\Http\Requests\BulkDeleteEmployeesRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RequestsTest extends TestCase
{
    public function test_requests_are_always_authorized(): void
    {
        $this->assertTrue((new LoginRequest)->authorize());
        $this->assertTrue((new BulkDeleteEmployeesRequest)->authorize());
    }

    public function test_login_request_validates_email_and_password(): void
    {
        $request = new LoginRequest;
        $validate = fn (array $data) => Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validate(['email' => 'a@example.com', 'password' => 'secret'])->passes());

        $errors = $validate(['email' => 'nope', 'password' => '123'])->errors();
        $this->assertSame('Please provide a valid email address.', $errors->first('email'));
        $this->assertSame('Password must be at least 6 characters.', $errors->first('password'));

        $this->assertSame('Email address is required.', $validate([])->errors()->first('email'));
    }

    public function test_bulk_delete_request_rules(): void
    {
        // 'exists' needs a DB, so only the structural rules are exercised here.
        $rules = collect((new BulkDeleteEmployeesRequest)->rules())
            ->map(fn (array $r) => array_values(array_filter($r, fn ($x) => ! str_starts_with($x, 'exists'))))
            ->all();
        $validate = fn (array $data) => Validator::make($data, $rules);

        $this->assertTrue($validate(['employee_ids' => [1, 2]])->passes());
        $this->assertTrue($validate(['employee_ids' => []])->fails());
        $this->assertTrue($validate(['employee_ids' => [0]])->fails());
        $this->assertTrue($validate(['employee_ids' => ['x']])->fails());
        $this->assertTrue($validate(['employee_ids' => range(1, 101)])->fails());
        $this->assertTrue($validate(['employee_ids' => range(1, 100)])->passes());
    }
}

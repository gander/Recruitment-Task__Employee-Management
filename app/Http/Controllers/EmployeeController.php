<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */

namespace App\Http\Controllers;

use App\Http\Requests\BulkDeleteEmployeesRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;

class EmployeeController extends Controller
{
    /**
     * @group Employees
     *
     * List employees
     *
     * Returns a paginated list of all employees with filtering and sorting capabilities.
     *
     * @queryParam filter[full_name] string Filter by full name. Example: John Doe
     * @queryParam filter[email] string Filter by email address. Example: john@example.com
     * @queryParam filter[position] string Filter by position. Example: front-end
     * @queryParam filter[is_active] boolean Filter by active status. Example: true
     * @queryParam sort string Sort results. Available fields: full_name, email, position, created_at. Example: -created_at
     * @queryParam page[number] integer Page number. Example: 1
     * @queryParam page[size] integer Items per page (max 100). Example: 15
     *
     * @response 200 scenario="success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "full_name": "John Doe",
     *       "email": "john@example.com",
     *       "phone": "+48123456789",
     *       "position": "front-end",
     *       "average_annual_salary": "75000.00",
     *       "is_active": true,
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/employees?page[number]=1",
     *     "last": "http://localhost/api/employees?page[number]=5",
     *     "prev": null,
     *     "next": "http://localhost/api/employees?page[number]=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 5,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 75
     *   }
     * }
     */
    public function index()
    {
        return QueryBuilder::for(Employee::class)
            ->allowedFilters(
                'full_name',
                'email',
                'position',
                'is_active'
            )
            ->allowedSorts(
                'full_name',
                'email',
                'position',
                'created_at'
            )
            ->select([
                'id',
                'full_name',
                'email',
                'phone',
                'position',
                'average_annual_salary',
                'is_active',
                'created_at',
                'updated_at'
            ])
            ->jsonPaginate();
    }

    /**
     * @group Employees
     *
     * Create new employee
     *
     * Creates a new employee with complete address information. Handles both residential and correspondence addresses in a single request.
     *
     * @authenticated
     *
     * @bodyParam full_name string required Employee's full name. Example: John Doe
     * @bodyParam email string required Employee's email address (must be unique). Example: john.doe@example.com
     * @bodyParam phone string optional Employee's phone number. Example: +48123456789
     * @bodyParam average_annual_salary number optional Employee's annual salary. Example: 75000.50
     * @bodyParam position string required Employee's position. Must be one of: front-end, back-end, pm, designer, tester. Example: front-end
     * @bodyParam password string required Employee's password (minimum 6 characters). Example: password123
     * @bodyParam residential_address_country string required Residential address country. Example: Poland
     * @bodyParam residential_address_postal_code string required Residential address postal code. Example: 00-123
     * @bodyParam residential_address_city string required Residential address city. Example: Warsaw
     * @bodyParam residential_address_house_number string required Residential address house number. Example: 15A
     * @bodyParam residential_address_apartment_number string optional Residential address apartment number. Example: 5
     * @bodyParam different_correspondence_address boolean required Whether correspondence address is different from residential. Example: true
     * @bodyParam correspondence_address_country string optional Correspondence address country (required if different_correspondence_address is true). Example: Germany
     * @bodyParam correspondence_address_postal_code string optional Correspondence address postal code (required if different_correspondence_address is true). Example: 10115
     * @bodyParam correspondence_address_city string optional Correspondence address city (required if different_correspondence_address is true). Example: Berlin
     * @bodyParam correspondence_address_house_number string optional Correspondence address house number (required if different_correspondence_address is true). Example: 22
     * @bodyParam correspondence_address_apartment_number string optional Correspondence address apartment number. Example: 10
     * @bodyParam is_active boolean optional Whether employee is active (defaults to false). Example: true
     *
     * @response 201 scenario="success" {
     *   "message": "Employee created successfully",
     *   "employee": {
     *     "id": 1,
     *     "full_name": "John Doe",
     *     "email": "john.doe@example.com",
     *     "phone": "+48123456789",
     *     "position": "front-end",
     *     "average_annual_salary": "75000.50",
     *     "residential_address_country": "Poland",
     *     "residential_address_postal_code": "00-123",
     *     "residential_address_city": "Warsaw",
     *     "residential_address_house_number": "15A",
     *     "residential_address_apartment_number": "5",
     *     "different_correspondence_address": true,
     *     "correspondence_address_country": "Germany",
     *     "correspondence_address_postal_code": "10115",
     *     "correspondence_address_city": "Berlin",
     *     "correspondence_address_house_number": "22",
     *     "correspondence_address_apartment_number": "10",
     *     "is_active": true,
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 422 scenario="validation error" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["This email address is already registered."],
     *     "position": ["Position must be one of: front-end, back-end, pm, designer, tester."]
     *   }
     * }
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['is_active'] = $validatedData['is_active'] ?? false;

        $employee = Employee::create($validatedData);

        return response()->json([
            'message' => 'Employee created successfully',
            'employee' => $employee->fresh()
        ], 201);
    }

    /**
     * @group Employees
     *
     * Show employee details
     *
     * Returns detailed information about a specific employee including all address data.
     *
     * @authenticated
     *
     * @urlParam employee integer required Employee ID. Example: 1
     *
     * @response 200 scenario="success" {
     *   "employee": {
     *     "id": 1,
     *     "full_name": "John Doe",
     *     "email": "john.doe@example.com",
     *     "phone": "+48123456789",
     *     "position": "front-end",
     *     "average_annual_salary": "75000.50",
     *     "residential_address_country": "Poland",
     *     "residential_address_postal_code": "00-123",
     *     "residential_address_city": "Warsaw",
     *     "residential_address_house_number": "15A",
     *     "residential_address_apartment_number": "5",
     *     "different_correspondence_address": true,
     *     "correspondence_address_country": "Germany",
     *     "correspondence_address_postal_code": "10115",
     *     "correspondence_address_city": "Berlin",
     *     "correspondence_address_house_number": "22",
     *     "correspondence_address_apartment_number": "10",
     *     "is_active": true,
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 404 scenario="employee not found" {
     *   "message": "Employee not found"
     * }
     */
    public function show(string $id): JsonResponse
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }

        return response()->json([
            'employee' => $employee
        ]);
    }

    /**
     * @group Employees
     *
     * Update employee
     *
     * Updates an existing employee with address information. All fields are optional - only provided fields will be updated.
     *
     * @authenticated
     *
     * @urlParam employee integer required Employee ID. Example: 1
     * @bodyParam full_name string optional Employee's full name. Example: John Doe Updated
     * @bodyParam email string optional Employee's email address (must be unique). Example: john.updated@example.com
     * @bodyParam phone string optional Employee's phone number. Example: +48987654321
     * @bodyParam average_annual_salary number optional Employee's annual salary. Example: 85000.00
     * @bodyParam position string optional Employee's position. Must be one of: front-end, back-end, pm, designer, tester. Example: back-end
     * @bodyParam password string optional Employee's password (minimum 6 characters). Example: newpassword123
     * @bodyParam residential_address_country string optional Residential address country. Example: Germany
     * @bodyParam residential_address_postal_code string optional Residential address postal code. Example: 10115
     * @bodyParam residential_address_city string optional Residential address city. Example: Berlin
     * @bodyParam residential_address_house_number string optional Residential address house number. Example: 22A
     * @bodyParam residential_address_apartment_number string optional Residential address apartment number. Example: 8
     * @bodyParam different_correspondence_address boolean optional Whether correspondence address is different from residential. Example: false
     * @bodyParam correspondence_address_country string optional Correspondence address country (required if different_correspondence_address is true). Example: France
     * @bodyParam correspondence_address_postal_code string optional Correspondence address postal code (required if different_correspondence_address is true). Example: 75001
     * @bodyParam correspondence_address_city string optional Correspondence address city (required if different_correspondence_address is true). Example: Paris
     * @bodyParam correspondence_address_house_number string optional Correspondence address house number (required if different_correspondence_address is true). Example: 15
     * @bodyParam correspondence_address_apartment_number string optional Correspondence address apartment number. Example: 3
     * @bodyParam is_active boolean optional Whether employee is active. Example: true
     *
     * @response 200 scenario="success" {
     *   "message": "Employee updated successfully",
     *   "employee": {
     *     "id": 1,
     *     "full_name": "John Doe Updated",
     *     "email": "john.updated@example.com",
     *     "phone": "+48987654321",
     *     "position": "back-end",
     *     "average_annual_salary": "85000.00",
     *     "residential_address_country": "Germany",
     *     "residential_address_postal_code": "10115",
     *     "residential_address_city": "Berlin",
     *     "residential_address_house_number": "22A",
     *     "residential_address_apartment_number": "8",
     *     "different_correspondence_address": false,
     *     "correspondence_address_country": null,
     *     "correspondence_address_postal_code": null,
     *     "correspondence_address_city": null,
     *     "correspondence_address_house_number": null,
     *     "correspondence_address_apartment_number": null,
     *     "is_active": true,
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T12:00:00.000000Z"
     *   }
     * }
     *
     * @response 404 scenario="employee not found" {
     *   "message": "Employee not found"
     * }
     *
     * @response 422 scenario="validation error" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["This email address is already registered."],
     *     "position": ["Position must be one of: front-end, back-end, pm, designer, tester."]
     *   }
     * }
     */
    public function update(UpdateEmployeeRequest $request, string $id): JsonResponse
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }

        $validatedData = $request->validated();

        // Hash password if provided
        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        // Clear correspondence address fields if different_correspondence_address is set to false
        if (isset($validatedData['different_correspondence_address']) &&
            $validatedData['different_correspondence_address'] === false) {
            $validatedData['correspondence_address_country'] = null;
            $validatedData['correspondence_address_postal_code'] = null;
            $validatedData['correspondence_address_city'] = null;
            $validatedData['correspondence_address_house_number'] = null;
            $validatedData['correspondence_address_apartment_number'] = null;
        }

        $employee->update($validatedData);

        return response()->json([
            'message' => 'Employee updated successfully',
            'employee' => $employee->fresh()
        ]);
    }

    /**
     * @group Employees
     *
     * Delete employee
     *
     * Deletes an employee and all associated address data permanently. This action cannot be undone.
     *
     * @authenticated
     *
     * @urlParam employee integer required Employee ID. Example: 1
     *
     * @response 200 scenario="success" {
     *   "message": "Employee deleted successfully"
     * }
     *
     * @response 404 scenario="employee not found" {
     *   "message": "Employee not found"
     * }
     */
    public function destroy(string $id): JsonResponse
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'message' => 'Employee not found'
            ], 404);
        }

        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully'
        ]);
    }

    /**
     * @group Employees
     *
     * Bulk delete employees
     *
     * Deletes multiple employees and their associated address data based on provided employee IDs. Maximum 100 employees can be deleted at once.
     *
     * @authenticated
     *
     * @bodyParam employee_ids array required Array of employee IDs to delete (1-100 IDs). Example: [1, 2, 3]
     * @bodyParam employee_ids.* integer required Employee ID that exists in the database. Example: 1
     *
     * @response 200 scenario="success" {
     *   "message": "3 employees deleted successfully",
     *   "deleted_count": 3,
     *   "deleted_ids": [1, 2, 3]
     * }
     *
     * @response 422 scenario="validation error" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "employee_ids": ["Employee IDs are required."],
     *     "employee_ids.0": ["One or more employee IDs do not exist."]
     *   }
     * }
     */
    public function bulkDestroy(BulkDeleteEmployeesRequest $request): JsonResponse
    {
        $employeeIds = $request->validated()['employee_ids'];

        // Get existing employees to ensure we only delete what exists
        $existingEmployees = Employee::whereIn('id', $employeeIds)->pluck('id')->toArray();

        // Filter original IDs to keep only existing ones in original order and remove duplicates
        $uniqueEmployeeIds = array_unique($employeeIds);
        $orderedExistingIds = array_values(array_intersect($uniqueEmployeeIds, $existingEmployees));

        // Delete the employees
        $deletedCount = Employee::whereIn('id', $existingEmployees)->delete();

        return response()->json([
            'message' => "{$deletedCount} employees deleted successfully",
            'deleted_count' => $deletedCount,
            'deleted_ids' => $orderedExistingIds
        ]);
    }
}

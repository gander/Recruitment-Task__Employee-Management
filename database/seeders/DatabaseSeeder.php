<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Database\Seeders;

use App\Models\Employee;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Employee::factory()->create([
            'full_name' => 'Active Employee',
            'email' => 'active@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
            'position' => 'front-end',
        ]);

        Employee::factory()->create([
            'full_name' => 'Inactive Employee',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
            'position' => 'back-end',
        ]);

        // Create additional test employees for demonstration
        Employee::factory(15)->create();
    }
}

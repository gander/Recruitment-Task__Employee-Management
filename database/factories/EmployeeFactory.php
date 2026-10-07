<?php

/*
 * Copyright (c) 2025 Adam Gąsowski
 */
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $differentAddress = fake()->boolean(30);

        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'average_annual_salary' => fake()->randomFloat(2, 30000, 150000),
            'position' => fake()->randomElement(['front-end', 'back-end', 'pm', 'designer', 'tester']),
            'residential_address_country' => 'Polska',
            'residential_address_postal_code' => fake()->postcode(),
            'residential_address_city' => fake()->city(),
            'residential_address_house_number' => fake()->buildingNumber(),
            'residential_address_apartment_number' => fake()->optional()->buildingNumber(),
            'different_correspondence_address' => $differentAddress,
            'correspondence_address_country' => $differentAddress ? 'Polska' : null,
            'correspondence_address_postal_code' => $differentAddress ? fake()->postcode() : null,
            'correspondence_address_city' => $differentAddress ? fake()->city() : null,
            'correspondence_address_house_number' => $differentAddress ? fake()->buildingNumber() : null,
            'correspondence_address_apartment_number' => $differentAddress ? fake()->optional()->buildingNumber() : null,
            'is_active' => fake()->boolean(80),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

<?php
/*
 * Copyright (c) 2025 Adam Gąsowski
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->decimal('average_annual_salary', 10, 2)->nullable();
            $table->enum('position', ['front-end', 'back-end', 'pm', 'designer', 'tester']);

            // Residential address
            $table->string('residential_address_country');
            $table->string('residential_address_postal_code');
            $table->string('residential_address_city');
            $table->string('residential_address_house_number');
            $table->string('residential_address_apartment_number')->nullable();

            // Correspondence address
            $table->boolean('different_correspondence_address')->index();
            $table->string('correspondence_address_country')->nullable();
            $table->string('correspondence_address_postal_code')->nullable();
            $table->string('correspondence_address_city')->nullable();
            $table->string('correspondence_address_house_number')->nullable();
            $table->string('correspondence_address_apartment_number')->nullable();

            $table->boolean('is_active')->default(false)->index();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

<?php

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
        Schema::create('access_permissions', function (Blueprint $table) {
            $table->id();
            // Employee permission → user_id; temporary visitor permission → visitor fields.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('visitor_name', 150)->nullable();
            $table->string('company', 150)->nullable();
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('badge_id', 30)->index();
            $table->string('department', 100)->nullable()->index();
            $table->string('position', 100)->nullable();
            $table->string('access_level', 30)->default('reception')->index();
            $table->string('building', 100)->nullable();
            $table->string('floor', 50)->nullable();

            $table->json('working_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->string('type', 20)->default('permanent')->index(); // permanent | temporary
            $table->timestamps();
        });

        Schema::create('access_permission_door', function (Blueprint $table) {
            $table->foreignId('access_permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('door_id')->constrained()->cascadeOnDelete();
            $table->primary(['access_permission_id', 'door_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_permission_door');
        Schema::dropIfExists('access_permissions');
    }
};

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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_code', 20)->unique()->nullable();

            // Visitor identity
            $table->string('full_name', 150);
            $table->string('national_id', 50)->index();
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('photo')->nullable();
            $table->string('company', 150)->nullable();

            // Visit information
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('department', 100)->index();
            $table->string('purpose', 200);
            $table->date('visit_date')->index();
            $table->time('expected_check_in')->nullable();
            $table->unsignedSmallInteger('expected_duration_minutes')->default(60);
            $table->unsignedTinyInteger('companions')->default(0);
            $table->string('vehicle_plate', 30)->nullable();

            // Security information
            $table->string('document_type', 30)->default('national_id');
            $table->string('badge_number', 30)->nullable();
            $table->boolean('bag_inspected')->default(false);
            $table->boolean('special_permission')->default(false);
            $table->string('access_level', 30)->default('reception')->index();
            $table->boolean('blacklisted')->default(false);
            $table->text('security_notes')->nullable();

            // Visit state
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->string('status', 20)->default('expected')->index();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};

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
        Schema::create('biometric_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code', 20)->unique()->nullable();
            $table->string('department', 100)->index();
            $table->string('position', 100);

            // Enrollment state per modality
            $table->timestamp('face_enrolled_at')->nullable();
            $table->unsignedTinyInteger('face_quality')->nullable();
            $table->timestamp('fingerprint_enrolled_at')->nullable();
            $table->string('fingerprint_finger', 30)->nullable();
            $table->unsignedTinyInteger('fingerprint_quality')->nullable();
            $table->timestamp('iris_enrolled_at')->nullable();

            $table->foreignId('assigned_device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_profiles');
    }
};

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
        Schema::create('biometric_verifications', function (Blueprint $table) {
            $table->id();
            // Nullable: failed attempts by unknown subjects have no profile.
            $table->foreignId('biometric_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_name', 150); // snapshot, survives deletions
            $table->string('method', 20)->index();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('result', 20)->index();
            $table->string('detail', 200)->nullable();
            $table->unsignedSmallInteger('duration_ms')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('happened_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_verifications');
    }
};

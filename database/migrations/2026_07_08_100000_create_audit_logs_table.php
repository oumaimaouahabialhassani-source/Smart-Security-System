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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 150)->nullable(); // snapshot, survives deletions
            $table->string('user_role', 30)->nullable()->index();

            $table->string('module', 50)->index();
            $table->string('action', 80)->index();
            $table->string('description', 500);
            $table->string('status', 10)->default('success')->index(); // success | failed

            $table->string('ip_address', 45)->nullable()->index();
            $table->string('browser', 50)->nullable();
            $table->string('operating_system', 50)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('http_method', 10)->nullable();

            $table->timestamp('happened_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

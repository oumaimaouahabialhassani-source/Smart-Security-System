<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alerts produced by the AI Security Bot's analysis engine.
     */
    public function up(): void
    {
        Schema::create('ai_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('ai_code', 20)->unique()->nullable();
            $table->string('event_type', 100)->index();
            $table->string('description', 500);
            $table->string('risk_level', 20)->index();
            $table->unsignedTinyInteger('risk_score');
            $table->text('analysis'); // why the AI assigned this risk level
            $table->string('recommendation', 50);
            $table->foreignId('camera_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('door_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location', 150)->nullable();
            $table->string('building', 100)->nullable()->index();
            $table->string('floor', 50)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('notified_channels')->nullable();
            // Origin row the alert was derived from, so a monitoring sweep
            // never analyzes the same event twice.
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('happened_at')->index();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_alerts');
    }
};

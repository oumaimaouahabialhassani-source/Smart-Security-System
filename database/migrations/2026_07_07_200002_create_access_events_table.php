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
        Schema::create('access_events', function (Blueprint $table) {
            $table->id();
            // 'access' rows appear in the logs; 'security' rows feed the incident timeline.
            $table->string('kind', 20)->default('access')->index();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('person_name', 150); // snapshot, survives deletions
            $table->string('badge_id', 30)->nullable();

            $table->foreignId('door_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 10)->nullable(); // entry | exit
            $table->string('result', 30)->nullable()->index();   // access rows
            $table->string('severity', 20)->nullable()->index(); // security rows
            $table->string('method', 20)->nullable(); // badge | face | fingerprint | iris | manual

            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('camera_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('face_confidence')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('detail', 200)->nullable();

            $table->timestamp('happened_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_events');
    }
};

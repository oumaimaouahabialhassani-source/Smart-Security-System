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
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('camera_id', 50)->unique();
            $table->string('name', 100);
            $table->string('brand', 30)->index();
            $table->string('model', 100);
            $table->string('type', 30)->index();
            $table->string('ip_address', 45);
            $table->string('mac_address', 17);
            $table->string('username', 100);
            $table->text('password'); // encrypted cast on the model
            $table->string('rtsp_url');
            $table->string('location', 150);
            $table->string('building', 100)->index();
            $table->string('floor', 50)->index();
            $table->string('zone', 100)->index();
            $table->string('resolution', 20);
            $table->unsignedSmallInteger('fps');
            $table->boolean('recording_enabled')->default(true);
            $table->string('status', 20)->default('online')->index();
            $table->timestamp('last_seen')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};

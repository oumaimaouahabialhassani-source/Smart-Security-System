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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 50)->unique();
            $table->string('name', 100);
            $table->string('type', 40)->index();
            $table->string('brand', 60);
            $table->string('model', 100);
            $table->string('protocol', 20)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 17);
            $table->string('serial_number', 100);
            $table->string('firmware_version', 40);
            $table->string('username', 100);
            $table->text('password'); // encrypted cast on the model
            $table->string('building', 100)->index();
            $table->string('floor', 50)->index();
            $table->string('zone', 100)->index();
            $table->string('room', 100)->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable(); // null = mains-powered
            $table->string('signal_strength', 20)->index();
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
        Schema::dropIfExists('devices');
    }
};

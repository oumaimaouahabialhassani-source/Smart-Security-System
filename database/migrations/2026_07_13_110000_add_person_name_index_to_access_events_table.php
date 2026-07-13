<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The AI monitor and the suspicious-pattern queries group and
     * filter denials by person_name over a time window.
     */
    public function up(): void
    {
        Schema::table('access_events', function (Blueprint $table) {
            $table->index(['person_name', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::table('access_events', function (Blueprint $table) {
            $table->dropIndex(['person_name', 'happened_at']);
        });
    }
};

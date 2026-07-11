<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Almost every access query filters on kind AND sorts/filters on
     * happened_at; the composite index serves both in one pass. The
     * standalone kind index (2 distinct values) is dropped as redundant.
     */
    public function up(): void
    {
        Schema::table('access_events', function (Blueprint $table) {
            $table->index(['kind', 'happened_at']);
            $table->dropIndex(['kind']);
        });
    }

    public function down(): void
    {
        Schema::table('access_events', function (Blueprint $table) {
            $table->index('kind');
            $table->dropIndex(['kind', 'happened_at']);
        });
    }
};

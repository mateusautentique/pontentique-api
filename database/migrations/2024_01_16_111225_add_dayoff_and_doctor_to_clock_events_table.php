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
        Schema::table('clock_events', function (Blueprint $table) {
            $table->boolean('dayOff')->default(0);
            $table->boolean('doctor')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clock_events', function (Blueprint $table) {
            $table->dropColumn('dayOff');
            $table->dropColumn('doctor');
        });
    }
};

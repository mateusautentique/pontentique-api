<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('clock_events', 'deleted_at')) {
            return;
        }
    
        Schema::table('clock_events', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
    
    public function down()
    {
        if (!Schema::hasColumn('clock_events', 'deleted_at')) {
            return;
        }
    
        Schema::table('clock_events', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

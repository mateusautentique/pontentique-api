<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('clock_events', 'control_id')) {
            return;
        }
    
        Schema::table('clock_events', function (Blueprint $table) {
            $table->renameColumn('controlId', 'control_id');
        });
    }
    
    public function down()
    {
        if (!Schema::hasColumn('clock_events', 'control_id')) {
            return;
        }
    
        Schema::table('clock_events', function (Blueprint $table) {
            $table->renameColumn('control_id', 'controlId');
        });
    }
};

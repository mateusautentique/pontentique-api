<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        //DB::table('clock_events')->update(['control_id' => true]);
    }

    public function down()
    {
       //DB::table('clock_events')->update(['control_id' => false]);
    }
};

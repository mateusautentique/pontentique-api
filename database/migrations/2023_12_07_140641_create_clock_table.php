<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clock_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('timestamp');
            $table->timestamps();
            $table->string('justification')->nullable();
            $table->boolean('dayOff')->default(false);
            $table->boolean('doctor')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clock');
    }
};

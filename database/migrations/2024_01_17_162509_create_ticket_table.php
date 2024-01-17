<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->required();
            $table->foreignId('clock_id')->constrained()->nullable()->default(null)->onDelete('cascade');
            $table->enum('type', ['update', 'create', 'delete'])->required();
            $table->enum('status', ['pending', 'approved', 'reject'])->default('pending');
            $table->string('justification')->required();
            $table->json('requested_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};

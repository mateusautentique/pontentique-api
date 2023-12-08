<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClockEvent;

class ClockEventSeeder extends Seeder
{
    public function run(): void
    {
        ClockEvent::factory()
            ->count(10)
            ->create();
    }
}


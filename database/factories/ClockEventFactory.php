<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ClockEvent;

class ClockEventFactory extends Factory
{
    protected $model = ClockEvent::class;

    public function definition(): array
    {
        $date = $this->faker->date();
        
        return [
            'user_id' => 1,
            'timestamp' => $this->faker->dateTimeBetween($date, $date.' 23:59:59'),
            'timestamp' => $this->faker->dateTimeBetween($date, $date.' 23:59:59'),
            'timestamp' => $this->faker->dateTimeBetween($date, $date.' 23:59:59'),
            'timestamp' => $this->faker->dateTimeBetween($date, $date.' 23:59:59'),
        ];
    }
}

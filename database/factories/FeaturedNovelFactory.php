<?php

namespace Database\Factories;

use App\Models\FeaturedNovel;
use App\Models\Novel;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeaturedNovelFactory extends Factory
{
    protected $model = FeaturedNovel::class;

    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');

        return [
            'novel_id' => Novel::factory(),
            'position' => $this->faker->unique()->numberBetween(1, 100),
            'start_date' => $startDate,
            'end_date' => $this->faker->dateTimeBetween($startDate, '+1 month'),
        ];
    }
}
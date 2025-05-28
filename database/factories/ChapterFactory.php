<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition()
    {
        return [
            'novel_id' => Novel::factory(),
            'chapter_number' => $this->faker->numberBetween(1, 100),
            'title' => $this->faker->sentence(4),
            'audio_url' => $this->faker->url(),
            'content_text' => $this->faker->paragraphs(10, true),
            'order_index' => fn (array $attributes) => $attributes['chapter_number'],
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }
}
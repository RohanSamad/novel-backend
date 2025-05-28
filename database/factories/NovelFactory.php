<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Genre;
use App\Models\Novel;
use Illuminate\Database\Eloquent\Factories\Factory;

class NovelFactory extends Factory
{
    protected $model = Novel::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'author' => $this->faker->name(),
            'author_id' => Author::factory(),
            'publisher' => $this->faker->company(),
            'cover_image_url' => $this->faker->imageUrl(300, 400, 'book'),
            'synopsis' => $this->faker->paragraph(5),
            'status' => $this->faker->randomElement(['completed', 'ongoing', 'hiatus']),
            'publishing_year' => $this->faker->year(),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fn ($attributes) => $attributes['created_at'],
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Novel $novel) {
            $genreIds = Genre::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $novel->genres()->attach($genreIds);
        });
    }
}
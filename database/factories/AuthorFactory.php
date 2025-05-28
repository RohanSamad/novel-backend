<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'bio' => $this->faker->paragraph(3),
            'avatar_url' => $this->faker->imageUrl(200, 200, 'people'),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }
}
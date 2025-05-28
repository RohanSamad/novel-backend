<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Chapter;
use App\Models\FeaturedNovel;
use App\Models\Novel;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $authors = Author::factory()->count(10)->create();

        // Create 50 novels, each with 1-3 genres and 5-10 chapters
       $novels = Novel::factory()->count(50)->create()->each(function (Novel $novel) {
    Chapter::factory()->count(rand(5, 10))->create([
        'novel_id' => $novel->id,
        'chapter_number' => fn ($attributes, $index) => $index + 1,
        'order_index' => fn ($attributes, $index) => $index + 1,
    ]);
});

        // Create 5 featured novels
        FeaturedNovel::factory()->count(5)->create([
            'novel_id' => fn () => $novels->random()->id,
        ]);
}
}
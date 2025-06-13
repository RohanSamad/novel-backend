<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddRemainingGenresToGenresTable extends Migration
{
    public function up()
    {
        $genres = [
            ['name' => 'Thriller', 'slug' => 'thriller', 'description' => 'Suspenseful novels that keep readers on edge'],
            ['name' => 'Adventure', 'slug' => 'adventure', 'description' => 'Stories of exciting journeys and exploration'],
            ['name' => 'Contemporary', 'slug' => 'contemporary', 'description' => 'Modern-day stories reflecting current times'],
            ['name' => 'Urban Fantasy', 'slug' => 'urban-fantasy', 'description' => 'Fantasy stories set in modern urban settings'],
            ['name' => 'Young Adult', 'slug' => 'young-adult', 'description' => 'Stories targeting teenage and young adult readers'],
            ['name' => 'Harem', 'slug' => 'harem', 'description' => 'Stories featuring multiple romantic interests'],
            ['name' => 'Adult', 'slug' => 'adult', 'description' => 'Mature content intended for adult audiences'],
            ['name' => 'Cultivation', 'slug' => 'cultivation', 'description' => 'Stories about martial artists and spiritual cultivation'],
            ['name' => 'Game', 'slug' => 'game', 'description' => 'Stories based on or involving video games and gaming worlds'],
            ['name' => 'System', 'slug' => 'system', 'description' => 'Stories featuring system-based progression and mechanics'],
            ['name' => 'Reincarnation', 'slug' => 'reincarnation', 'description' => 'Stories about characters being reborn or transported to new worlds'],
            ['name' => 'Ecchi', 'slug' => 'ecchi', 'description' => 'Stories with mild adult themes and fanservice'],
            ['name' => 'Hentai', 'slug' => 'hentai', 'description' => 'Adult-oriented content with explicit themes'],
            ['name' => 'Dark', 'slug' => 'dark', 'description' => 'Stories with darker themes and mature content'],
            ['name' => 'Gore', 'slug' => 'gore', 'description' => 'Stories containing graphic violence and intense content'],
            ['name' => 'Other', 'slug' => 'other', 'description' => 'Stories that don\'t fit into other specific categories'],
            ['name' => 'Slice of Life', 'slug' => 'slice-of-life', 'description' => 'Stories focusing on everyday life experiences and personal growth'],
            ['name' => 'Isekai', 'slug' => 'isekai', 'description' => 'Stories about characters transported to another world'],
            ['name' => 'Fanfiction', 'slug' => 'fanfiction', 'description' => 'Stories based on existing works, characters, or universes'],
            ['name' => 'Anime / Comic', 'slug' => 'anime-comic', 'description' => 'Stories adapted from or inspired by anime and comics'],
            ['name' => 'Tragedy', 'slug' => 'tragedy', 'description' => 'Stories with dramatic and often sorrowful themes'],
            ['name' => 'War', 'slug' => 'war', 'description' => 'Stories centered around military conflicts and their impact'],
        ];

        DB::table('genres')->insertOrIgnore($genres);
    }

    public function down()
    {
        // Delete only the genres added in this migration
        $slugs = [
            'thriller',
            'adventure',
            'contemporary',
            'urban-fantasy',
            'young-adult',
            'harem',
            'adult',
            'cultivation',
            'game',
            'system',
            'reincarnation',
            'ecchi',
            'hentai',
            'dark',
            'gore',
            'other',
            'slice-of-life',
            'isekai',
            'fanfiction',
            'anime-comic',
            'tragedy',
            'war',
        ];

        DB::table('genres')->whereIn('slug', $slugs)->delete();
    }
}
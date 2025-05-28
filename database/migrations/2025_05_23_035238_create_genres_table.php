{{--

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateGenresTable extends Migration
{
    public function up()
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('name', 'genres_name_idx');
            $table->index('slug', 'genres_slug_idx');
        });

        DB::statement('ALTER TABLE genres ADD CONSTRAINT genres_name_length_check CHECK (length(name) >= 1 AND length(name) <= 255)');
    }

    public function down()
    {
        Schema::dropIfExists('genres');
    }
}
--}}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateGenresTable extends Migration
{
    public function up()
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();

            // Regular indexes
            $table->index('name', 'genres_name_idx');
            $table->index('slug', 'genres_slug_idx');
        });

        // Add constraints
        DB::statement('ALTER TABLE genres ADD CONSTRAINT genres_name_length_check CHECK (length(name) >= 1 AND length(name) <= 255)');

        // Only add trigram indexes if extension exists
        if ($this->pgTrgmExtensionExists()) {
            DB::statement('CREATE INDEX genres_name_trgm_idx ON genres USING gin (name gin_trgm_ops)');
            DB::statement('CREATE INDEX genres_description_trgm_idx ON genres USING gin (description gin_trgm_ops)');
        }
        
        // Insert default genres
        $this->seedDefaultGenres();
    }

    protected function pgTrgmExtensionExists(): bool
    {
        $result = DB::selectOne(
            "SELECT 1 FROM pg_extension WHERE extname = 'pg_trgm'"
        );
        return !empty($result);
    }

    protected function seedDefaultGenres()
    {
        $genres = [
            ['name' => 'Action', 'slug' => 'action', 'description' => 'Action-packed stories with thrilling adventures'],
            ['name' => 'Romance', 'slug' => 'romance', 'description' => 'Stories focusing on romantic relationships and love'],
            ['name' => 'Fantasy', 'slug' => 'fantasy', 'description' => 'Tales of magic, mythical creatures, and epic quests'],
            ['name' => 'Science Fiction', 'slug' => 'sci-fi', 'description' => 'Stories exploring futuristic technology and space'],
            ['name' => 'Mystery', 'slug' => 'mystery', 'description' => 'Intriguing detective stories and suspenseful mysteries'],
            ['name' => 'Horror', 'slug' => 'horror', 'description' => 'Frightening tales designed to scare and thrill'],
            ['name' => 'Historical', 'slug' => 'historical', 'description' => 'Stories set in past historical periods'],
            ['name' => 'Comedy', 'slug' => 'comedy', 'description' => 'Humorous stories meant to entertain and amuse'],
            ['name' => 'Drama', 'slug' => 'drama', 'description' => 'Character-driven stories with emotional depth'],
            ['name' => 'Literary', 'slug' => 'literary', 'description' => 'Sophisticated, character-focused literary works'],
        ];

        DB::table('genres')->insert($genres);
    }

    public function down()
    {
        Schema::dropIfExists('genres');
    }
}


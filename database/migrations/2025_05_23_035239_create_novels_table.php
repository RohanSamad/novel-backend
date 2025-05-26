{{--

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNovelsTable extends Migration
{
    public function up()
    {
        
        DB::statement("CREATE TYPE novel_status AS ENUM ('completed', 'ongoing', 'hiatus')");

        Schema::create('novels', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('title')->nullable(false);
            $table->text('author')->nullable(false);
            $table->uuid('author_id');
            $table->text('publisher')->nullable(false);
            $table->text('cover_image_url')->nullable(false);
            $table->text('synopsis')->nullable(false);
            $table->enum('novel_status', ['completed', 'ongoing', 'hiatus'])->default('ongoing');
            $table->text('genre')->nullable(false);
            $table->year('publishing_year')->nullable();
            $table->timestamp('created_at')->default(DB::raw('now()'));
            $table->timestamp('updated_at')->default(DB::raw('now()'));
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
            $table->index('author_id', 'novels_author_id_idx');
            $table->index('title', 'novels_title_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('novels');
        DB::statement("DROP TYPE IF EXISTS novel_status");
    }
}--}}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNovelsTable extends Migration
{
    public function up()
    {
        DB::statement("CREATE TYPE novel_status AS ENUM ('completed', 'ongoing', 'hiatus')");

        Schema::create('novels', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author'); // Keeping author field for backward compatibility
            $table->unsignedBigInteger('author_id');
            $table->string('publisher');
            $table->string('cover_image_url');
            $table->text('synopsis');
            $table->enum('status', ['completed', 'ongoing', 'hiatus'])->default('ongoing');
            $table->string('genre'); // Keeping single genre field for backward compatibility
            $table->timestamps();
            
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
        });

        // Add constraints and indexes
        DB::statement('ALTER TABLE novels ADD CONSTRAINT novels_title_length_check CHECK (length(title) >= 1 AND length(title) <= 255)');
        DB::statement('ALTER TABLE novels ADD CONSTRAINT novels_synopsis_length_check CHECK (length(synopsis) >= 10)');
        
        DB::statement('CREATE INDEX novels_title_idx ON novels (title)');
        DB::statement('CREATE INDEX novels_author_idx ON novels (author)');
        DB::statement('CREATE INDEX novels_genre_idx ON novels (genre)');
        DB::statement('CREATE INDEX novels_status_idx ON novels (status)');
        DB::statement('CREATE INDEX novels_title_trgm_idx ON novels USING gin (title gin_trgm_ops)');
        DB::statement('CREATE INDEX novels_author_trgm_idx ON novels USING gin (author gin_trgm_ops)');
        DB::statement('CREATE INDEX novels_synopsis_trgm_idx ON novels USING gin (synopsis gin_trgm_ops)');
    }

    public function down()
    {
        Schema::dropIfExists('novels');
        DB::statement("DROP TYPE IF EXISTS novel_status");
    }
}
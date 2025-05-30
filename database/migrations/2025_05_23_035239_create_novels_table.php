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
            $table->unsignedBigInteger('author_id');
            $table->string('publisher');
            $table->string('cover_image_url');
            $table->text('synopsis');
            $table->enum('status', ['completed', 'ongoing', 'hiatus'])->default('ongoing');
            $table->year('publishing_year')->nullable();
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
            $table->index('author_id', 'novels_author_id_idx');
            $table->index('title', 'novels_title_idx');
            $table->index('status', 'novels_status_idx');
        });

        DB::statement('ALTER TABLE novels ADD CONSTRAINT novels_title_length_check CHECK (length(title) >= 1 AND length(title) <= 255)');
        DB::statement('ALTER TABLE novels ADD CONSTRAINT novels_synopsis_length_check CHECK (length(synopsis) >= 10)');

        if ($this->pgTrgmExtensionExists()) {
            DB::statement('CREATE INDEX novels_title_trgm_idx ON novels USING gin (title gin_trgm_ops)');
            DB::statement('CREATE INDEX novels_synopsis_trgm_idx ON novels USING gin (synopsis gin_trgm_ops)');
        }

        $this->seedDefaultNovels();
    }

    protected function pgTrgmExtensionExists(): bool
    {
        $result = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'pg_trgm'");
        return !empty($result);
    }

    protected function seedDefaultNovels()
    {
        $authors = DB::table('authors')->get()->pluck('id', 'name')->toArray();

        $novels = [
            [
                'title' => 'Pride and Prejudice',
                'author_id' => $authors['Jane Austen'],
                'publisher' => 'T. Egerton',
                'cover_image_url' => 'https://example.com/covers/pride-and-prejudice.jpg',
                'synopsis' => 'A classic romance novel about Elizabeth Bennet and Mr. Darcy.',
                'status' => 'completed',
                'publishing_year' => 1813,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'The Adventures of Tom Sawyer',
                'author_id' => $authors['Mark Twain'],
                'publisher' => 'American Publishing Company',
                'cover_image_url' => 'https://example.com/covers/tom-sawyer.jpg',
                'synopsis' => 'The story of a young boy’s adventures in the Mississippi River town.',
                'status' => 'completed',
                'publishing_year' => 1876,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Harry Potter and the Philosopher\'s Stone',
                'author_id' => $authors['J.K. Rowling'],
                'publisher' => 'Bloomsbury',
                'cover_image_url' => 'https://example.com/covers/harry-potter.jpg',
                'synopsis' => 'A young wizard discovers his magical heritage.',
                'status' => 'completed',
                'publishing_year' => 1997,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('novels')->insert($novels);
    }

    public function down()
    {
        if ($this->pgTrgmExtensionExists()) {
            DB::statement('DROP INDEX IF EXISTS novels_title_trgm_idx');
            DB::statement('DROP INDEX IF EXISTS novels_synopsis_trgm_idx');
        }

        Schema::dropIfExists('novels');
        DB::statement("DROP TYPE IF EXISTS novel_status");
    }
}
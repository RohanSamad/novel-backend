<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAuthorsTable extends Migration
{
    public function up()
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });

        // Add trigram index
        if ($this->pgTrgmExtensionExists()) {
            DB::statement('CREATE INDEX authors_name_trgm_idx ON authors USING gin (name gin_trgm_ops)');
        }

        $this->seedDefaultAuthors();
    }

    protected function pgTrgmExtensionExists(): bool
    {
        $result = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'pg_trgm'");
        return !empty($result);
    }

    protected function seedDefaultAuthors()
    {
        $authors = [
            [
                'name' => 'Jane Austen',
                'bio' => 'Renowned English novelist known for her romantic fiction.',
                'avatar_url' => 'https://example.com/avatars/jane-austen.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mark Twain',
                'bio' => 'American writer and humorist, famous for his adventure stories.',
                'avatar_url' => 'https://example.com/avatars/mark-twain.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'J.K. Rowling',
                'bio' => 'British author best known for the Harry Potter series.',
                'avatar_url' => 'https://example.com/avatars/jk-rowling.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('authors')->insert($authors);
    }

    public function down()
    {
        if ($this->pgTrgmExtensionExists()) {
            DB::statement('DROP INDEX IF EXISTS authors_name_trgm_idx');
        }
        Schema::dropIfExists('authors');
    }
}
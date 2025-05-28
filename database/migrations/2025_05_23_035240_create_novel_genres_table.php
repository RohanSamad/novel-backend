<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNovelGenresTable extends Migration
{
    public function up()
    {
        Schema::create('novel_genres', function (Blueprint $table) {
            $table->unsignedBigInteger('novel_id');
            $table->unsignedBigInteger('genre_id');
            $table->primary(['novel_id', 'genre_id']);
            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
            $table->foreign('genre_id')->references('id')->on('genres')->onDelete('cascade');
            $table->index('novel_id', 'novel_genres_novel_id_idx');
            $table->index('genre_id', 'novel_genres_genre_id_idx');
        });

        $this->seedDefaultNovelGenres();
    }

    protected function seedDefaultNovelGenres()
    {
        $novels = DB::table('novels')->get()->pluck('id', 'title')->toArray();
        $genres = DB::table('genres')->get()->pluck('id', 'name')->toArray();

        $novelGenres = [
            ['novel_id' => $novels['Pride and Prejudice'], 'genre_id' => $genres['Romance']],
            ['novel_id' => $novels['Pride and Prejudice'], 'genre_id' => $genres['Literary']],
            ['novel_id' => $novels['The Adventures of Tom Sawyer'], 'genre_id' => $genres['Action']],
            ['novel_id' => $novels['The Adventures of Tom Sawyer'], 'genre_id' => $genres['Comedy']],
            ['novel_id' => $novels['Harry Potter and the Philosopher\'s Stone'], 'genre_id' => $genres['Fantasy']],
            ['novel_id' => $novels['Harry Potter and the Philosopher\'s Stone'], 'genre_id' => $genres['Action']],
        ];

        DB::table('novel_genres')->insert($novelGenres);
    }

    public function down()
    {
        Schema::dropIfExists('novel_genres');
    }
}
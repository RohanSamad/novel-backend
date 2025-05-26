{{--

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNovelGenresTable extends Migration
{
    public function up()
    {
        Schema::create('novel_genres', function (Blueprint $table) {
            $table->uuid('novel_id');
            $table->unsignedBigInteger('genre_id');
            $table->primary(['novel_id', 'genre_id']);
            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
            $table->foreign('genre_id')->references('id')->on('genres')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('novel_genres');
    }
}--}}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
    }

    public function down()
    {
        Schema::dropIfExists('novel_genres');
    }
}
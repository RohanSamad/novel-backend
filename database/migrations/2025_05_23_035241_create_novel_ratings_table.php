<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateNovelRatingsTable extends Migration
{
    public function up()
    {
        Schema::create('novel_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('novel_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('rating');
            $table->timestamps();

            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['novel_id', 'user_id']);
            $table->index('novel_id', 'novel_ratings_novel_id_idx');
            $table->index('user_id', 'novel_ratings_user_id_idx');
        });

        DB::statement('ALTER TABLE novel_ratings ADD CONSTRAINT novel_ratings_rating_check CHECK (rating >= 1 AND rating <= 5)');

        $this->seedDefaultRatings();
    }

    protected function seedDefaultRatings()
    {
        $novels = DB::table('novels')->get()->pluck('id', 'title')->toArray();
        $users = DB::table('users')->get()->pluck('id')->first(); // Assumes at least 1 user

        $ratings = [
            [
                'novel_id' => $novels['Pride and Prejudice'],
                'user_id' => $users,
                'rating' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('novel_ratings')->insert($ratings);
    }

    public function down()
    {
        Schema::dropIfExists('novel_ratings');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateChaptersTable extends Migration
{
    public function up()
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('novel_id');
            $table->integer('chapter_number');
            $table->string('title');
            $table->string('audio_url');
            $table->text('content_text');
            $table->integer('order_index');
            $table->timestamps();

            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
            $table->unique(['novel_id', 'chapter_number']);
        });

        // Add constraints and indexes
        DB::statement('ALTER TABLE chapters ADD CONSTRAINT chapters_title_length_check CHECK (length(title) >= 1 AND length(title) <= 255)');
        DB::statement('ALTER TABLE chapters ADD CONSTRAINT chapters_content_text_length_check CHECK (length(content_text) >= 1)');
        DB::statement('ALTER TABLE chapters ADD CONSTRAINT chapters_chapter_number_check CHECK (chapter_number > 0)');
        DB::statement('ALTER TABLE chapters ADD CONSTRAINT chapters_order_index_check CHECK (order_index > 0)');

        DB::statement('CREATE INDEX chapters_novel_id_idx ON chapters (novel_id)');
        DB::statement('CREATE INDEX chapters_title_idx ON chapters (title)');
        DB::statement('CREATE INDEX chapters_created_at_idx ON chapters (created_at)');
        DB::statement('CREATE INDEX chapters_novel_order_idx ON chapters (novel_id, order_index, chapter_number)');

        $this->seedDefaultChapters();
    }

    protected function seedDefaultChapters()
    {
        $novels = DB::table('novels')->get()->pluck('id', 'title')->toArray();

        $chapters = [
            [
                'novel_id' => $novels['Pride and Prejudice'],
                'chapter_number' => 1,
                'title' => 'Chapter 1: A New Acquaintance',
                'audio_url' => 'https://example.com/audio/pride-chapter1.mp3',
                'content_text' => 'It is a truth universally acknowledged...',
                'order_index' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'novel_id' => $novels['The Adventures of Tom Sawyer'],
                'chapter_number' => 1,
                'title' => 'Chapter 1: Tom’s Mischief',
                'audio_url' => 'https://example.com/audio/tom-sawyer-chapter1.mp3',
                'content_text' => 'Tom Sawyer lived with his Aunt Polly...',
                'order_index' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'novel_id' => $novels['Harry Potter and the Philosopher\'s Stone'],
                'chapter_number' => 1,
                'title' => 'Chapter 1: The Boy Who Lived',
                'audio_url' => 'https://example.com/audio/harry-potter-chapter1.mp3',
                'content_text' => 'Mr. and Mrs. Dursley, of number four, Privet Drive...',
                'order_index' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('chapters')->insert($chapters);
    }

    public function down()
    {
        Schema::dropIfExists('chapters');
    }
}
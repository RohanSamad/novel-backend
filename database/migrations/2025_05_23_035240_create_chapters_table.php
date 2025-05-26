{{--

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChaptersTable extends Migration
{
    public function up()
    {
        
        Schema::create('chapters', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('novel_id');
            $table->integer('chapter_number');
            $table->text('title');
            $table->text('audio_url');
            $table->text('content_text');
            $table->integer('order_index');
            $table->timestamp('created_at')->default(DB::raw('now()'));
            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
            $table->unique(['novel_id', 'chapter_number']);
            $table->index('novel_id', 'chapters_novel_id_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chapters');
    }
}--}}

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
    }

    public function down()
    {
        Schema::dropIfExists('chapters');
    }
}
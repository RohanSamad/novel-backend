

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFeaturedNovelsTable extends Migration
{
    public function up()
    {
        Schema::create('featured_novels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('novel_id');
            $table->integer('position');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->timestamps();
            
            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
            $table->unique('position');
        });

        DB::statement('ALTER TABLE featured_novels ADD CONSTRAINT featured_novels_date_check CHECK (start_date < end_date)');
        DB::statement('CREATE INDEX featured_novels_novel_id_idx ON featured_novels (novel_id)');
        DB::statement('CREATE INDEX featured_novels_date_idx ON featured_novels (start_date, end_date)');
    }

    public function down()
    {
        Schema::dropIfExists('featured_novels');
    }
}
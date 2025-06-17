<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('novel_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('novel_id')->primary();
            $table->string('title');
            $table->integer('chapter_count')->default(0);
            $table->integer('reader_count')->default(0);
            $table->float('average_rating')->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('total_views')->default(0);
            $table->timestamp('last_updated')->nullable();

            $table->foreign('novel_id')->references('id')->on('novels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('novel_stats');
    }
};


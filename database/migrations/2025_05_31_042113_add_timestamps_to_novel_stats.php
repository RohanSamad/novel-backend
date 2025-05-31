<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('novel_stats', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('novel_stats', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }
};
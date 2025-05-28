<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class EnablePgTrgmExtension extends Migration
{
    public function up()
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }

    public function down()
    {
        // Drop dependent trigram indexes
        $indexes = [
            'authors_name_trgm_idx',
            'genres_name_trgm_idx',
            'genres_description_trgm_idx',
            'novels_title_trgm_idx',
            'novels_author_trgm_idx',
            'novels_synopsis_trgm_idx',
        ];

        foreach ($indexes as $index) {
            DB::statement("DROP INDEX IF EXISTS $index");
        }

        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
}
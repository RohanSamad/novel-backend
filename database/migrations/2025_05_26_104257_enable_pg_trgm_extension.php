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
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
}


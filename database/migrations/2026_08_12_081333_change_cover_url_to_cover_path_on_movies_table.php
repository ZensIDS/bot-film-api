<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Rename cover_url -> cover_path. Sekarang menyimpan path relatif di disk "public"
        // (mis. "covers/xxx.jpg"), bukan URL penuh. URL untuk ditampilkan di-generate
        // lewat accessor Movie::getCoverUrlAttribute().
        // Pakai raw SQL (bukan ->renameColumn()) supaya tidak butuh package doctrine/dbal.
        DB::statement('ALTER TABLE movies CHANGE cover_url cover_path VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE movies CHANGE cover_path cover_url VARCHAR(255) NULL');
    }
};

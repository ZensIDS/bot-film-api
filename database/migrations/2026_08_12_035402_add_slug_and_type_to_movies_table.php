<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');

            // 'single' = film sekali tayang (pakai telegram_file_id di tabel ini).
            // 'series' = film ber-episode (video-nya disimpan per baris di tabel episodes).
            $table->enum('type', ['single', 'series'])->default('single')->after('genre');
        });

        // Isi slug untuk baris lama (kalau ada) supaya tidak null, berdasarkan title.
        DB::table('movies')->whereNull('slug')->orderBy('id')->each(function ($movie) {
            DB::table('movies')->where('id', $movie->id)->update([
                'slug' => Str::slug($movie->title) . '-' . $movie->id,
            ]);
        });

        // telegram_file_id wajib untuk type=single, tapi harus boleh kosong untuk type=series
        // (karena file id-nya ada per episode di tabel episodes).
        // Pakai raw SQL (bukan ->change()) supaya tidak butuh package doctrine/dbal.
        DB::statement('ALTER TABLE movies MODIFY telegram_file_id VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE movies MODIFY telegram_file_id VARCHAR(255) NOT NULL');

        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['slug', 'type']);
        });
    }
};

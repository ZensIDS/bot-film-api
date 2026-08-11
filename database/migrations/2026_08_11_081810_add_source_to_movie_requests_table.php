<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('movie_requests', function (Blueprint $table) {
            // Asal film diminta dari aplikasi mana (Netflix, Viu, WeTV, dll)
            $table->string('source')->nullable()->after('movie_title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movie_requests', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};

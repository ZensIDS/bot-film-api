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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('episode_number');
            $table->string('title')->nullable(); // opsional, mis. "Episode 1: Awal Pertemuan"
            $table->string('telegram_file_id');
            $table->timestamps();

            // Tidak boleh ada episode_number ganda dalam satu movie yang sama.
            $table->unique(['movie_id', 'episode_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('episodes');
    }
};

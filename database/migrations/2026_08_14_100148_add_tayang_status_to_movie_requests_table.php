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
        // Ubah enum status: tambahkan 'TAYANG' (film sudah selesai diproses & bisa dilihat)
        DB::statement("ALTER TABLE movie_requests MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'REJECTED', 'TAYANG') NOT NULL DEFAULT 'PENDING'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Sebelum rollback, pastikan tidak ada baris dengan status TAYANG agar tidak truncate error
        DB::table('movie_requests')->where('status', 'TAYANG')->update(['status' => 'APPROVED']);

        DB::statement("ALTER TABLE movie_requests MODIFY COLUMN status ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING'");
    }
};

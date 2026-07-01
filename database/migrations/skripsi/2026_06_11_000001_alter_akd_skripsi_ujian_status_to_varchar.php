<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterAkdSkripsiUjianStatusToVarchar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Alter status column to VARCHAR(50) to support all statuses dynamically
        DB::statement("ALTER TABLE akd_skripsi_ujian MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE akd_skripsi_ujian MODIFY COLUMN status ENUM('pending','dijadwalkan','lulus','tidak_lulus','revisi') NOT NULL DEFAULT 'pending'");
    }
}

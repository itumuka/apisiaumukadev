<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterAkdSkripsiBimbinganStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Alter status column from enum to varchar(30) to support longer status strings
        DB::statement("ALTER TABLE akd_skripsi_bimbingan MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending'");

        // 2. Restore existing logs that were truncated to empty strings back to 'disetujui_kaprodi'
        DB::table('akd_skripsi_bimbingan')
            ->where('status', '')
            ->update(['status' => 'disetujui_kaprodi']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert column back to ENUM
        DB::statement("ALTER TABLE akd_skripsi_bimbingan MODIFY COLUMN status ENUM('pending', 'disetujui', 'revisi') NOT NULL DEFAULT 'pending'");
    }
}

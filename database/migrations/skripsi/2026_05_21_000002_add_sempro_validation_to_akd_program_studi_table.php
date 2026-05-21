<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSemproValidationToAkdProgramStudiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_program_studi', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_program_studi', 'ta_sempro_is_validated')) {
                $table->tinyInteger('ta_sempro_is_validated')->default(1)->comment('0=Pending, 1=Validated')->after('ta_sempro_skema');
            }
        });

        // Mengubah enum agar sesuai dengan value yang dikirim dari Controller & Model (skripsi/matakuliah)
        DB::statement("ALTER TABLE akd_program_studi MODIFY COLUMN ta_sempro_skema ENUM('skripsi', 'matakuliah') DEFAULT 'skripsi'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_program_studi', function (Blueprint $table) {
            if (Schema::hasColumn('akd_program_studi', 'ta_sempro_is_validated')) {
                $table->dropColumn('ta_sempro_is_validated');
            }
        });

        // Mengembalikan enum ke state awal jika diperlukan rollback
        DB::statement("ALTER TABLE akd_program_studi MODIFY COLUMN ta_sempro_skema ENUM('AUTOMATIC', 'MANUAL') DEFAULT 'AUTOMATIC'");
    }
}
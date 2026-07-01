<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTaIsObeToAkdProgramStudiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_program_studi', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_program_studi', 'ta_is_obe')) {
                $table->tinyInteger('ta_is_obe')->default(1)->comment('1=Mendukung OBE, 0=Tidak Mendukung (Traditional)')->after('ta_sempro_is_validated');
            }
        });

        // Set D3 to 0 (non-OBE)
        DB::table('akd_program_studi')
            ->where('nama_program_studi', 'like', '%D3%')
            ->orWhere('kode_jenjang_pendidikan', 'D3')
            ->update(['ta_is_obe' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_program_studi', function (Blueprint $table) {
            if (Schema::hasColumn('akd_program_studi', 'ta_is_obe')) {
                $table->dropColumn('ta_is_obe');
            }
        });
    }
}

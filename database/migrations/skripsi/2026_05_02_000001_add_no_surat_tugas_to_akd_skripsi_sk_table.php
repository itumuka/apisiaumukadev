<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoSuratTugasToAkdSkripsiSkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi_sk', function (Blueprint $table) {
            $table->string('no_surat_tugas', 100)->nullable()->after('no_sk');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_skripsi_sk', function (Blueprint $table) {
            $table->dropColumn('no_surat_tugas');
        });
    }
}

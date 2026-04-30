<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAkdSkripsiUjianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi_ujian', function (Blueprint $table) {
            $table->integer('id_proposal')->nullable()->comment('FK akd_skripsi_proposal.id — naskah yang diujikan')->after('id_skripsi');
            $table->text('catatan_mhs')->nullable()->after('id_proposal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_skripsi_ujian', function (Blueprint $table) {
            $table->dropColumn(['id_proposal', 'catatan_mhs']);
        });
    }
}

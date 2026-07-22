<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNomorBaToAkdSkripsiBeritaAcara extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi_berita_acara', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi_berita_acara', 'nomor_ba')) {
                $table->string('nomor_ba', 100)->nullable()->after('id_skripsi_ujian');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_skripsi_berita_acara', function (Blueprint $table) {
            if (Schema::hasColumn('akd_skripsi_berita_acara', 'nomor_ba')) {
                $table->dropColumn('nomor_ba');
            }
        });
    }
}

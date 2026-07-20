<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidIdPengujiToAkdSkripsiBeritaAcara extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi_berita_acara', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi_berita_acara', 'valid_id_penguji1')) {
                $table->string('valid_id_penguji1', 100)->nullable()->after('setuju_penguji1');
            }
            if (!Schema::hasColumn('akd_skripsi_berita_acara', 'valid_id_penguji2')) {
                $table->string('valid_id_penguji2', 100)->nullable()->after('setuju_penguji2');
            }
            if (!Schema::hasColumn('akd_skripsi_berita_acara', 'valid_id_penguji3')) {
                $table->string('valid_id_penguji3', 100)->nullable()->after('setuju_penguji3');
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
            if (Schema::hasColumn('akd_skripsi_berita_acara', 'valid_id_penguji1')) {
                $table->dropColumn('valid_id_penguji1');
            }
            if (Schema::hasColumn('akd_skripsi_berita_acara', 'valid_id_penguji2')) {
                $table->dropColumn('valid_id_penguji2');
            }
            if (Schema::hasColumn('akd_skripsi_berita_acara', 'valid_id_penguji3')) {
                $table->dropColumn('valid_id_penguji3');
            }
        });
    }
}

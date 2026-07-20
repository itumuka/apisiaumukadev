<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeputusanToAkdSkripsiBeritaAcara extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi_berita_acara', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi_berita_acara', 'keputusan')) {
                $table->string('keputusan', 50)->nullable()->after('nilai_huruf');
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
            if (Schema::hasColumn('akd_skripsi_berita_acara', 'keputusan')) {
                $table->dropColumn('keputusan');
            }
        });
    }
}

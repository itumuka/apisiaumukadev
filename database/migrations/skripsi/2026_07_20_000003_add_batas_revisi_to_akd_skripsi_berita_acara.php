<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatasRevisiToAkdSkripsiBeritaAcara extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi_berita_acara', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi_berita_acara', 'batas_revisi')) {
                $table->date('batas_revisi')->nullable()->after('keputusan');
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
            if (Schema::hasColumn('akd_skripsi_berita_acara', 'batas_revisi')) {
                $table->dropColumn('batas_revisi');
            }
        });
    }
}

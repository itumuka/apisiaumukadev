<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAkdProgramStudiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_program_studi', function (Blueprint $table) {
            $table->smallInteger('ta_sks_minimal')->default(138)->comment('Minimal SKS untuk masuk modul TA');
            $table->tinyInteger('ta_ada_sempro')->default(1)->comment('1=Wajib Sempro, 0=Langsung Bimbingan');
            $table->smallInteger('ta_minimal_bimbingan')->default(8)->comment('Minimal jumlah log bimbingan yang disetujui');
            $table->string('ta_komponen_bayar', 100)->default('Bimbingan Skripsi')->comment('Nama komponen di keu_tagihan_mhs');
            $table->string('ta_komponen_bayar_ujian', 100)->default('Ujian Skripsi')->comment('Nama komponen ujian di keu_tagihan_mhs');
            $table->string('ta_nama_tugas_akhir', 50)->default('Skripsi')->comment('Label TA di UI, cth: Skripsi / Tugas Akhir');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_program_studi', function (Blueprint $table) {
            $table->dropColumn([
                'ta_sks_minimal', 
                'ta_ada_sempro', 
                'ta_minimal_bimbingan', 
                'ta_komponen_bayar', 
                'ta_komponen_bayar_ujian', 
                'ta_nama_tugas_akhir'
            ]);
        });
    }
}

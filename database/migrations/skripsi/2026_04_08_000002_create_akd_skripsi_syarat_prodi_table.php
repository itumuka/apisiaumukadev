<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiSyaratProdiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_syarat_prodi', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('kode_prodi', 20);
            $table->enum('kode_jenjang', ['S1', 'D4', 'D3']);
            $table->enum('fase', ['sempro', 'ujian']);
            $table->string('kode_syarat', 50);
            $table->enum('operator', ['>=', '<=', '=', 'EXISTS', '-'])->default('>=');
            $table->string('nilai_target', 100)->nullable();
            $table->string('petugas_validasi', 100)->default('Petugas Fakultas');
            $table->enum('tipe_upload', ['file', 'url', 'bebas'])->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->tinyInteger('is_wajib')->default(1);
            $table->tinyInteger('is_aktif')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('akd_skripsi_syarat_prodi');
    }
}

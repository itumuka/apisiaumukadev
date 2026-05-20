<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiLuaranTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_luaran', function (Blueprint $table) {
            $table->id();
            $table->integer('id_skripsi')->index();
            $table->string('nim', 20)->index();
            $table->enum('jenis_luaran', [
                'buku_skripsi', 
                'jurnal_sinta', 
                'jurnal_internasional', 
                'prosiding', 
                'paten', 
                'hki', 
                'lainnya'
            ])->default('buku_skripsi');
            $table->text('judul_luaran')->nullable()->comment('Judul artikel jurnal/paten/HKI');
            $table->string('nama_media', 255)->nullable()->comment('Nama Jurnal/Konferensi/Instansi HKI');
            $table->string('url_link', 255)->nullable()->comment('URL publikasi/OJS/HKI');
            $table->string('file_bukti', 255)->nullable()->comment('Path file bukti (LOA, Sertifikat, dll)');
            $table->enum('status_validasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('akd_skripsi_luaran');
    }
}

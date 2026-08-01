<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkpiPrestasiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skpi_prestasi', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 20)->index();
            $table->string('nama_kegiatan_id', 255);
            $table->string('nama_kegiatan_en', 255);
            $table->string('kategori', 50)->index(); // sertifikasi, organisasi, prestasi, magang, dll.
            $table->string('peran_id', 100);
            $table->string('peran_en', 100);
            $table->string('penyelenggara_id', 255);
            $table->string('penyelenggara_en', 255);
            $table->date('tanggal_perolehan');
            $table->string('path_bukti', 255);
            $table->enum('status_verifikasi', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->text('catatan_verifikator')->nullable();
            $table->integer('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
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
        Schema::dropIfExists('akd_skpi_prestasi');
    }
}

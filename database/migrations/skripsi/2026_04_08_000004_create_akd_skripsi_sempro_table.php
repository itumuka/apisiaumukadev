<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiSemproTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_sempro', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nim', 20);
            $table->integer('id_skripsi');
            $table->text('judul_proposal');
            $table->string('usulan_dosen', 20)->nullable();
            $table->text('catatan_mahasiswa')->nullable();
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->date('tanggal_sempro')->nullable();
            $table->string('nilai_sempro', 5)->nullable();
            $table->integer('submit_ke')->default(1);
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
        Schema::dropIfExists('akd_skripsi_sempro');
    }
}

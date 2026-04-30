<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiUjianTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_ujian', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nim', 20);
            $table->integer('id_skripsi');
            $table->enum('status', ['pending', 'dijadwalkan', 'lulus', 'tidak_lulus', 'revisi'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->date('tanggal_ujian')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('ruang', 50)->nullable();
            $table->string('id_penguji1', 20)->nullable();
            $table->string('id_penguji2', 20)->nullable();
            $table->string('id_penguji3', 20)->nullable();
            $table->string('nilai_ujian', 5)->nullable();
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
        Schema::dropIfExists('akd_skripsi_ujian');
    }
}

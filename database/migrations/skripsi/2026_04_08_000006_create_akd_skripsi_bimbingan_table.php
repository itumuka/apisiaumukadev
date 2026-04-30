<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiBimbinganTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_bimbingan', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nim', 20);
            $table->integer('id_skripsi');
            $table->string('id_dosen', 20);
            $table->date('tanggal');
            $table->string('topik', 255);
            $table->text('uraian')->nullable();
            $table->string('path_file', 500)->nullable();
            $table->enum('status', ['pending', 'disetujui', 'revisi'])->default('pending');
            $table->text('catatan_dosen')->nullable();
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
        Schema::dropIfExists('akd_skripsi_bimbingan');
    }
}

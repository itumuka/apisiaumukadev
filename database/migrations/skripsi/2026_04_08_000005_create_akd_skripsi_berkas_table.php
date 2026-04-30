<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiBerkasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_berkas', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nim', 20);
            $table->integer('id_skripsi');
            $table->enum('fase', ['sempro', 'ujian']);
            $table->integer('id_syarat_prodi');
            $table->string('nama_file', 255)->nullable();
            $table->string('path_file', 500)->nullable();
            $table->enum('tipe', ['file', 'url'])->default('file');
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
        Schema::dropIfExists('akd_skripsi_berkas');
    }
}

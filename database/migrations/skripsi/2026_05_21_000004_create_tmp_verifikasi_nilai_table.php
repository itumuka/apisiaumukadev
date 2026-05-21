<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmpVerifikasiNilaiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmp_verifikasi_nilai', function (Blueprint $table) {
            $table->string('nim', 20)->nullable();
            $table->integer('id_matakuliah')->nullable();
            $table->string('nama_matakuliah', 255)->nullable();
            $table->string('nilai_sumber', 5)->nullable();
            
            $table->index(['nim', 'id_matakuliah'], 'idx_nim_matkul');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tmp_verifikasi_nilai');
    }
}
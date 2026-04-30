<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiSyaratTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_syarat', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('kode_syarat', 50)->unique();
            $table->string('nama_syarat', 255);
            $table->enum('jenis', ['sistem', 'berkas', 'pembayaran']);
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
        Schema::dropIfExists('akd_skripsi_syarat');
    }
}

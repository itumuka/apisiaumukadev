<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdPkkmbTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_pkkmb', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 20)->index();
            $table->tinyInteger('status_lulus')->default(0)->comment('1=Lulus, 0=Belum Lulus');
            $table->string('tahun', 4)->nullable();
            $table->string('no_sertifikat', 100)->nullable();
            $table->string('file_sertifikat', 255)->nullable();
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
        Schema::dropIfExists('akd_pkkmb');
    }
}

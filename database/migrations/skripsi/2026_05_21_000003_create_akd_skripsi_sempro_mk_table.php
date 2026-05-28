<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiSemproMkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('akd_skripsi_sempro_mk')) {
            Schema::create('akd_skripsi_sempro_mk', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('kode_prodi', 20)->nullable()->index();
                $table->integer('id_matakuliah')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('akd_skripsi_sempro_mk');
    }
}
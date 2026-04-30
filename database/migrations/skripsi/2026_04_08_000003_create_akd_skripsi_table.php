<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nim', 20)->unique();
            $table->text('judul')->nullable();
            $table->string('id_dosen_pembimbing1', 20)->nullable();
            $table->string('id_dosen_pembimbing2', 20)->nullable();
            $table->enum('fase_aktif', ['sempro', 'bimbingan', 'ujian'])->default('sempro');
            $table->enum('status', ['draft', 'aktif', 'lulus', 'tidak_lulus'])->default('draft');
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
        Schema::dropIfExists('akd_skripsi');
    }
}

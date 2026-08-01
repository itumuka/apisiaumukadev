<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdMahasiswaVerifikasiSemesterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_mahasiswa_verifikasi_semester', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 20)->index();
            $table->year('tahun');
            $table->integer('semester');
            $table->tinyInteger('is_verified')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['nim', 'tahun', 'semester'], 'uq_nim_semester');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('akd_mahasiswa_verifikasi_semester');
    }
}

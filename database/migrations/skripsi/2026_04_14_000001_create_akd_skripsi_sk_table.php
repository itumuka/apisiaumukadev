<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiSkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_sk', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('no_sk', 100);
            $table->date('tgl_sk');
            $table->string('kode_prodi', 20)->nullable();
            $table->string('kode_fakultas', 20)->nullable();
            $table->string('tahun_akademik', 10)->nullable();
            $table->string('semester', 10)->nullable();
            $table->text('perihal')->nullable();
            $table->timestamps();
        });

        Schema::table('akd_skripsi', function (Blueprint $table) {
            $table->integer('id_sk_pembimbing')->nullable()->after('id_dosen_pembimbing2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_skripsi', function (Blueprint $table) {
            $table->dropColumn('id_sk_pembimbing');
        });
        Schema::dropIfExists('akd_skripsi_sk');
    }
}

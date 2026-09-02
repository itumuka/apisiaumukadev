<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiPerpanjanganTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('akd_skripsi_perpanjangan')) {
            Schema::create('akd_skripsi_perpanjangan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('id_skripsi')->nullable()->index();
                $table->string('nim', 15)->index();
                $table->string('tahun', 4)->index();
                $table->string('semester', 1)->index();
                $table->text('alasan_perpanjangan');
                $table->string('progress_terakhir', 100)->nullable();
                $table->date('target_selesai')->nullable();
                
                // Status Keuangan
                $table->enum('status_keuangan', ['pending', 'lunas', 'ditolak'])->default('pending');
                $table->text('catatan_keuangan')->nullable();
                $table->string('diverifikasi_oleh_keuangan', 50)->nullable();
                $table->dateTime('tgl_verifikasi_keuangan')->nullable();

                // Status Final (Langsung disetujui setelah keuangan lunas)
                $table->enum('status_final', ['diajukan', 'disetujui', 'ditolak'])->default('diajukan');
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
        Schema::dropIfExists('akd_skripsi_perpanjangan');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAkdSkripsiProposalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_proposal', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('id_skripsi');
            $table->string('nim', 20);
            $table->integer('iterasi')->default(1)->comment('Pengajuan ke-1, ke-2, dst (jika revisi)');
            $table->string('path_file_pdf', 500)->nullable()->comment('Path/URL file naskah PDF Bab 1-3');
            $table->text('catatan_mhs')->nullable();
            $table->enum('status', ['draft', 'diajukan', 'dijadwalkan', 'lulus', 'revisi', 'ditolak'])->default('draft');
            $table->text('catatan_admin')->nullable()->comment('Feedback dari Admin/Penguji');
            $table->date('tanggal_sempro')->nullable()->comment('Diisi Admin saat jadwalkan');
            $table->string('ruang', 50)->nullable();
            $table->integer('id_penguji1')->nullable()->comment('FK simpeg_pegawai.id — Penguji dari luar');
            $table->integer('id_penguji2')->nullable()->comment('FK simpeg_pegawai.id — Biasanya Dosen Pembimbing');
            $table->string('nilai_sempro', 5)->nullable();
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
        Schema::dropIfExists('akd_skripsi_proposal');
    }
}

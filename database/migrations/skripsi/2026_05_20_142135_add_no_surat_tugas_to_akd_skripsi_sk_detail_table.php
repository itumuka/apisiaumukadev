<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('akd_skripsi_sk_detail', function (Blueprint $table) {
            // Menambahkan kolom 'no_surat_tugas' setelah kolom 'id_skripsi'
            // Kolom ini dibuat nullable untuk fleksibilitas, meskipun dalam implementasi saat ini
            // seharusnya selalu terisi saat dibuat.
            $table->string('no_surat_tugas')->nullable()->after('id_skripsi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akd_skripsi_sk_detail', function (Blueprint $table) {
            // Menghapus kolom 'no_surat_tugas' jika migrasi di-rollback
            $table->dropColumn('no_surat_tugas');
        });
    }
};


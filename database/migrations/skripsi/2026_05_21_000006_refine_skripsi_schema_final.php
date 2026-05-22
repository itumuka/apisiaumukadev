<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RefineSkripsiSchemaFinal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Penyesuaian tabel akd_program_studi
        Schema::table('akd_program_studi', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_program_studi', 'updated_at')) {
                // Cek kolom referensi untuk 'after' agar tidak error jika kolom tersebut belum ada
                if (Schema::hasColumn('akd_program_studi', 'ta_sempro_is_validated')) {
                    $table->dateTime('updated_at')->nullable()->after('ta_sempro_is_validated');
                } else {
                    // Fallback jika migrasi 000002 belum jalan
                    $table->dateTime('updated_at')->nullable();
                }
            }
        });

        // Menggunakan raw SQL untuk memastikan tipe enum dan comment sesuai dengan permintaan SQL
        DB::statement("ALTER TABLE akd_program_studi MODIFY COLUMN ta_sempro_skema ENUM('skripsi', 'matakuliah') NULL DEFAULT 'skripsi' AFTER ta_ada_sempro");
        DB::statement("ALTER TABLE akd_program_studi MODIFY COLUMN ta_sempro_is_validated TINYINT NOT NULL DEFAULT 1 COMMENT '1=Validated/Approved, 0=Pending/Draft' AFTER ta_nama_tugas_akhir");

        // 2. Menghapus kolom flag kelulusan lama di tabel akd_skripsi (karena sekarang dicek dinamis via transkrip/tabel khusus)
        Schema::table('akd_skripsi', function (Blueprint $table) {
            if (Schema::hasColumn('akd_skripsi', 'is_pkkmb')) {
                $table->dropColumn('is_pkkmb');
            }
            if (Schema::hasColumn('akd_skripsi', 'is_kkn')) {
                $table->dropColumn('is_kkn');
            }
        });

        // 3. Menghapus kolom flag kelulusan di tabel akd_skripsi_ujian
        Schema::table('akd_skripsi_ujian', function (Blueprint $table) {
            if (Schema::hasColumn('akd_skripsi_ujian', 'is_pkkmb')) {
                $table->dropColumn('is_pkkmb');
            }
            if (Schema::hasColumn('akd_skripsi_ujian', 'is_kkn')) {
                $table->dropColumn('is_kkn');
            }
            if (Schema::hasColumn('akd_skripsi_ujian', 'is_pkpm')) {
                $table->dropColumn('is_pkpm');
            }
        });

        // 4. Memastikan tabel akd_skripsi_sempro_mk ada (jika migrasi sebelumnya belum dijalankan)
        if (!Schema::hasTable('akd_skripsi_sempro_mk')) {
            Schema::create('akd_skripsi_sempro_mk', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('kode_prodi', 20)->nullable()->index('kode_prodi');
                $table->integer('id_matakuliah')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }
    }

    public function down()
    {
        // Rollback biasanya tidak disarankan untuk migrasi pembersihan skema seperti ini kecuali diperlukan.
    }
}

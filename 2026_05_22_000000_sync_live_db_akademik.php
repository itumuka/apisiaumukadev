<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. UPDATE TABEL: akd_program_studi (Menambahkan Kolom TA)
        if (Schema::hasTable('akd_program_studi')) {
            Schema::table('akd_program_studi', function (Blueprint $table) {
                if (!Schema::hasColumn('akd_program_studi', 'ta_sks_minimal')) {
                    $table->smallInteger('ta_sks_minimal')->default(138)->comment('Minimal SKS untuk masuk modul TA');
                }
                if (!Schema::hasColumn('akd_program_studi', 'ta_ada_sempro')) {
                    $table->tinyInteger('ta_ada_sempro')->default(1)->comment('1=Wajib Sempro, 0=Langsung Bimbingan');
                }
                if (!Schema::hasColumn('akd_program_studi', 'ta_sempro_skema')) {
                    $table->enum('ta_sempro_skema', ['skripsi', 'matakuliah'])->nullable()->default('skripsi');
                }
                if (!Schema::hasColumn('akd_program_studi', 'ta_minimal_bimbingan')) {
                    $table->smallInteger('ta_minimal_bimbingan')->default(8)->comment('Minimal jumlah log bimbingan yang disetujui');
                }
                if (!Schema::hasColumn('akd_program_studi', 'ta_komponen_bayar')) {
                    $table->string('ta_komponen_bayar', 100)->default('Bimbingan Skripsi')->comment('Nama komponen di keu_tagihan_mhs');
                }
                if (!Schema::hasColumn('akd_program_studi', 'ta_komponen_bayar_ujian')) {
                    $table->string('ta_komponen_bayar_ujian', 100)->default('Ujian Skripsi')->comment('Nama komponen ujian di keu_tagihan_mhs');
                }
                if (!Schema::hasColumn('akd_program_studi', 'ta_nama_tugas_akhir')) {
                    $table->string('ta_nama_tugas_akhir', 50)->default('Skripsi')->comment('Label TA di UI, cth: Skripsi / Tugas Akhir');
                }
                if (!Schema::hasColumn('akd_program_studi', 'ta_sempro_is_validated')) {
                    $table->tinyInteger('ta_sempro_is_validated')->default(1)->comment('1=Validated/Approved, 0=Pending/Draft');
                }
                if (!Schema::hasColumn('akd_program_studi', 'updated_at')) {
                    $table->dateTime('updated_at')->nullable()->after('ta_sempro_is_validated');
                }
            });
        }

        // 2. ADMINISTRASI SYARAT
        if (!Schema::hasTable('akd_skripsi_syarat')) {
            Schema::create('akd_skripsi_syarat', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('kode_syarat', 50)->unique();
                $table->string('nama_syarat', 255);
                $table->enum('jenis', ['sistem', 'berkas', 'pembayaran']);
                $table->tinyInteger('is_aktif')->default(1);
            });
        }

        if (!Schema::hasTable('akd_skripsi_syarat_prodi')) {
            Schema::create('akd_skripsi_syarat_prodi', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('kode_prodi', 20);
                $table->enum('kode_jenjang', ['S1', 'D4', 'D3']);
                $table->enum('fase', ['sempro', 'ujian']);
                $table->string('kode_syarat', 50);
                $table->enum('operator', ['>=', '<=', '=', 'EXISTS', '-'])->default('>=');
                $table->string('nilai_target', 100)->nullable();
                $table->string('petugas_validasi', 100)->default('Petugas Fakultas');
                $table->enum('tipe_upload', ['file', 'url', 'bebas'])->nullable();
                $table->text('keterangan')->nullable();
                $table->integer('urutan')->default(0);
                $table->tinyInteger('is_wajib')->default(1);
                $table->tinyInteger('is_aktif')->default(1);
            });
        }

        // 3. CREATE TABEL: akd_pkkmb
        if (!Schema::hasTable('akd_pkkmb')) {
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

        // 4. CREATE TABEL: akd_skripsi (Tabel Induk Skripsi)
        if (!Schema::hasTable('akd_skripsi')) {
            Schema::create('akd_skripsi', function (Blueprint $table) {
                $table->increments('id'); 
                $table->string('nim', 20)->unique();
                $table->string('topik', 255)->nullable();
                $table->string('topik_en', 255)->nullable();
                $table->text('judul')->nullable();
                $table->text('judul_en')->nullable();
                $table->text('abstrak')->nullable();
                $table->text('abstrak_en')->nullable();
                $table->date('tanggal_pengajuan')->nullable();
                $table->integer('id_dosen_pembimbing1')->nullable();
                $table->integer('id_dosen_pembimbing2')->nullable();
                $table->integer('id_sk_pembimbing')->nullable();
                $table->enum('fase_aktif', ['proposal','sempro','bimbingan','ujian'])->nullable()->default('proposal');
                $table->enum('status', ['draft','menunggu_pembimbing','aktif','lulus','tidak_lulus'])->nullable()->default('draft');
                $table->string('target_luaran', 50)->nullable()->default('buku_skripsi');
                $table->timestamps();
            });
        } else {
            // Pastikan kolom flag lama yang sudah tidak dipakai dihapus jika tabel sudah ada
            Schema::table('akd_skripsi', function (Blueprint $table) {
                if (Schema::hasColumn('akd_skripsi', 'is_pkpm')) {
                    $table->dropColumn('is_pkpm');
                }
            });
        }

        // 5. CREATE TABEL: akd_skripsi_berkas
        if (!Schema::hasTable('akd_skripsi_berkas')) {
            Schema::create('akd_skripsi_berkas', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nim', 20);
                $table->integer('id_skripsi');
                $table->enum('fase', ['sempro','ujian']);
                $table->integer('id_syarat_prodi');
                $table->string('nama_file', 255)->nullable();
                $table->string('path_file', 500)->nullable();
                $table->enum('tipe', ['file','url'])->default('file');
                $table->timestamps();
            });
        }

        // 6. CREATE TABEL: akd_skripsi_bimbingan
        if (!Schema::hasTable('akd_skripsi_bimbingan')) {
            Schema::create('akd_skripsi_bimbingan', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nim', 20);
                $table->integer('id_skripsi');
                $table->string('id_dosen', 20);
                $table->date('tanggal');
                $table->string('topik', 255);
                $table->text('uraian')->nullable();
                $table->string('path_file', 500)->nullable();
                $table->enum('status', ['pending','disetujui','revisi'])->default('pending');
                $table->text('catatan_dosen')->nullable();
                $table->timestamps();
            });
        }

        // 7. SURAT KEPUTUSAN (SK)
        if (!Schema::hasTable('akd_skripsi_sk')) {
            Schema::create('akd_skripsi_sk', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('no_sk', 100);
                $table->string('no_surat_tugas', 100)->nullable();
                $table->date('tgl_sk');
                $table->string('kode_prodi', 20)->nullable();
                $table->string('kode_fakultas', 20)->nullable();
                $table->string('tahun_akademik', 10)->nullable();
                $table->string('semester', 10)->nullable();
                $table->text('perihal')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('akd_skripsi_sk_detail')) {
            Schema::create('akd_skripsi_sk_detail', function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('id_sk')->index();
                $table->integer('id_skripsi')->index();
                $table->string('no_surat_tugas')->nullable();
                $table->timestamps();
            });
        }

        // 8. CREATE TABEL: akd_skripsi_luaran
        if (!Schema::hasTable('akd_skripsi_luaran')) {
            Schema::create('akd_skripsi_luaran', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_skripsi');
                $table->string('nim', 20);
                $table->string('jenis_luaran', 100)->nullable();
                $table->string('judul_luaran', 255)->nullable();
                $table->string('nama_media', 255)->nullable();
                $table->string('url_link', 500)->nullable();
                $table->string('file_bukti', 255)->nullable();
                $table->enum('status_validasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        // 9. CREATE TABEL: akd_skripsi_proposal
        if (!Schema::hasTable('akd_skripsi_proposal')) {
            Schema::create('akd_skripsi_proposal', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_skripsi');
                $table->string('nim', 20);
                $table->integer('iterasi')->default(1);
                $table->string('path_file_pdf', 500)->nullable();
                $table->text('catatan_mhs')->nullable();
                $table->enum('status', ['draft', 'diajukan', 'dijadwalkan', 'lulus', 'revisi', 'ditolak'])->default('draft');
                $table->text('catatan_admin')->nullable();
                $table->date('tanggal_sempro')->nullable();
                $table->string('ruang', 100)->nullable();
                $table->integer('id_penguji1')->nullable();
                $table->integer('id_penguji2')->nullable();
                $table->string('nilai_sempro', 5)->nullable();
                $table->timestamps();
            });
        }
        
        // 10. CREATE TABEL: akd_skripsi_ujian
        if (!Schema::hasTable('akd_skripsi_ujian')) {
            Schema::create('akd_skripsi_ujian', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_skripsi');
                $table->string('nim', 20);
                $table->integer('id_proposal')->nullable();
                $table->text('catatan_mhs')->nullable();
                $table->date('tanggal_ujian')->nullable();
                $table->string('ruang', 100)->nullable();
                $table->string('id_penguji1', 20)->nullable();
                $table->string('id_penguji2', 20)->nullable();
                $table->string('id_penguji3', 20)->nullable();
                $table->decimal('nilai_angka', 5, 2)->nullable();
                $table->string('nilai_ujian', 5)->nullable();
                $table->enum('status', ['pending','diajukan','dijadwalkan','dinilai','menunggu_penetapan','ditetapkan','lulus','tidak_lulus','revisi'])->default('pending');
                $table->timestamps();
            });
        }

        // 11. CREATE TABEL: akd_skripsi_berita_acara
        if (!Schema::hasTable('akd_skripsi_berita_acara')) {
            Schema::create('akd_skripsi_berita_acara', function (Blueprint $table) {
                $table->id();
                $table->integer('id_skripsi_ujian')->index('idx_id_skripsi_ujian');
                $table->string('nim', 20)->index('idx_nim');
                $table->decimal('nilai_angka', 5, 2)->nullable();
                $table->string('nilai_huruf', 5)->nullable();
                $table->text('catatan')->nullable();
                $table->string('id_penguji1', 20)->nullable();
                $table->timestamp('setuju_penguji1')->nullable();
                $table->string('id_penguji2', 20)->nullable();
                $table->timestamp('setuju_penguji2')->nullable();
                $table->string('id_penguji3', 20)->nullable();
                $table->timestamp('setuju_penguji3')->nullable();
                $table->enum('status', ['draft','menunggu_ttd','selesai'])->default('draft');
                $table->timestamps();
            });
        }

        // 12. CREATE TABEL: akd_yudisium_periode
        if (!Schema::hasTable('akd_yudisium_periode')) {
            Schema::create('akd_yudisium_periode', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_periode', 100);
                $table->date('tanggal_buka')->nullable();
                $table->date('tanggal_tutup')->nullable();
                $table->date('tanggal_yudisium')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->timestamps();
            });
        }

        // 13. CREATE TABEL: akd_yudisium_peserta
        if (!Schema::hasTable('akd_yudisium_peserta')) {
            Schema::create('akd_yudisium_peserta', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('id_periode');
                $table->string('nim', 20);
                $table->enum('status_daftar', ['diajukan','diverifikasi','disetujui','ditolak'])->default('diajukan');
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }

        // 14. TABEL OBE & SEMPRO MK
        if (!Schema::hasTable('akd_skripsi_rubrik_cpmk')) {
            Schema::create('akd_skripsi_rubrik_cpmk', function (Blueprint $table) {
                $table->id();
                $table->string('kode_cpmk', 50);
                $table->string('nama_cpmk', 255);
                $table->decimal('bobot', 5, 2);
                $table->string('kode_prodi', 20)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('akd_skripsi_cpmk_cpl')) {
            Schema::create('akd_skripsi_cpmk_cpl', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_cpmk');
                $table->string('kode_cpl', 50);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('akd_skripsi_nilai_cpmk')) {
            Schema::create('akd_skripsi_nilai_cpmk', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_skripsi_ujian');
                $table->string('id_dosen', 20);
                $table->unsignedBigInteger('id_cpmk');
                $table->decimal('nilai', 5, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('akd_skripsi_sempro_mk')) {
            Schema::create('akd_skripsi_sempro_mk', function (Blueprint $table) {
                $table->increments('id');
                $table->string('kode_prodi', 20)->nullable()->index('kode_prodi');
                $table->integer('id_matakuliah')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }

        // 15. CLEANUP / DROP TABLES
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('tmp_verifikasi_nilai');
        Schema::dropIfExists('users');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        Schema::dropIfExists('akd_skripsi_sempro_mk');
        Schema::dropIfExists('akd_skripsi_nilai_cpmk');
        Schema::dropIfExists('akd_skripsi_cpmk_cpl');
        Schema::dropIfExists('akd_skripsi_rubrik_cpmk');

        // Drop tabel secara aman saat di-rollback
        Schema::dropIfExists('akd_yudisium_peserta');
        Schema::dropIfExists('akd_yudisium_periode');
        Schema::dropIfExists('akd_skripsi_berita_acara');
        Schema::dropIfExists('akd_skripsi_ujian');
        Schema::dropIfExists('akd_skripsi_proposal');
        Schema::dropIfExists('akd_skripsi_luaran');
        Schema::dropIfExists('akd_skripsi_sk_detail');
        Schema::dropIfExists('akd_skripsi_sk');
        Schema::dropIfExists('akd_skripsi_bimbingan');
        Schema::dropIfExists('akd_skripsi_berkas');
        Schema::dropIfExists('akd_skripsi');
        Schema::dropIfExists('akd_pkkmb');
        Schema::dropIfExists('akd_skripsi_syarat_prodi');
        Schema::dropIfExists('akd_skripsi_syarat');
        
        // Hapus kolom yang ditambahkan jika tabel akd_program_studi ada
        if (Schema::hasTable('akd_program_studi')) {
            Schema::table('akd_program_studi', function (Blueprint $table) {
                $columns = [
                    'ta_sks_minimal', 'ta_ada_sempro', 'ta_sempro_skema', 
                    'ta_minimal_bimbingan', 'ta_komponen_bayar', 'ta_komponen_bayar_ujian', 
                    'ta_nama_tugas_akhir', 'ta_sempro_is_validated'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('akd_program_studi', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

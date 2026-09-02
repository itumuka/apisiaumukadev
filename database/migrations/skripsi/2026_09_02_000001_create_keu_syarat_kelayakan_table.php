<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateKeuSyaratKelayakanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('keu_syarat_kelayakan')) {
            Schema::create('keu_syarat_kelayakan', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('kegiatan', 50)->index(); // perpanjangan_studi, pendaftaran_wisuda, dll.
                $table->string('jenjang', 10)->nullable()->index(); // D3, S1, S2, or null (all)
                $table->string('kode_prodi', 10)->nullable()->index(); // PT10, IF02, etc. or null (all)
                $table->string('angkatan', 4)->nullable()->index(); // 2022, 2023, etc. or null (all)
                $table->string('kode_komponen', 10)->index(); // 01 (SPP), 05 (Herreg), 03 (Wisuda), 21 (Toga), etc.
                $table->string('nama_komponen_label', 100);
                $table->integer('jumlah_bulan')->default(0); // 6 untuk SPP 1 semester
                $table->tinyInteger('is_wajib')->default(1);
                $table->string('keterangan', 255)->nullable();
                $table->tinyInteger('is_aktif')->default(1);
                $table->timestamps();
            });

            // Seed default data for perpanjangan_studi and pendaftaran_wisuda
            DB::table('keu_syarat_kelayakan')->insert([
                // 1. Perpanjangan Studi D3 Angkatan 2023 (Herregistrasi + SPP 1 Semester)
                [
                    'kegiatan' => 'perpanjangan_studi',
                    'jenjang' => 'D3',
                    'kode_prodi' => null,
                    'angkatan' => '2023',
                    'kode_komponen' => '05',
                    'nama_komponen_label' => 'Herregistrasi Semester Perpanjangan',
                    'jumlah_bulan' => 0,
                    'is_wajib' => 1,
                    'keterangan' => 'Wajib herregistrasi untuk mahasiswa D3 Angkatan 2023',
                    'is_aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'kegiatan' => 'perpanjangan_studi',
                    'jenjang' => 'D3',
                    'kode_prodi' => null,
                    'angkatan' => '2023',
                    'kode_komponen' => '01',
                    'nama_komponen_label' => 'SPP 1 Semester (6 Bulan)',
                    'jumlah_bulan' => 6,
                    'is_wajib' => 1,
                    'keterangan' => 'Wajib lunas SPP 1 semester (6 bulan) untuk mahasiswa D3 Angkatan 2023',
                    'is_aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],

                // 2. Perpanjangan Studi S1 Angkatan 2022 (Hanya SPP 1 Semester)
                [
                    'kegiatan' => 'perpanjangan_studi',
                    'jenjang' => 'S1',
                    'kode_prodi' => null,
                    'angkatan' => '2022',
                    'kode_komponen' => '01',
                    'nama_komponen_label' => 'SPP 1 Semester (6 Bulan)',
                    'jumlah_bulan' => 6,
                    'is_wajib' => 1,
                    'keterangan' => 'Wajib lunas SPP 1 semester (6 bulan) untuk mahasiswa S1 Angkatan 2022',
                    'is_aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],

                // 3. Pendaftaran Wisuda (General / All Jenjang & Angkatan)
                [
                    'kegiatan' => 'pendaftaran_wisuda',
                    'jenjang' => null,
                    'kode_prodi' => null,
                    'angkatan' => null,
                    'kode_komponen' => '03',
                    'nama_komponen_label' => 'Pelunasan Biaya Wisuda',
                    'jumlah_bulan' => 0,
                    'is_wajib' => 1,
                    'keterangan' => 'Tagihan biaya pelaksanaan wisuda',
                    'is_aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'kegiatan' => 'pendaftaran_wisuda',
                    'jenjang' => null,
                    'kode_prodi' => null,
                    'angkatan' => null,
                    'kode_komponen' => '21',
                    'nama_komponen_label' => 'Jaminan Toga Wisuda',
                    'jumlah_bulan' => 0,
                    'is_wajib' => 1,
                    'keterangan' => 'Biaya jaminan peminjaman toga wisuda',
                    'is_aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('keu_syarat_kelayakan');
    }
}

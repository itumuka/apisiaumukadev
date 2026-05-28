<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAkdSkripsiObeTables extends Migration
{
    public function up()
    {
        // 1. akd_skripsi_rubrik_cpmk
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

        // 2. akd_skripsi_cpmk_cpl
        if (!Schema::hasTable('akd_skripsi_cpmk_cpl')) {
            Schema::create('akd_skripsi_cpmk_cpl', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_cpmk');
                $table->string('kode_cpl', 50);
                $table->timestamps();
            });
        }

        // 3. akd_skripsi_nilai_cpmk
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

        // 4. Alter akd_skripsi_ujian to add nilai_angka
        if (Schema::hasTable('akd_skripsi_ujian')) {
            Schema::table('akd_skripsi_ujian', function (Blueprint $table) {
                if (!Schema::hasColumn('akd_skripsi_ujian', 'nilai_angka')) {
                    $table->decimal('nilai_angka', 5, 2)->nullable()->after('nilai_ujian');
                }
            });
        }

        // Seed default rubrics
        $this->seedDefaults();
    }

    public function down()
    {
        Schema::dropIfExists('akd_skripsi_nilai_cpmk');
        Schema::dropIfExists('akd_skripsi_cpmk_cpl');
        Schema::dropIfExists('akd_skripsi_rubrik_cpmk');

        if (Schema::hasTable('akd_skripsi_ujian')) {
            Schema::table('akd_skripsi_ujian', function (Blueprint $table) {
                if (Schema::hasColumn('akd_skripsi_ujian', 'nilai_angka')) {
                    $table->dropColumn('nilai_angka');
                }
            });
        }
    }

    private function seedDefaults()
    {
        $now = now();
        // Default CPMK for all prodis (null prodi code)
        $cpmks = [
            [
                'kode_cpmk' => 'CPMK1',
                'nama_cpmk' => 'Pendahuluan, Perumusan Masalah, dan Tinjauan Pustaka',
                'bobot' => 20.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_cpmk' => 'CPMK2',
                'nama_cpmk' => 'Metodologi Penelitian dan Desain Sistem',
                'bobot' => 30.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_cpmk' => 'CPMK3',
                'nama_cpmk' => 'Implementasi, Pengujian, dan Analisis Hasil',
                'bobot' => 30.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_cpmk' => 'CPMK4',
                'nama_cpmk' => 'Sikap, Kemampuan Presentasi, dan Tanya Jawab',
                'bobot' => 20.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        foreach ($cpmks as $c) {
            $id = DB::table('akd_skripsi_rubrik_cpmk')->insertGetId($c);

            // Map each CPMK to a CPL
            if ($c['kode_cpmk'] == 'CPMK1') {
                DB::table('akd_skripsi_cpmk_cpl')->insert([
                    'id_cpmk' => $id,
                    'kode_cpl' => 'CPL1 (Pengetahuan)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif ($c['kode_cpmk'] == 'CPMK2') {
                DB::table('akd_skripsi_cpmk_cpl')->insert([
                    'id_cpmk' => $id,
                    'kode_cpl' => 'CPL2 (Keterampilan Kerja)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif ($c['kode_cpmk'] == 'CPMK3') {
                DB::table('akd_skripsi_cpmk_cpl')->insert([
                    'id_cpmk' => $id,
                    'kode_cpl' => 'CPL3 (Analisis & Evaluasi)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif ($c['kode_cpmk'] == 'CPMK4') {
                DB::table('akd_skripsi_cpmk_cpl')->insert([
                    'id_cpmk' => $id,
                    'kode_cpl' => 'CPL4 (Sikap & Komunikasi)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

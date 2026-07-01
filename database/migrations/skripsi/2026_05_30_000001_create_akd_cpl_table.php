<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAkdCplTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('akd_cpl')) {
            Schema::create('akd_cpl', function (Blueprint $table) {
                $table->id();
                $table->string('kode_prodi', 20)->nullable();
                $table->string('kode_kategori', 100);
                $table->string('kode_cpl', 50);
                $table->text('deskripsi');
                $table->string('tahun_kurikulum', 10);
                $table->string('level', 50)->default('Program Studi');
                $table->boolean('is_aktif')->default(true);
                $table->timestamps();

                $table->unique(['kode_prodi', 'kode_cpl', 'tahun_kurikulum'], 'uniq_prodi_cpl_tahun');
            });
        }

        // Seed default CPLs based on existing mappings and standard values
        $now = now();
        
        $defaults = [
            [
                'kode_kategori' => 'Pengetahuan',
                'kode_cpl' => 'CPL1 (Pengetahuan)',
                'deskripsi' => 'Menguasai konsep teoritis bidang pengetahuan secara umum dan mendalam.',
                'tahun_kurikulum' => '2024',
                'level' => 'Program Studi'
            ],
            [
                'kode_kategori' => 'Keterampilan Kerja',
                'kode_cpl' => 'CPL2 (Keterampilan Kerja)',
                'deskripsi' => 'Mampu menerapkan pemikiran logis, kritis, sistematis, dan inovatif.',
                'tahun_kurikulum' => '2024',
                'level' => 'Program Studi'
            ],
            [
                'kode_kategori' => 'Analisis & Evaluasi',
                'kode_cpl' => 'CPL3 (Analisis & Evaluasi)',
                'deskripsi' => 'Mampu mengambil keputusan secara tepat dalam konteks penyelesaian masalah.',
                'tahun_kurikulum' => '2024',
                'level' => 'Program Studi'
            ],
            [
                'kode_kategori' => 'Sikap & Komunikasi',
                'kode_cpl' => 'CPL4 (Sikap & Komunikasi)',
                'deskripsi' => 'Menunjukkan sikap bertanggungjawab atas pekerjaan di bidang keahliannya secara mandiri.',
                'tahun_kurikulum' => '2024',
                'level' => 'Program Studi'
            ],
            [
                'kode_kategori' => 'Sikap 1',
                'kode_cpl' => 'S1',
                'deskripsi' => 'Bertakwa kepada Tuhan Yang Maha Esa dan mampu menunjukkan sikap religius.',
                'tahun_kurikulum' => '2024',
                'level' => 'Program Studi'
            ],
            [
                'kode_kategori' => 'Sikap 2',
                'kode_cpl' => 'S2',
                'deskripsi' => 'Menjunjung tinggi nilai kemanusiaan dalam menjalankan tugas berdasarkan agama, moral dan etika.',
                'tahun_kurikulum' => '2024',
                'level' => 'Program Studi'
            ]
        ];

        // Seed defaults for prodis that currently have rubrics
        $prodis = DB::table('akd_skripsi_rubrik_cpmk')
            ->whereNotNull('kode_prodi')
            ->distinct()
            ->pluck('kode_prodi')
            ->toArray();

        // Also add null (global defaults)
        $prodis[] = null;

        foreach ($prodis as $prodi) {
            foreach ($defaults as $d) {
                $exists = DB::table('akd_cpl')
                    ->where('kode_prodi', $prodi)
                    ->where('kode_cpl', $d['kode_cpl'])
                    ->where('tahun_kurikulum', $d['tahun_kurikulum'])
                    ->exists();

                if (!$exists) {
                    DB::table('akd_cpl')->insert([
                        'kode_prodi' => $prodi,
                        'kode_kategori' => $d['kode_kategori'],
                        'kode_cpl' => $d['kode_cpl'],
                        'deskripsi' => $d['deskripsi'],
                        'tahun_kurikulum' => $d['tahun_kurikulum'],
                        'level' => $d['level'],
                        'is_aktif' => true,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            }
        }

        // 2. Import other custom CPL codes currently mapped in CPMK-CPL table
        if (Schema::hasTable('akd_skripsi_cpmk_cpl') && Schema::hasTable('akd_skripsi_rubrik_cpmk')) {
            $mappedCpls = DB::table('akd_skripsi_cpmk_cpl as cc')
                ->join('akd_skripsi_rubrik_cpmk as r', 'cc.id_cpmk', '=', 'r.id')
                ->select('r.kode_prodi', 'cc.kode_cpl')
                ->distinct()
                ->get();

            foreach ($mappedCpls as $mc) {
                $exists = DB::table('akd_cpl')
                    ->where('kode_prodi', $mc->kode_prodi)
                    ->where('kode_cpl', $mc->kode_cpl)
                    ->exists();

                if (!$exists) {
                    $cat = 'Lainnya';
                    if (stripos($mc->kode_cpl, 'Sikap') !== false || stripos($mc->kode_cpl, 'S') === 0) {
                        $cat = 'Sikap';
                    } elseif (stripos($mc->kode_cpl, 'Pengetahuan') !== false || stripos($mc->kode_cpl, 'P') === 0) {
                        $cat = 'Pengetahuan';
                    } elseif (stripos($mc->kode_cpl, 'Keterampilan') !== false || stripos($mc->kode_cpl, 'K') === 0) {
                        $cat = 'Keterampilan';
                    }

                    DB::table('akd_cpl')->insert([
                        'kode_prodi' => $mc->kode_prodi,
                        'kode_kategori' => $cat,
                        'kode_cpl' => $mc->kode_cpl,
                        'deskripsi' => 'Capaian Pembelajaran Lulusan untuk ' . $mc->kode_cpl,
                        'tahun_kurikulum' => '2024',
                        'level' => 'Program Studi',
                        'is_aktif' => true,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('akd_cpl');
    }
}

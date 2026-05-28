<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AkdSkripsiProdiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function up()
    {
        $prodis = DB::table('akd_program_studi')->get();

        foreach ($prodis as $prodi) {
            $is_d3 = strpos($prodi->nama_program_studi, 'D3') !== false || $prodi->kode_jenjang_pendidikan == 'D3'; // Assuming kode_jenjang might be string or ID
            
            // Logic based on User Request Q2
            if ($is_d3) {
                DB::table('akd_program_studi')->where('id_program_studi', $prodi->id_program_studi)->update([
                    'ta_sks_minimal' => 108,
                    'ta_ada_sempro' => 0,
                    'ta_minimal_bimbingan' => 6,
                    'ta_komponen_bayar' => 'Tugas Akhir',
                    'ta_komponen_bayar_ujian' => 'Tugas Akhir',
                    'ta_nama_tugas_akhir' => 'Tugas Akhir',
                    'ta_is_obe' => 0
                ]);
            } else {
                // S1 / D4 / Others
                DB::table('akd_program_studi')->where('id_program_studi', $prodi->id_program_studi)->update([
                    'ta_sks_minimal' => 138,
                    'ta_ada_sempro' => 1,
                    'ta_minimal_bimbingan' => 8,
                    'ta_komponen_bayar' => 'Bimbingan Skripsi',
                    'ta_komponen_bayar_ujian' => 'Ujian Skripsi',
                    'ta_nama_tugas_akhir' => 'Skripsi',
                    'ta_is_obe' => 1
                ]);
            }
        }
    }

    /**
     * Backward compatibility if running from artisan db:seed
     */
    public function run() {
        $this->up();
    }
}

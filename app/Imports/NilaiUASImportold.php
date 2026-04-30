<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class NilaiUASImport implements ToCollection, WithStartRow
{

    // private $table;
    // public function __construct($table)
    // {
    //     $this->table = $table;
    // }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        $nilaimutu = [];
        foreach ($collection as $row) {

            // $nilai_akhir_huruf = $row[3];

            // $nilai = collect(DB::select("SELECT mutu FROM akd_predikat_nilai_huruf WHERE nilai_huruf_akhir = '" . $row[3] . "'"))->first();
            // $nilaimutu = $nilai->mutu;
            // dd($nilaimutu);
            DB::table('akd_detail_krs')
                ->where('id_detail_krs', $row[11])
                ->update([
                    'nilai_akhir_huruf'  =>  $row[10],
                    'nilai_akhir_angka'  =>  $row[9],
                    'nilai_uas'  =>  $row[8],
                    'nilai_uts'  =>  $row[7],
                    'nilai_kuis'  =>  $row[6],
                    'nilai_praktek'  =>  $row[5],
                    'nilai_tugas'  =>  $row[4],
                    'kehadiran'  =>  $row[3],
                    'dtime_update'  =>  date('Y-m-d H:i:s')
                ]);


            // //transkrip
            $cek_nilai = collect(DB::select("SELECT id_transkrip, id_matakuliah, tahun_kurikulum from akd_transkrip where nim='" . $row[0] . "' and id_matakuliah='" . $row[12] . "' and tahun_kurikulum='" . $row[13] . "'"))->count();

            if ($cek_nilai > 0) {
                DB::table('akd_transkrip')
                    ->where('nim', $row[0])
                    ->where('id_matakuliah', $row[5])
                    ->where('tahun_kurikulum', $row[6])
                    ->update([
                        'nilai'  =>  $row[10]
                    ]);
            } else {
                DB::table('akd_transkrip')->insert([
                    'nim'  =>  $row[0],
                    'id_matakuliah'  =>  $row[12],
                    'tahun_kurikulum'  =>  $row[13],
                    'nilai'  =>  $row[10]
                ]);
            }
        }
        // var_dump($nilaimutu);
    }

    public function startRow(): int
    {
        return 8;
    }
}

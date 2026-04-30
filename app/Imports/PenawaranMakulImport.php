<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenawaranMakulImport implements ToCollection, WithStartRow
{
    public $failures = [];

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            // dd($row[3]);
            try {

                $matkul = DB::table('akd_matakuliah')->where('id_matakuliah', '=', $row[2])->first();

                if ($row[5] == null) {
                    $getID = DB::table('akd_penawaran_matakuliah')->insertGetId([
                        'tahun'  =>  $row[0],
                        'semester'  =>  $row[1],
                        'id_matakuliah'  =>  $row[2],
                        'tahun_kurikulum'  =>  $matkul->tahun_kurikulum,
                        'sks_matakuliah'  =>  $matkul->sks_matakuliah,
                        'smt_matakuliah'  =>  $matkul->smt_matakuliah,
                        'kode_program_studi'  => $row[3],
                        'kode_dosen'  =>  $row[4]
                    ]);

                    DB::table('akd_kelas_kuliah')->insert([
                        'id_tawar'  =>  $getID,
                        'nama_kelas'  =>  $row[6],
                        'hari'  =>  $row[7],
                        'jam_mulai'  =>  $row[8],
                        'jam_selesai'  =>  $row[9],
                        'kode_ruang'  =>  $row[10],
                        'kapasitas_ruang'  =>  $row[11],
                        'kode_dosen'  => $row[4]
                    ]);
                } else {
                    $getID = DB::table('akd_penawaran_matakuliah')->insertGetId([
                        'tahun'  =>  $row[0],
                        'semester'  =>  $row[1],
                        'id_matakuliah'  =>  $row[2],
                        'tahun_kurikulum'  =>  $matkul->tahun_kurikulum,
                        'sks_matakuliah'  =>  $matkul->sks_matakuliah,
                        'smt_matakuliah'  =>  $matkul->smt_matakuliah,
                        'kode_program_studi'  => $row[3],
                        'kode_dosen'  =>  $row[4],
                        'kode_dosen2'  =>  $row[5]
                    ]);

                    DB::table('akd_kelas_kuliah')->insert([
                        'id_tawar'  =>  $getID,
                        'nama_kelas'  =>  $row[6],
                        'hari'  =>  $row[7],
                        'jam_mulai'  =>  $row[8],
                        'jam_selesai'  =>  $row[9],
                        'kode_ruang'  =>  $row[10],
                        'kapasitas_ruang'  =>  $row[11],
                        'kode_dosen'  => $row[4],
                        'kode_dosen2'  => $row[5]
                    ]);
                }
            } catch (\Exception $e) {
                // Capture the error and store it with row data
                $this->failures[] = [
                    'row' => $row,
                    'error' => $e->getMessage()
                ];
                // Log the error
                Log::error('Import error: ' . $e->getMessage());
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }

    public function getFailures()
    {
        return $this->failures;
    }
}

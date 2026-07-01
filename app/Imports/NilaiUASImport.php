<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class NilaiUASImport implements ToCollection, WithStartRow
{
    private $dataFromWorksheet = [];
    private $errors = []; // Untuk menyimpan pesan error
    private $persentase = []; // Untuk menyimpan nilai persentase dari sel D8-G8

    public function __construct($dataFromWorksheet, $persentase)
    {
        $this->dataFromWorksheet = $dataFromWorksheet;
        $this->persentase = $persentase;
    }

    public function collection(Collection $collection)
    {
        $validGrades = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'E', 'AB', 'BC', 'CD', 'DE'];

        foreach ($collection as $key => $row) {
            $rowNumber = $key + 9; // Baris aktual (karena startRow dimulai dari 8)

            // Ambil data terkait nilai dari data worksheet yang sudah diekstraksi
            $nilaiAkhirAngka = $this->dataFromWorksheet[$key]['nilaiAkhirAngka'];
            $nilaiAkhirHuruf = $this->dataFromWorksheet[$key]['nilaiAkhirHuruf'];
            
            // Validasi nilai 0-100 untuk kolom kehadiran sampai nilai UAS ($row[3] hingga $row[9])
            for ($i = 3; $i <= 5; $i++) {
                if (!is_numeric($row[$i]) || $row[$i] < 0 || $row[$i] > 100) {
                    $this->errors[] = "Baris {$rowNumber}: Nilai pada kolom " . ($i + 1) . " (nilai angka) harus berupa angka 0-100.";
                    continue 2; // Lewati baris ini jika ada error
                }
            }

            DB::table('akd_detail_krs')
                ->where('id_detail_krs', $row[11])
                ->update([
                    'nilai_akhir_huruf'  =>  $nilaiAkhirHuruf,
                    'nilai_akhir_angka'  =>  $nilaiAkhirAngka,
                    'nilai_kuis'  =>  $row[8],
                    'nilai_praktek'  =>  $row[7],
                    'nilai_tugas'  =>  $row[6],
                    'nilai_uas'  =>  $row[5],
                    'nilai_uts'  =>  $row[4],
                    'kehadiran'  =>  $row[3],
                    'persen_kehadiran' => $this->persentase['kehadiran'],
                    'persen_uts' => $this->persentase['uts'],
                    'persen_uas' => $this->persentase['uas'],
                    'persen_tugas' => $this->persentase['tugas'],
                    'persen_praktek' => $this->persentase['praktek'],
                    'persen_kuis' => $this->persentase['kuis'],
                    'dtime_update'  =>  date('Y-m-d H:i:s')
                ]);

            // Cek keberadaan data pada transkrip
            $cek_nilai = collect(DB::select("SELECT id_transkrip FROM akd_transkrip WHERE nim = ? AND id_matakuliah = ? AND tahun_kurikulum = ?", [
                $row[0], $row[12], $row[13]
            ]))->count();

            if ($cek_nilai > 0) {
                DB::table('akd_transkrip')
                    ->where('nim', $row[0])
                    ->where('id_matakuliah', $row[12])
                    ->where('tahun_kurikulum', $row[13])
                    ->update([
                        'nilai' => $nilaiAkhirHuruf
                    ]);
            } else {
                DB::table('akd_transkrip')->insert([
                    'nim' => $row[0],
                    'id_matakuliah' => $row[12],
                    'tahun_kurikulum' => $row[13],
                    'nilai' => $nilaiAkhirHuruf
                ]);
            }
        }
    }

    public function startRow(): int
    {
        return 9; // Mulai membaca dari baris ke-9
    }

    public function getErrors(): array
    {
        return $this->errors; // Kembalikan daftar pesan error
    }
}

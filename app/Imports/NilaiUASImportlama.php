<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class NilaiUASImport implements ToCollection, WithStartRow
{
    private $errors = []; // Untuk menyimpan pesan error

    public function collection(Collection $collection)
    {
        $validGrades = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'E', 'AB', 'BC', 'CD', 'DE'];

        foreach ($collection as $key => $row) {
            $rowNumber = $key + 8; // Baris aktual (karena startRow dimulai dari 8)

            // Validasi nilai 0-100 untuk kolom kehadiran sampai nilai UAS ($row[3] hingga $row[9])
            for ($i = 3; $i <= 9; $i++) {
                if (!is_numeric($row[$i]) || $row[$i] < 0 || $row[$i] > 100) {
                    $this->errors[] = "Baris {$rowNumber}: Nilai pada kolom " . ($i + 1) . " (nilai angka) harus berupa angka 0-100.";
                    continue 2; // Lewati baris ini jika ada error
                }
            }

            // Validasi nilai akhir huruf ($row[10])
            if (!in_array($row[10], $validGrades)) {
                $this->errors[] = "Baris {$rowNumber}: Nilai pada kolom 11 (nilai huruf) harus berupa salah satu dari: " . implode(', ', $validGrades) . ".";
                continue; // Lewati baris ini jika ada error
            }

            // Update data pada `akd_detail_krs`
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

            // Cek keberadaan data pada transkrip
            $cek_nilai = collect(DB::select("SELECT id_transkrip FROM akd_transkrip WHERE nim = ? AND id_matakuliah = ? AND tahun_kurikulum = ?", [
                $row[0], $row[12], $row[13]
            ]))->count();

            if ($cek_nilai > 0) {
                // Update data transkrip jika sudah ada
                DB::table('akd_transkrip')
                    ->where('nim', $row[0])
                    ->where('id_matakuliah', $row[12])
                    ->where('tahun_kurikulum', $row[13])
                    ->update([
                        'nilai' => $row[10]
                    ]);
            } else {
                // Insert data baru ke transkrip jika belum ada
                DB::table('akd_transkrip')->insert([
                    'nim' => $row[0],
                    'id_matakuliah' => $row[12],
                    'tahun_kurikulum' => $row[13],
                    'nilai' => $row[10]
                ]);
            }
        }
    }

    public function startRow(): int
    {
        return 8; // Mulai membaca dari baris ke-8
    }

    public function getErrors(): array
    {
        return $this->errors; // Kembalikan daftar pesan error
    }
}

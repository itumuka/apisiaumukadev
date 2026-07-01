<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class JadwalUjianImport implements ToCollection, WithStartRow
{
    private $failures = [];
    private $successCount = 0;

    public function collection(Collection $collection)
    {
        foreach ($collection as $index => $row) {
            $rowNum = $index + 13; // Baris Excel asli
            
            $id_tawar = isset($row[1]) ? trim($row[1]) : null;
            $id_kelas = isset($row[2]) ? trim($row[2]) : null;
            
            // Lewati jika data kunci kosong
            if (empty($id_tawar) || empty($id_kelas)) {
                continue;
            }

            // Lewati jika bukan numerik (seperti teks header)
            if (!is_numeric($id_tawar) || !is_numeric($id_kelas)) {
                continue;
            }

            $hari = isset($row[6]) ? trim($row[6]) : '';
            $tanggal = isset($row[7]) ? trim($row[7]) : '';
            $jam_mulai = isset($row[8]) ? trim($row[8]) : '';
            $jam_selesai = isset($row[9]) ? trim($row[9]) : '';
            $ruang = isset($row[10]) ? trim($row[10]) : '';

            // 1. Validasi Kolom Wajib
            if (empty($hari) || empty($tanggal) || empty($jam_mulai) || empty($jam_selesai) || empty($ruang)) {
                $this->failures[] = "Baris $rowNum: Semua data ujian (Hari, Tanggal, Jam, Ruang) wajib diisi.";
                continue;
            }

            // 2. Validasi Hari
            $validHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
            if (!in_array($hari, $validHari)) {
                $this->failures[] = "Baris $rowNum: Hari '$hari' tidak valid. Isi dengan: Senin, Selasa, dll.";
                continue;
            }

            // 3. Validasi Format Tanggal YYYY-MM-DD
            if (is_numeric($tanggal)) {
                // Konversi tanggal number Excel
                $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('Y-m-d');
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                $this->failures[] = "Baris $rowNum: Format tanggal '$tanggal' tidak valid. Gunakan YYYY-MM-DD.";
                continue;
            }

            // 4. Validasi Format Jam HH:MM
            if (is_numeric($jam_mulai)) {
                $jam_mulai = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($jam_mulai)->format('H:i');
            }
            if (is_numeric($jam_selesai)) {
                $jam_selesai = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($jam_selesai)->format('H:i');
            }
            if (!preg_match('/^\d{2}:\d{2}$/', $jam_mulai) || !preg_match('/^\d{2}:\d{2}$/', $jam_selesai)) {
                $this->failures[] = "Baris $rowNum: Format jam mulai/selesai harus HH:MM.";
                continue;
            }

            // 5. Cek Keberadaan Kelas
            $kelasExists = DB::table('akd_kelas_kuliah')
                ->where('id_tawar', $id_tawar)
                ->where('id_kelas', $id_kelas)
                ->exists();

            if (!$kelasExists) {
                $this->failures[] = "Baris $rowNum: Kelas Kuliah dengan ID Tawar $id_tawar & ID Kelas $id_kelas tidak ditemukan.";
                continue;
            }

            // 6. Cek Bentrokan Ruangan (Same Date & Time Overlap)
            $conflict = DB::table('akd_kelas_kuliah')
                ->where('ujian_tanggal', $tanggal)
                ->where('ujian_kode_ruang', $ruang)
                ->where(function($query) use ($jam_mulai, $jam_selesai) {
                    $query->where('ujian_jam_mulai', '<', $jam_selesai . ':00')
                          ->where('ujian_jam_selesai', '>', $jam_mulai . ':00');
                })
                ->where(function($query) use ($id_tawar, $id_kelas) {
                    $query->where('id_tawar', '!=', $id_tawar)
                          ->orWhere('id_kelas', '!=', $id_kelas);
                })
                ->first();

            if ($conflict) {
                $this->failures[] = "Baris $rowNum: Bentrok ruangan '$ruang' terdeteksi dengan mata kuliah lain di jam $conflict->ujian_jam_mulai - $conflict->ujian_jam_selesai.";
                continue;
            }

            // Jika semua valid, eksekusi update
            try {
                DB::table('akd_kelas_kuliah')
                    ->where('id_tawar', $id_tawar)
                    ->where('id_kelas', $id_kelas)
                    ->update([
                        'ujian_hari' => $hari,
                        'ujian_tanggal' => $tanggal,
                        'ujian_jam_mulai' => $jam_mulai . ':00',
                        'ujian_jam_selesai' => $jam_selesai . ':00',
                        'ujian_kode_ruang' => $ruang,
                    ]);
                $this->successCount++;
            } catch (\Exception $e) {
                $this->failures[] = "Baris $rowNum: Gagal menyimpan data database (" . $e->getMessage() . ").";
            }
        }
    }

    public function startRow(): int
    {
        return 13; // Memulai dari baris ke-13 (data setelah header)
    }

    public function getFailures()
    {
        return $this->failures;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }
}

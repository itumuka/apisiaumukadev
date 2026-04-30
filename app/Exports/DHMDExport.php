<?php

namespace App\Exports;


use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class DHMDExport extends DefaultValueBinder implements WithCustomValueBinder, FromView, WithColumnFormatting, WithColumnWidths
{
    use Exportable;

    private $id_kelas;
    public function __construct($id_kelas)
    {
        $this->id_kelas = $id_kelas;
    }

    public function view(): View
    {


        $id_kelas = $this->id_kelas;
        $data = DB::select('SELECT *, DATE_FORMAT(akd_berita_acara.tgl,"%d-%m-%Y") AS tgl_indo  FROM akd_berita_acara WHERE id_kelas = "' . $id_kelas . '"');

        $data_dep = DB::table('akd_kelas_kuliah')
            ->join('akd_penawaran_matakuliah', 'akd_kelas_kuliah.id_tawar', '=', 'akd_penawaran_matakuliah.id_tawar')
            ->join('akd_matakuliah', 'akd_matakuliah.id_matakuliah', '=', 'akd_penawaran_matakuliah.id_matakuliah')
            ->join('akd_program_studi', 'akd_program_studi.kode_program_studi', '=', 'akd_penawaran_matakuliah.kode_program_studi')
            ->join('akd_fakultas', 'akd_fakultas.kode_fakultas', '=', 'akd_program_studi.kode_fakultas')
            ->join('simpeg_pegawai', 'simpeg_pegawai.id', '=', 'akd_penawaran_matakuliah.kode_dosen')
            ->selectRaw('id_kelas, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah, nama_kelas,
            CONCAT_WS(" ", gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, hari, nama_fakultas, 
            (SELECT CONCAT(
                DAY(NOW())," ",
                CASE MONTH(NOW()) 
                    WHEN 1 THEN "Januari" 
                    WHEN 2 THEN "Februari" 
                    WHEN 3 THEN "Maret" 
                    WHEN 4 THEN "April" 
                    WHEN 5 THEN "Mei" 
                    WHEN 6 THEN "Juni" 
                    WHEN 7 THEN "Juli" 
                    WHEN 8 THEN "Agustus" 
                    WHEN 9 THEN "September"
                    WHEN 10 THEN "Oktober" 
                    WHEN 11 THEN "November" 
                    WHEN 12 THEN "Desember" 
                END," ",
                YEAR(NOW())
                )) AS tglindo,
            IF(akd_penawaran_matakuliah.semester = "1", 
            CONCAT_WS(" ","Semester Ganjil", CONCAT_WS("/",tahun, tahun+1) ), CONCAT_WS(" ","Semester Genap", CONCAT_WS("/",tahun, tahun+1))) AS tahun_akademik,
            TIME_FORMAT(jam_mulai, "%H:%i") AS jam_mulai, TIME_FORMAT(jam_selesai, "%H:%i") AS jam_selesai, kode_ruang')
            ->where('id_kelas', $id_kelas)->first();

        return view('template.view_export_berita_acara', compact('data', 'data_dep'));
    }
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT
        ];
    }


    public function bindValue(Cell $cell, $value)
    {

        if ($cell->getColumn() == 'A') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }


        return parent::bindValue($cell, $value);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 8,
            'C' => 30,
            'D' => 20,
            'E' => 15,
            'F' => 15,
            'G' => 20,
            'H' => 25,
            'I' => 25,
            'J' => 25,
            'K' => 25,
            'L' => 20,
            'M' => 25
        ];
    }
}

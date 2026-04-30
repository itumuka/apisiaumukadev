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

class TemplatePresensiExport extends DefaultValueBinder implements WithCustomValueBinder, FromView, WithColumnFormatting, WithColumnWidths
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
        $data = DB::select('SELECT c.nim, nama_mahasiswa, a.id_kelas
        FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE a.id_kelas= "' . $id_kelas . '"');


        $data_dep = DB::table('akd_kelas_kuliah')
            ->join('akd_penawaran_matakuliah', 'akd_kelas_kuliah.id_tawar', '=', 'akd_penawaran_matakuliah.id_tawar')
            ->join('akd_matakuliah', 'akd_matakuliah.id_matakuliah', '=', 'akd_penawaran_matakuliah.id_matakuliah')
            ->join('akd_program_studi', 'akd_program_studi.kode_program_studi', '=', 'akd_penawaran_matakuliah.kode_program_studi')
            ->join('akd_fakultas', 'akd_fakultas.kode_fakultas', '=', 'akd_program_studi.kode_fakultas')
            ->join('simpeg_pegawai', 'simpeg_pegawai.id', '=', 'akd_penawaran_matakuliah.kode_dosen')
            ->selectRaw('akd_kelas_kuliah.id_kelas, akd_matakuliah.nama_matakuliah, akd_program_studi.nama_program_studi, akd_fakultas.nama_fakultas, IF(akd_penawaran_matakuliah.semester = "1", CONCAT_WS("","Ganjil ", CONCAT_WS("/",tahun, tahun+1) ) , 
            CONCAT_WS("","Genap ", CONCAT_WS("/",tahun, tahun+1))) AS tahun_akademik, CONCAT_WS("", gelar_depan, nama, gelar_belakang) AS fullname')
            ->where('id_kelas', $id_kelas)->first();

        return view('template.view_template_presensi', compact('data', 'data_dep'));
    }
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT
        ];
    }


    public function bindValue(Cell $cell, $value)
    {

        if ($cell->getColumn() == 'B') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }


        return parent::bindValue($cell, $value);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 15,
            'C' => 50,
            'D' => 15,
            'E' => 10
        ];
    }
}

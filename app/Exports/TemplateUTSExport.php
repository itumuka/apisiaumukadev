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

class TemplateUTSExport extends DefaultValueBinder implements WithCustomValueBinder, FromView, WithColumnFormatting, WithColumnWidths
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
        $data = DB::select('SELECT a.id_detail_krs,e.nim, e.nama_mahasiswa, g.id_matakuliah, g.nama_matakuliah, 
        f.tahun_kurikulum, nilai_uts, a.nilai_akhir_angka,a.nilai_akhir_huruf
                FROM akd_detail_krs a 
                        JOIN akd_krs b ON a.id_krs=b.id_krs 
                        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
                        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
                        JOIN akd_mahasiswa e ON c.nim = e.nim
                        JOIN akd_penawaran_matakuliah f ON f.id_tawar = d.id_tawar
                        JOIN akd_matakuliah g ON g.id_matakuliah = f.id_matakuliah
                        WHERE a.id_kelas= "' . $id_kelas . '" ORDER BY c.nim');

        //         "SELECT akd_kelas_kuliah.id_kelas, akd_matakuliah.nama_matakuliah, akd_program_studi.nama_program_studi, 
        // akd_fakultas.nama_fakultas, IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , 
        // CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik, CONCAT_WS('', gelar_depan, nama, gelar_belakang) AS fullname
        //             FROM akd_kelas_kuliah
        //             JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
        //             JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
        //             JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
        //             JOIN akd_fakultas ON akd_fakultas.kode_fakultas = akd_program_studi.kode_fakultas
        //             JOIN simpeg_pegawai ON akd_penawaran_matakuliah.kode_dosen = simpeg_pegawai.id
        //             WHERE akd_kelas_kuliah.id_kelas = '2735'";

        $data_dep = DB::table('akd_kelas_kuliah')
            ->join('akd_penawaran_matakuliah', 'akd_kelas_kuliah.id_tawar', '=', 'akd_penawaran_matakuliah.id_tawar')
            ->join('akd_matakuliah', 'akd_matakuliah.id_matakuliah', '=', 'akd_penawaran_matakuliah.id_matakuliah')
            ->join('akd_program_studi', 'akd_program_studi.kode_program_studi', '=', 'akd_penawaran_matakuliah.kode_program_studi')
            ->join('akd_fakultas', 'akd_fakultas.kode_fakultas', '=', 'akd_program_studi.kode_fakultas')
            ->join('simpeg_pegawai', 'simpeg_pegawai.id', '=', 'akd_penawaran_matakuliah.kode_dosen')
            ->selectRaw('akd_kelas_kuliah.id_kelas, nama_kelas, akd_matakuliah.nama_matakuliah, akd_program_studi.nama_program_studi, akd_fakultas.nama_fakultas, IF(akd_penawaran_matakuliah.semester = "1", CONCAT_WS("","Ganjil ", CONCAT_WS("/",tahun, tahun+1) ) , 
            CONCAT_WS("","Genap ", CONCAT_WS("/",tahun, tahun+1))) AS tahun_akademik, CONCAT_WS("", gelar_depan, nama, gelar_belakang) AS fullname')
            ->where('id_kelas', $id_kelas)->first();

        return view('template.view_template_inputnilai_uts', compact('data', 'data_dep'));
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
            'A' => 15,
            'B' => 35,
            'C' => 35,
            'D' => 20,
            'E' => 20
        ];
    }
}

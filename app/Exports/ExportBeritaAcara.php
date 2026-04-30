<?php

namespace App\Exports;


use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class ExportBeritaAcara extends DefaultValueBinder implements WithCustomValueBinder, FromView
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
        $data_dep = collect(DB::select("SELECT id_kelas, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah, nama_kelas,
        CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, hari, nama_fakultas, 
        (SELECT CONCAT(
            DAY(NOW()),' ',
            CASE MONTH(NOW()) 
                WHEN 1 THEN 'Januari' 
                WHEN 2 THEN 'Februari' 
                WHEN 3 THEN 'Maret' 
                WHEN 4 THEN 'April' 
                WHEN 5 THEN 'Mei' 
                WHEN 6 THEN 'Juni' 
                WHEN 7 THEN 'Juli' 
                WHEN 8 THEN 'Agustus' 
                WHEN 9 THEN 'September'
                WHEN 10 THEN 'Oktober' 
                WHEN 11 THEN 'November' 
                WHEN 12 THEN 'Desember' 
            END,' ',
            YEAR(NOW())
            )) AS tglindo,
        IF(akd_penawaran_matakuliah.semester = '1', 
        CONCAT_WS('','Semester Ganjil ', CONCAT_WS('/',tahun, tahun+1) ), CONCAT_WS('','Semester Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
        TIME_FORMAT(jam_mulai, '%H:%i') AS jam_mulai, TIME_FORMAT(jam_selesai, '%H:%i') AS jam_selesai, kode_ruang
        FROM akd_kelas_kuliah
        LEFT JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar = akd_kelas_kuliah.id_tawar
        LEFT JOIN akd_program_studi ON akd_program_studi.kode_program_studi=akd_penawaran_matakuliah.kode_program_studi 
        LEFT JOIN akd_fakultas ON akd_fakultas.kode_fakultas=akd_program_studi.kode_fakultas 
        LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah=akd_penawaran_matakuliah.id_matakuliah
        LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_kelas_kuliah.kode_dosen
        WHERE id_kelas ='" . $id_kelas . "'"))->first();


        $data = DB::select("SELECT *, DATE_FORMAT(akd_berita_acara.tgl,'%d-%m-%Y') AS tgl_indo  FROM akd_berita_acara WHERE id_kelas = '" . $id_kelas . "'");

        return view('template.view_export_berita_acara', compact('data_dep', 'data'));
    }
}

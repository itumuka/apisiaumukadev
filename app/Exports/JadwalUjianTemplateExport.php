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

class JadwalUjianTemplateExport extends DefaultValueBinder implements WithCustomValueBinder, FromView, WithColumnFormatting, WithColumnWidths
{
    use Exportable;

    private $tahun, $semester, $nama_prodi;

    public function __construct($tahun, $semester, $nama_prodi)
    {
        $this->tahun = $tahun;
        $this->semester = $semester;
        $this->nama_prodi = $nama_prodi;
    }

    public function view(): View
    {
        $data = DB::table('akd_kelas_kuliah')
            ->join('akd_penawaran_matakuliah', 'akd_kelas_kuliah.id_tawar', '=', 'akd_penawaran_matakuliah.id_tawar')
            ->join('akd_matakuliah', 'akd_matakuliah.id_matakuliah', '=', 'akd_penawaran_matakuliah.id_matakuliah')
            ->join('akd_program_studi', 'akd_program_studi.kode_program_studi', '=', 'akd_penawaran_matakuliah.kode_program_studi')
            ->select(
                'akd_kelas_kuliah.id_tawar',
                'akd_kelas_kuliah.id_kelas',
                'akd_matakuliah.kode_matakuliah',
                'akd_matakuliah.nama_matakuliah',
                'akd_kelas_kuliah.nama_kelas',
                'akd_kelas_kuliah.ujian_hari',
                'akd_kelas_kuliah.ujian_tanggal',
                'akd_kelas_kuliah.ujian_jam_mulai',
                'akd_kelas_kuliah.ujian_jam_selesai',
                'akd_kelas_kuliah.ujian_kode_ruang'
            )
            ->where('akd_penawaran_matakuliah.tahun', $this->tahun)
            ->where('akd_penawaran_matakuliah.semester', $this->semester)
            ->where('akd_program_studi.nama_program_studi', 'like', '%' . $this->nama_prodi . '%')
            ->get();

        return view('template.view_template_jadwalujian', [
            'data' => $data,
            'tahun' => $this->tahun,
            'semester' => $this->semester,
            'nama_prodi' => $this->nama_prodi
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), ['B', 'C'])) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 12,  // ID Tawar (Hidden/Text Putih)
            'C' => 12,  // ID Kelas (Hidden/Text Putih)
            'D' => 15,  // Kode MK
            'E' => 35,  // Nama MK
            'F' => 10,  // Kelas
            'G' => 12,  // Hari Ujian
            'H' => 15,  // Tanggal Ujian
            'I' => 12,  // Jam Mulai
            'J' => 12,  // Jam Selesai
            'K' => 15,  // Ruang Ujian
        ];
    }
}

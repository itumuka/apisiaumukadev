<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateNewUASExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private $id_kelas;

    public function __construct($id_kelas)
    {
        $this->id_kelas = $id_kelas;
    }

    public function collection()
    {
        $id_kelas = $this->id_kelas;

        return collect(DB::select('
            SELECT a.id_detail_krs, e.nim, e.nama_mahasiswa, g.nama_matakuliah,
                   f.tahun_kurikulum, a.nilai_uts, a.nilai_akhir_angka, a.nilai_akhir_huruf
            FROM akd_detail_krs a
            JOIN akd_krs b ON a.id_krs = b.id_krs
            JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
            JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
            JOIN akd_mahasiswa e ON c.nim = e.nim
            JOIN akd_penawaran_matakuliah f ON f.id_tawar = d.id_tawar
            JOIN akd_matakuliah g ON g.id_matakuliah = f.id_matakuliah
            WHERE a.id_kelas = ? ORDER BY e.nim
        ', [$id_kelas]));
    }

    public function headings(): array
    {
        return [
            ['Tabel Rekapitulasi Nilai Kelas'], // Merge header (contoh)
            [
                'No', 'NIM', 'Nama', 'Presensi (10%)', 'UTS (25%)', 
                'UAS (40%)', 'Tugas 1 (25%)', 'Tugas 2', 
                'Tugas 3', 'Tugas 4', 'Tugas 5', 'Total (100%)', 'Huruf'
            ]
        ];
    }

    public function map($row): array
    {
        $presensi = $row->nilai_uts * 0.1; // Dummy logic
        $uts = $row->nilai_uts * 0.25;
        $uas = $row->nilai_akhir_angka * 0.4; // Dummy data
        $tugas1 = $row->nilai_akhir_angka * 0.25;
        $total = $presensi + $uts + $uas + $tugas1;
        $huruf = $this->calculateGrade($total);

        return [
            null,
            $row->nim,
            $row->nama_mahasiswa,
            $presensi,
            $uts,
            $uas,
            $tugas1,
            '-', '-', '-', '-', 
            round($total, 2),
            $huruf
        ];
    }

    private function calculateGrade($total)
    {
        if ($total >= 80) return 'A';
        if ($total >= 70) return 'AB';
        if ($total >= 60) return 'B';
        if ($total >= 50) return 'C';
        return 'D';
    }

    public function styles(Worksheet $sheet)
    {
        // Merge Cell (contoh untuk header di baris 1)
        $sheet->mergeCells('A1:M1');
        
        // Styling Header
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center'
            ]
        ]);

        // Styling Subheader (baris kedua)
        $sheet->getStyle('A2:M2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => 'center'],
            'borders' => ['allBorders' => ['borderStyle' => 'thin']]
        ]);

        return [];
    }
}

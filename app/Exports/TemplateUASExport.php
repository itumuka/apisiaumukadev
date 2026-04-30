<?php

namespace App\Exports;


use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


class TemplateUASExport extends DefaultValueBinder implements WithCustomValueBinder, FromView, WithColumnFormatting, WithColumnWidths, WithEvents
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
        f.tahun_kurikulum, nilai_uts, a.nilai_akhir_angka,a.nilai_akhir_huruf, "=(E7*E6)+(F7*F6)+(G7*G6)+(H7*H6)+(I7*I6)+(J7*J6)+(K7*K6)+(L7*L6)" as f_total
                FROM akd_detail_krs a 
                        JOIN akd_krs b ON a.id_krs=b.id_krs 
                        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
                        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
                        JOIN akd_mahasiswa e ON c.nim = e.nim
                        JOIN akd_penawaran_matakuliah f ON f.id_tawar = d.id_tawar
                        JOIN akd_matakuliah g ON g.id_matakuliah = f.id_matakuliah
                        WHERE a.id_kelas= "' . $id_kelas . '" ORDER BY c.nim');

        // $data_persen = collect(DB::select('SELECT \'=IF(SUM(D8:I8)<>1;"Salah";TEXT(SUM(D8:I8);"0%"))\' as f_persen'))->first();
        
        // dd($data_persen->f_persen);

        $data_dep = DB::table('akd_kelas_kuliah')
            ->join('akd_penawaran_matakuliah', 'akd_kelas_kuliah.id_tawar', '=', 'akd_penawaran_matakuliah.id_tawar')
            ->join('akd_matakuliah', 'akd_matakuliah.id_matakuliah', '=', 'akd_penawaran_matakuliah.id_matakuliah')
            ->join('akd_program_studi', 'akd_program_studi.kode_program_studi', '=', 'akd_penawaran_matakuliah.kode_program_studi')
            ->join('akd_fakultas', 'akd_fakultas.kode_fakultas', '=', 'akd_program_studi.kode_fakultas')
            ->join('simpeg_pegawai', 'simpeg_pegawai.id', '=', 'akd_penawaran_matakuliah.kode_dosen')
            ->selectRaw('akd_kelas_kuliah.id_kelas, nama_kelas, akd_matakuliah.nama_matakuliah, akd_program_studi.nama_program_studi, akd_fakultas.nama_fakultas, IF(akd_penawaran_matakuliah.semester = "1", CONCAT_WS("","Ganjil ", CONCAT_WS("/",tahun, tahun+1) ) , 
            CONCAT_WS("","Genap ", CONCAT_WS("/",tahun, tahun+1))) AS tahun_akademik, CONCAT_WS("", gelar_depan, nama, gelar_belakang) AS fullname')
            ->where('id_kelas', $id_kelas)->first();

        return view('template.view_template_inputnilai_uas', compact('data', 'data_dep'));
    }

    public function columnFormats(): array
    {

        // Pastikan format persentase pada baris yang relevan
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            // 'D' => NumberFormat::FORMAT_PERCENTAGE_00, // Kolom D baris 8
            // 'E' => NumberFormat::FORMAT_PERCENTAGE_00, // Kolom E baris 8
            // 'F' => NumberFormat::FORMAT_PERCENTAGE_00, // Kolom F baris 8
            // 'G' => NumberFormat::FORMAT_PERCENTAGE_00, // Kolom G baris 8
            // 'H' => NumberFormat::FORMAT_PERCENTAGE_00, // Kolom H baris 8
            // 'I' => NumberFormat::FORMAT_PERCENTAGE_00, // Kolom I baris 8
            // 'J' => NumberFormat::FORMAT_PERCENTAGE_00, // Kolom J baris 8
        ];
    }


    public function bindValue(Cell $cell, $value)
    {

        if ($cell->getColumn() == 'A') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }
        
        if ($cell->getColumn() === 'J' && $cell->getRow() === 8) {
            $cell->setValueExplicit('=IF(SUM(D8:I8)>1,"Salah",IF(SUM(D8:I8)<0.01,"0%",TEXT(SUM(D8:I8),"0%")))', DataType::TYPE_FORMULA);
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
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 10,
            'M' => 10,
            'N' => 10,
        ];
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ambil sheet dari event
                $sheet = $event->sheet->getDelegate();

                // Menambahkan warna kuning pada cell D8:I8
                $sheet->getStyle('D8:I8')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFFF00', // Warna kuning
                        ],
                    ],
                ]);
    
                // Menambahkan warna kuning pada cell B5 dan D5
                $sheet->getStyle('B5')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFFF00', // Warna kuning
                        ],
                    ],
                ]);
    
                $sheet->getStyle('D5')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FFFF00', // Warna kuning
                        ],
                    ],
                ]);
    
                // Tambahkan enum pada cell B5 (Teori/Praktikum)
                $validationB5 = $sheet->getCell('B5')->getDataValidation();
                $validationB5->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationB5->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationB5->setAllowBlank(false);
                $validationB5->setShowInputMessage(true);
                $validationB5->setShowErrorMessage(true);
                $validationB5->setShowDropDown(true);
                $validationB5->setFormula1('"12,13,14,15,16"'); // Daftar enum untuk B5
                
                // Tambahkan enum pada cell D5 (1/2)
                $validationD5 = $sheet->getCell('D5')->getDataValidation();
                $validationD5->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validationD5->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validationD5->setAllowBlank(false);
                $validationD5->setShowInputMessage(true);
                $validationD5->setShowErrorMessage(true);
                $validationD5->setShowDropDown(true);
                $validationD5->setFormula1('"1,2"'); // Daftar enum untuk D5

                // Reset gaya di D1:J7 ke General
                $sheet->getStyle('D1:J7')
                      ->getNumberFormat()
                      ->setFormatCode(NumberFormat::FORMAT_GENERAL);
                
                // Terapkan persentase hanya di D8:J8
                $sheet->getStyle('D8:J8')
                      ->getNumberFormat()
                      ->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);
    
                // Query data
                $data = DB::select('SELECT e.nim, e.nama_mahasiswa, g.id_matakuliah, g.nama_matakuliah, 
                                    f.tahun_kurikulum, nilai_uts, a.nilai_akhir_angka, a.nilai_akhir_huruf 
                                    FROM akd_detail_krs a 
                                    JOIN akd_krs b ON a.id_krs=b.id_krs 
                                    JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
                                    JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
                                    JOIN akd_mahasiswa e ON c.nim = e.nim
                                    JOIN akd_penawaran_matakuliah f ON f.id_tawar = d.id_tawar
                                    JOIN akd_matakuliah g ON g.id_matakuliah = f.id_matakuliah
                                    WHERE a.id_kelas = "' . $this->id_kelas . '" ORDER BY c.nim');
    
                $startRow = 9;  // Mulai dari baris 9
                $baseColumn = 'D';  // Kolom untuk mulai iterasi
                $lastColumn = 'I';  // Kolom terakhir untuk rumus
                
                // Ambil indeks kolom awal dan akhir
                $baseColumnIndex = Coordinate::columnIndexFromString($baseColumn);
                $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
                
                foreach ($data as $index => $row) {
                    // Baris data saat ini
                    $currentRow = $startRow + $index;
                
                    // Bangun rumus dengan IFERROR dan validasi hasil lebih dari 100
                    $formula = "=IFERROR(IF(ROUND((((D{$currentRow}/B5)*100)*D8";
                
                    // Iterasi kolom E sampai I untuk menambahkan bagian penjumlahan
                    for ($colIndex = $baseColumnIndex + 1; $colIndex <= $lastColumnIndex; $colIndex++) {
                        $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $formula .= "+({$columnLetter}{$currentRow}*{$columnLetter}8)";
                    }
                
                    // Menutup bagian perhitungan dan menambahkan validasi > 100
                    $formula .= "), 2) > 100, \"Invalid\", ROUND((((D{$currentRow}/B5)*100)*D8";
                
                    // Tambahkan kembali bagian perhitungan untuk nilai valid
                    for ($colIndex = $baseColumnIndex + 1; $colIndex <= $lastColumnIndex; $colIndex++) {
                        $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $formula .= "+({$columnLetter}{$currentRow}*{$columnLetter}8)";
                    }
                
                    // Tutup rumus dan tambahkan error message
                    $formula .= "), 2)), \"#B5 is required\")";
                
                    // Set rumus pada kolom J (target hasil perhitungan)
                    $sheet->setCellValue("J{$currentRow}", $formula);
                }

    
                // Format kolom D9:I sebagai General
                $sheet->getStyle('D9:I' . ($startRow + count($data) - 1))
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_GENERAL);
                // Format kolom J9 ke bawah sebagai Number (General)
                $sheet->getStyle("J9:J" . ($startRow + count($data) - 1))
                      ->getNumberFormat()
                      ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                foreach ($data as $index => $row) {
                    $currentRow = $startRow + $index; // Baris saat ini

                    // Terapkan warna kuning pada cell D9:I9 sesuai iterasi
                    $range = "D{$currentRow}:I{$currentRow}";
                    $sheet->getStyle($range)->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => 'FFFF00', // Warna kuning
                            ],
                        ],
                    ]);

                    // Menambahkan rumus pada kolom J berdasarkan nilai di D5
                    $formulaIf1 = 'IF(J' . $currentRow . '>=91,"A",IF(J' . $currentRow . '>=86,"A-",IF(J' . $currentRow . '>=81,"B+",IF(J' . $currentRow . '>=76,"B",IF(J' . $currentRow . '>=71,"B-",IF(J' . $currentRow . '>=66,"C+",IF(J' . $currentRow . '>=60,"C",IF(J' . $currentRow . '>=55,"C-",IF(J' . $currentRow . '>=50,"D+",IF(J' . $currentRow . '>=40,"D","E"))))))))))';
                    
                    $formulaIf2 = 'IF(J' . $currentRow . '>=81,"A",IF(J' . $currentRow . '>=76,"AB",IF(J' . $currentRow . '>=66,"B",IF(J' . $currentRow . '>=60,"BC",IF(J' . $currentRow . '>=51,"C",IF(J' . $currentRow . '>=46,"CD",IF(J' . $currentRow . '>=36,"D",IF(J' . $currentRow . '>=31,"DE","E"))))))))';
                    
                    $formula = "=IF(D5=1, $formulaIf1, IF(D5=2, $formulaIf2, \"Invalid\"))";
                    
                    $sheet->setCellValue('K' . $currentRow, $formula);
                }
    
                // Format kolom K sebagai teks untuk memastikan rumus diinterpretasikan dengan benar
                $sheet->getStyle("K$startRow:K" . ($startRow + count($data) - 1))
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                    
                foreach ($data as $index => $row) {
                    $currentRow = $startRow + $index;
    
                    // Pengisian data lainnya...
    
                    // Terapkan border untuk baris data ini
                    $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['argb' => '000000'],
                            ],
                        ],
                    ]);
                }
    
                // Terapkan border pada header
                $sheet->getStyle("A7:K7")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
    
                // Terapkan border pada seluruh tabel (header + data)
                $totalRows = $startRow + count($data) - 1;
                $sheet->getStyle("A7:K{$totalRows}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
                    
            }
        ];
    }
    
    // Utility function untuk mengonversi indeks kolom menjadi huruf (misal 5 = E, 6 = F, dst)
    private function getColumnLetter($columnIndex)
    {
        $letters = range('A', 'Z');
        return $letters[$columnIndex - 1];  // Menyesuaikan karena array dimulai dari indeks 0
    }
    
}

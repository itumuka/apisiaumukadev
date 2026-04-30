<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class PresensiImport implements ToCollection, WithStartRow
{

    private $kelas_id, $tgl, $pertemuan;
    public function __construct($kelas_id, $tgl, $pertemuan)
    {
        $this->kelas_id = $kelas_id;
        $this->tgl = $tgl;
        $this->pertemuan = $pertemuan;
    }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {

        $kelas_id = $this->kelas_id;
        $tgl = $this->tgl;
        $pertemuan = $this->pertemuan;
        $nimhadir = '';
        $nimsakit = '';
        $nimijin = '';
        $nimalpha = '';
        $nimarray = [];
        $absenarray = [];

        foreach ($collection as $row) {
            $nimarray[]                     = $row[1];
            $absenarray[]                   = $row[3];
        }
        $jml = count($absenarray);
        for ($i = 0; $i < $jml; $i++) {
            if ($absenarray[$i] == 'H') {
                $nimhadir = $nimhadir . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'S') {
                $nimsakit = $nimsakit . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'I') {
                $nimijin = $nimijin . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'A') {
                $nimalpha = $nimalpha . '#' . $nimarray[$i];
            }
        }


        DB::table('akd_presensi_mhs')->insert([
            'id_kelas'  =>  $kelas_id,
            'tgl'  => $tgl,
            'pertemuan' => $pertemuan,
            'hadir' => substr($nimhadir, 1),
            'sakit' => substr($nimsakit, 1),
            'ijin'  => substr($nimijin, 1),
            'alpha' => substr($nimalpha, 1)
        ]);
    }

    public function startRow(): int
    {
        return 8;
    }
}

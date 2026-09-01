<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$mhs = DB::table('akd_mahasiswa')->where('nim', 'TK0124001')->first();
echo "MAHASISWA:\n";
print_r($mhs);

$krsList = DB::table('akd_krs')
    ->join('akd_heregistrasi', 'akd_krs.id_heregistrasi', '=', 'akd_heregistrasi.id_heregistrasi')
    ->where('akd_heregistrasi.nim', 'TK0124001')
    ->select('akd_krs.*', 'akd_heregistrasi.tahun', 'akd_heregistrasi.semester')
    ->get();
echo "\nKRS LIST:\n";
print_r($krsList);

if ($krsList->count() > 0) {
    foreach ($krsList as $krs) {
        echo "\nDETAILS for KRS ID " . $krs->id_krs . " (Tahun: {$krs->tahun}, Semester: {$krs->semester}):\n";
        $details = DB::table('akd_detail_krs')
            ->join('akd_kelas_kuliah', 'akd_detail_krs.id_kelas', '=', 'akd_kelas_kuliah.id_kelas')
            ->join('akd_penawaran_matakuliah', 'akd_kelas_kuliah.id_tawar', '=', 'akd_penawaran_matakuliah.id_tawar')
            ->join('akd_matakuliah', 'akd_penawaran_matakuliah.id_matakuliah', '=', 'akd_matakuliah.id_matakuliah')
            ->leftJoin('simpeg_pegawai', 'akd_penawaran_matakuliah.kode_dosen', '=', 'simpeg_pegawai.id')
            ->where('akd_detail_krs.id_krs', $krs->id_krs)
            ->select(
                'akd_detail_krs.id_detail_krs',
                'akd_kelas_kuliah.id_kelas',
                'akd_kelas_kuliah.nama_kelas',
                'akd_kelas_kuliah.hari',
                'akd_kelas_kuliah.jam_mulai',
                'akd_kelas_kuliah.jam_selesai',
                'akd_kelas_kuliah.kode_ruang',
                'akd_matakuliah.kode_matakuliah',
                'akd_matakuliah.nama_matakuliah',
                'akd_penawaran_matakuliah.sks_matakuliah',
                'akd_penawaran_matakuliah.id_tawar',
                'simpeg_pegawai.nama as nama_dosen'
            )
            ->get();
        print_r($details);

        // Check if there are any BAP records for these classes
        foreach ($details as $d) {
            $bap = DB::table('akd_berita_acara')->where('id_kelas', $d->id_kelas)->get();
            if ($bap->count() > 0) {
                echo "\nBAP for Kelas ID {$d->id_kelas} ({$d->nama_matakuliah}):\n";
                print_r($bap);
            }
        }
    }
}

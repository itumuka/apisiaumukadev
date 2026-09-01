<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check active classes for Erwin Apriliyanto (dosen id 10 / id_pegawai 49)
// Let's get students enrolled in Digital Marketing (id_kelas 2455) or Simulasi Bisnis Digital (id_kelas 2515)
$students2455 = DB::select("
    SELECT 
        mhs.nim, 
        mhs.nama_mahasiswa,
        kls.id_kelas,
        kls.nama_kelas,
        m.kode_matakuliah,
        m.nama_matakuliah,
        p.tahun,
        p.semester
    FROM akd_detail_krs dk
    JOIN akd_krs k ON dk.id_krs = k.id_krs
    JOIN akd_heregistrasi h ON k.id_heregistrasi = h.id_heregistrasi
    JOIN akd_mahasiswa mhs ON h.nim = mhs.nim
    JOIN akd_kelas_kuliah kls ON dk.id_kelas = kls.id_kelas
    JOIN akd_penawaran_matakuliah p ON kls.id_tawar = p.id_tawar
    JOIN akd_matakuliah m ON p.id_matakuliah = m.id_matakuliah
    WHERE kls.id_kelas = 2455
    LIMIT 10
");
echo "STUDENTS IN KELAS 2455 (Digital Marketing - Erwin Apriliyanto):\n";
print_r($students2455);

// Check BAP for id_kelas 2455
$bap2455 = DB::table('akd_berita_acara')->where('id_kelas', 2455)->get();
echo "\nBAP IN KELAS 2455:\n";
print_r($bap2455);

// Also check student TK0124001 dosen wali
$waliMhs = DB::select("
    SELECT mhs.nim, mhs.nama_mahasiswa, mhs.id_dosen_wali, sp.nama as nama_dosen_wali, sp.id as id_pegawai_wali
    FROM akd_mahasiswa mhs
    LEFT JOIN simpeg_pegawai sp ON mhs.id_dosen_wali = sp.id
    WHERE mhs.nim = 'TK0124001'
");
echo "\nDOSEN WALI OF TK0124001:\n";
print_r($waliMhs);

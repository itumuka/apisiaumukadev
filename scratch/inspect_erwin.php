<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Find lecturer Erwin Aprilianto
$dosen = DB::select("SELECT * FROM simpeg_pegawai WHERE nama LIKE '%Erwin%'");
echo "DOSEN ERWIN:\n";
print_r($dosen);

$userDosen = DB::select("
    SELECT ud.*, sp.nama, sp.nidn 
    FROM user_dosen ud
    JOIN simpeg_pegawai sp ON ud.id_pegawai = sp.id
    WHERE sp.nama LIKE '%Erwin%'
");
echo "\nUSER DOSEN:\n";
print_r($userDosen);

if (!empty($dosen)) {
    $dosenId = $dosen[0]->id;
    echo "\nDOSEN ID: {$dosenId}\n";

    // Find all classes taught by Erwin Aprilianto
    $classes = DB::select("
        SELECT 
            p.tahun,
            p.semester,
            kls.id_kelas,
            kls.nama_kelas,
            kls.hari,
            kls.jam_mulai,
            kls.jam_selesai,
            kls.kode_ruang,
            kls.jumlah_peserta,
            m.id_matakuliah,
            m.kode_matakuliah,
            m.nama_matakuliah,
            p.sks_matakuliah,
            p.id_tawar,
            ps.nama_program_studi
        FROM akd_penawaran_matakuliah p
        JOIN akd_kelas_kuliah kls ON p.id_tawar = kls.id_tawar
        JOIN akd_matakuliah m ON p.id_matakuliah = m.id_matakuliah
        JOIN akd_program_studi ps ON p.kode_program_studi = ps.kode_program_studi
        WHERE p.kode_dosen = '{$dosenId}'
        ORDER BY p.tahun DESC, p.semester DESC, kls.id_kelas ASC
    ");
    echo "\nCLASSES TAUGHT BY ERWIN APRILIANTO:\n";
    foreach ($classes as $c) {
        echo "Tahun: {$c->tahun}/{$c->semester} | Prodi: {$c->nama_program_studi} | id_kelas: {$c->id_kelas} (id_tawar: {$c->id_tawar}) | MK: {$c->kode_matakuliah} - {$c->nama_matakuliah} (Kelas {$c->nama_kelas}) | Peserta: {$c->jumlah_peserta} | Jadwal: {$c->hari}, {$c->jam_mulai}-{$c->jam_selesai} (Ruang: {$c->kode_ruang})\n";
    }

    // Check which of these classes has student TK0124001
    $mhsClasses = DB::select("
        SELECT 
            h.nim,
            mhs.nama_mahasiswa,
            h.tahun,
            h.semester,
            kls.id_kelas,
            kls.nama_kelas,
            m.kode_matakuliah,
            m.nama_matakuliah,
            p.id_tawar
        FROM akd_detail_krs dk
        JOIN akd_krs k ON dk.id_krs = k.id_krs
        JOIN akd_heregistrasi h ON k.id_heregistrasi = h.id_heregistrasi
        JOIN akd_mahasiswa mhs ON h.nim = mhs.nim
        JOIN akd_kelas_kuliah kls ON dk.id_kelas = kls.id_kelas
        JOIN akd_penawaran_matakuliah p ON kls.id_tawar = p.id_tawar
        JOIN akd_matakuliah m ON p.id_matakuliah = m.id_matakuliah
        WHERE p.kode_dosen = '{$dosenId}' AND h.nim = 'TK0124001'
    ");
    echo "\nCLASSES WHERE TK0124001 IS ENROLLED IN ERWIN APRILIANTO'S CLASS:\n";
    print_r($mhsClasses);

    // Let's check BAP for Erwin's classes
    foreach ($mhsClasses as $mc) {
        $bap = DB::table('akd_berita_acara')->where('id_kelas', $mc->id_kelas)->get();
        echo "\nBAP for id_kelas {$mc->id_kelas} ({$mc->nama_matakuliah}):\n";
        print_r($bap);
    }
}

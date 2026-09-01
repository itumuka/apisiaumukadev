<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Current DB Name: " . DB::connection()->getDatabaseName() . "\n";

$mhs = DB::select("SELECT * FROM akd_mahasiswa WHERE nim='TK0124001'");
echo "Mahasiswa:\n";
print_r($mhs);

$classes = DB::select("
    SELECT 
        dk.id_detail_krs,
        k.id_krs,
        h.tahun,
        h.semester,
        kls.id_kelas,
        kls.nama_kelas,
        kls.hari,
        kls.jam_mulai,
        kls.jam_selesai,
        kls.kode_ruang,
        m.kode_matakuliah,
        m.nama_matakuliah,
        p.sks_matakuliah,
        p.id_tawar,
        sp.nama as nama_dosen
    FROM akd_detail_krs dk
    JOIN akd_krs k ON dk.id_krs = k.id_krs
    JOIN akd_heregistrasi h ON k.id_heregistrasi = h.id_heregistrasi
    JOIN akd_kelas_kuliah kls ON dk.id_kelas = kls.id_kelas
    JOIN akd_penawaran_matakuliah p ON kls.id_tawar = p.id_tawar
    JOIN akd_matakuliah m ON p.id_matakuliah = m.id_matakuliah
    LEFT JOIN simpeg_pegawai sp ON p.kode_dosen = sp.id
    WHERE h.nim = 'TK0124001'
    ORDER BY h.tahun DESC, h.semester DESC, kls.id_kelas ASC
");

foreach ($classes as $c) {
    echo "Tahun: {$c->tahun}/{$c->semester} | id_kelas: {$c->id_kelas} | MK: {$c->kode_matakuliah} - {$c->nama_matakuliah} | Dosen: {$c->nama_dosen} | Jadwal: {$c->hari}, {$c->jam_mulai}-{$c->jam_selesai} (Ruang: {$c->kode_ruang})\n";
}

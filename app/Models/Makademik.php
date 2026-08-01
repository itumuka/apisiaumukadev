<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class Makademik extends Model
{
    public function daftarmhs_pa(Request $request)
    {
        $daftarmhs_pa = DB::select("SELECT akd_mahasiswa.nim, nama_mahasiswa, tahun_angkatan, nama_program_studi, adm_camaba.telp AS no_hp, IF(sks_ambil > 0, 'KRS','Tidak KRS') AS status_krs
        FROM akd_mahasiswa 
            LEFT JOIN adm_camaba ON adm_camaba.no_pendaftaran = akd_mahasiswa.no_pendaftaran
            LEFT JOIN (SELECT akd_heregistrasi.nim,akd_krs.sks_ambil FROM akd_heregistrasi
            LEFT JOIN akd_krs ON akd_krs.id_heregistrasi = akd_heregistrasi.id_heregistrasi WHERE akd_heregistrasi.tahun = '" . $request->tahun . "' AND akd_heregistrasi.semester='" . $request->semester . "') AS tbl1 ON akd_mahasiswa.nim=tbl1.nim
            LEFT JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi = akd_program_studi.kode_program_studi
            WHERE id_dosen_wali = '" . $request->kode_dosen . "'");
        // $daftarmhs_pa = DB::select("SELECT akd_mahasiswa.nim, nama_mahasiswa, tahun_angkatan, nama_program_studi, telp AS no_hp, IF(sks_ambil > 0, 'KRS','Tidak KRS') AS status_krs
        // FROM akd_mahasiswa 
        //     JOIN adm_camaba ON adm_camaba.no_pendaftaran = akd_mahasiswa.no_pendaftaran
        //     LEFT JOIN akd_heregistrasi ON akd_heregistrasi.nim = akd_mahasiswa.nim
        //     LEFT JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi = akd_program_studi.kode_program_studi
        //     LEFT JOIN akd_krs ON akd_krs.id_heregistrasi = akd_heregistrasi.id_heregistrasi
        //     WHERE id_dosen_wali = '" . $request->kode_dosen . "' AND akd_heregistrasi.tahun = '" . $request->tahun . "' AND akd_heregistrasi.semester='" . $request->semester . "'");
        return $daftarmhs_pa;
    }
    public function cek_transkrip_krs(Request $request)
    {
        $cek_transkrip_krs = DB::select("SELECT akd_transkrip.nim, akd_matakuliah.smt_matakuliah, akd_matakuliah.kode_matakuliah, akd_matakuliah.id_matakuliah, akd_matakuliah.nama_matakuliah, akd_matakuliah.sks_matakuliah, 
        MIN(akd_transkrip.nilai) as nilai, 
        MAX(akd_predikat_nilai_huruf.mutu) AS mutu_nilai, 
        (akd_matakuliah.sks_matakuliah*MAX(mutu)) AS kum_sksmutu 
        FROM akd_transkrip
        JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_transkrip.id_matakuliah 
        JOIN akd_predikat_nilai_huruf ON akd_transkrip.nilai = akd_predikat_nilai_huruf.nilai_huruf_akhir 
        WHERE akd_transkrip.nim ='" . $request->nim . "' 
        GROUP BY akd_transkrip.id_matakuliah 
        ORDER BY akd_matakuliah.smt_matakuliah");

        return $cek_transkrip_krs;
    }

    public function presensi_permakul(Request $request)
    {
        $presensi_permakul = DB::select("SELECT c.nim, nama_mahasiswa,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS i,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS ii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS iii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS iv,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS v,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS vi,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS vii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS viii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS ix,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS 'x',
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS xi,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS xii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS xiii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS xiv,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS xv,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE '0'
        END AS xvi,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '1') AS tgl1,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '2') AS tgl2,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '3') AS tgl3,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '4') AS tgl4,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '5') AS tgl5,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '6') AS tgl6,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '7') AS tgl7,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '8') AS tgl8,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '9') AS tgl9,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '10') AS tgl10,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '11') AS tgl11,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '12') AS tgl12,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '13') AS tgl13,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '14') AS tgl14,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '15') AS tgl15,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '16') AS tgl16
        FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE a.id_kelas='$request->id_kelas' AND c.krs='1' ORDER BY c.nim ASC");
        return $presensi_permakul;
    }
    public function presensi_permakul1(Request $request)
    {
        $presensi_permakul = DB::select("SELECT c.nim, nama_mahasiswa,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '1' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS i,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '2' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS ii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '3' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS iii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '4' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS iv,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '5' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS v,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '6' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS vi,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '7' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS vii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '8' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS viii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '9' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS ix,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '10' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS 'x',
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '11' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS xi,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '12' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS xii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '13' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS xiii,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '14' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS xiv,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '15' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS xv,
        CASE
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND hadir LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'H'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND sakit LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'S'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND ijin LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'I'
            WHEN (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '16' AND alpha LIKE CONCAT('%',c.nim,'%')) > 0 THEN 'A'
            ELSE ' '
        END AS xvi, 
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '1') AS tgl1,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '2') AS tgl2,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '3') AS tgl3,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '4') AS tgl4,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '5') AS tgl5,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '6') AS tgl6,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '7') AS tgl7,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '8') AS tgl8,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '9') AS tgl9,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '10') AS tgl10,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '11') AS tgl11,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '12') AS tgl12,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '13') AS tgl13,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '14') AS tgl14,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '15') AS tgl15,
        (SELECT tgl FROM akd_berita_acara WHERE id_kelas=a.id_kelas AND pertemuan_ke = '16') AS tgl16 
        FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE d.id_tawar='$request->id_tawar' AND c.krs='1'  ORDER BY c.nim ASC");
        return $presensi_permakul;
    }
    public function auto_pertemuan(Request $request)
    {
        $kd = "";
        $query = DB::table('akd_berita_acara')
            ->select(DB::raw('MAX(pertemuan_ke) as id'))
            ->where('id_kelas', '=', $request->id_kelas);
        if ($query->count() > 0) {
            foreach ($query->get() as $key) {
                $kd = ((int)$key->id) + 1;
            }
        } else {
            $kd = "1";
        }
        $urut = $kd;
        // echo json_encode($notasales);
        return response()->json(['urut' => $urut]);
    }

    public function auto_pertemuan_presensi(Request $request)
    {
        $kd = "";
        $query = DB::table('akd_presensi_mhs')
            ->select(DB::raw('MAX(pertemuan) as id'))
            ->where('id_kelas', '=', $request->id_kelas);
        if ($query->count() > 0) {
            foreach ($query->get() as $key) {
                $kd = ((int)$key->id) + 1;
            }
        } else {
            $kd = "1";
        }
        $urut = $kd;
        // echo json_encode($notasales);
        return response()->json(['urut' => $urut]);
    }


    public function simpan_ba(Request $request)
    {
        // $tgl = (isset($request->tgl)) ? Carbon::createFromFormat('d-m-Y', $request->tgl)->format('Y-m-d') : '';
        $list_ba = collect(DB::select("SELECT * FROM akd_berita_acara WHERE id_kelas = '$request->id_kelas' AND tgl = '$request->tgl' AND pertemuan_ke = '$request->pertemuan' "))->count();

        $list_ba_max = collect(DB::select("SELECT * FROM akd_berita_acara WHERE id_kelas = '$request->id_kelas' AND tgl = '$request->tgl'"))->count();

        if ($list_ba > 0) {
            return response()->json(['error' => 'Duplikasi data pertemuan pada tanggal yang sama, cek List BA !']);
        } else if ($list_ba_max > 1) {
            return response()->json(['error' => 'Maximum BA ditanggal yang sama, hanya boleh 2x']);
        } else {
            DB::table('akd_berita_acara')->insert([
                'id_kelas'  =>  $request->id_kelas,
                'tgl'  =>  $request->tgl,
                'pertemuan_ke'  =>  $request->pertemuan,
                'materi_makul'  =>  $request->materi_makul,
                // 'peserta_hadir'  =>  $request->peserta_hadir,
                'jam_mulai'  =>  $request->jam_mulai,
                'jam_selesai'  =>  $request->jam_selesai
            ]);
            return response()->json(['success' => 'Data BA berhasil ditambahkan !']);
        }
    }

    public function hapus_ba(Request $request)
    {
        $hapus_ba = DB::table('akd_berita_acara')->where('id', $request->id)->delete();
        return $hapus_ba;
    }

    public function validated_ba(Request $request)
    {
        $validate_ba = DB::table('akd_kelas_kuliah')->where('id_kelas', $request->id)
                    ->update([
                        'validated_ba'  =>  1
                    ]);
        // var_dump($validate_ba);
        return $validate_ba;
    }

    public function list_ba(Request $request)
    {
        $list_ba = DB::select("SELECT a.*,b.hadir, b.alpha, DATE_FORMAT(a.tgl,'%d-%m-%Y') AS tgl_indo  FROM akd_berita_acara a LEFT JOIN akd_presensi_mhs b ON a.id_kelas=b.id_kelas AND a.pertemuan_ke=b.pertemuan WHERE a.id_kelas = '$request->id_kelas' ORDER BY a.pertemuan_ke ASC");
        return $list_ba;
    }

    // public function ubah_ba(Request $request)
    // {

    //     $ubah_ba = DB::table('akd_berita_acara')
    //         ->where('id', $request->eid)
    //         ->update([
    //             'tgl'  =>  $request->etgl,
    //             'pertemuan_ke'  =>  $request->epertemuan,
    //             'materi_makul'  =>  $request->emateri_makul,
    //             'peserta_hadir'  =>  $request->epeserta_hadir,
    //             'jam_mulai'  =>  $request->ejam_mulai,
    //             'jam_selesai'  =>  $request->ejam_selesai
    //         ]);
    //     return $ubah_ba;
    // }
    public function ubah_ba(Request $request)
    {
        DB::beginTransaction();
        try {
            // Ambil data lama terlebih dahulu SEBELUM update
            $ba_lama = DB::table('akd_berita_acara')->where('id', $request->eid)->first();
    
            if (!$ba_lama) {
                DB::rollBack();
                return response()->json(['error' => 'Data Berita Acara tidak ditemukan.']);
            }
    
            // Simpan tgl & pertemuan lama
            $tgl_lama = $ba_lama->tgl;
            $pertemuan_lama = $ba_lama->pertemuan_ke;
            $id_kelas = $ba_lama->id_kelas;
    
            // Update table akd_berita_acara
            DB::table('akd_berita_acara')
                ->where('id', $request->eid)
                ->update([
                    'tgl'            => $request->etgl,
                    'pertemuan_ke'   => $request->epertemuan,
                    'materi_makul'   => $request->emateri_makul,
                    'peserta_hadir'  => $request->epeserta_hadir,
                    'jam_mulai'      => $request->ejam_mulai,
                    'jam_selesai'    => $request->ejam_selesai
                ]);
    
            // Update table akd_presensi_mhs berdasarkan tgl & pertemuan LAMA
            DB::table('akd_presensi_mhs')
                ->where('id_kelas', $id_kelas)
                ->where('tgl', $tgl_lama)
                ->where('pertemuan', $pertemuan_lama)
                ->update([
                    'tgl'       => $request->etgl,
                    'pertemuan' => $request->epertemuan
                ]);
    
            DB::commit();
            return response()->json(['success' => 'Data Berita Acara dan Presensi berhasil diubah.']);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()]);
        }
    }



    public function select_nim_tidak_hadir(Request $request)
    {
        $daftar_mahasiswa = DB::select("SELECT c.nim, nama_mahasiswa
        FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE a.id_kelas='" . $request->id_kelas . "' AND nama_mahasiswa like '%{$request->search}%'");


        if (!empty($daftar_mahasiswa[0]->nim)) {
            foreach ($daftar_mahasiswa as $namadaftar_mahasiswa) {
                $daftar_mahasiswaArray[] = array(
                    "id" => $namadaftar_mahasiswa->nim,
                    "text" => $namadaftar_mahasiswa->nim . ' ' . $namadaftar_mahasiswa->nama_mahasiswa
                );
            }
        } else {
            $daftar_mahasiswaArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $daftar_mahasiswaArray]);
    }


    public function simpan_ba_ujian(Request $request)
    {
        // $tgl = (isset($request->tgl)) ? Carbon::createFromFormat('d-m-Y', $request->tgl)->format('Y-m-d') : '';

        $nimhadir = str_replace('#', "','", $request->nim_tidak_hadir);

        $hitunghadir = ($nimhadir != '') ? collect(DB::select("SELECT c.nim FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE a.id_kelas='" . $request->id_kelas . "' AND c.nim NOT IN('" . $nimhadir . "')"))->count() : collect(DB::select("SELECT c.nim FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE a.id_kelas='" . $request->id_kelas . "'"))->count();

        // $id_ba = $request->berita_acara;

        // $jml = count($nimarray);
        // $nimalpha = '';
        // for ($i = 0; $i < $jml; $i++) {
        //     $nimalpha = $nimalpha . '#' . $nimarray[$i];
        // }

        $list_ba = collect(DB::select("SELECT * FROM akd_berita_acara_ujian WHERE id_kelas = '$request->id_kelas' AND jenis_ujian = '$request->jenis_ujian'"))->count();
        if ($list_ba > 0) {
            return response()->json(['error' => 'Berita Acara Ujian (' . $request->jenis_ujian . ') telah diisi, anda sudah bisa input nilai mahasiswa !']);
        } else {
            DB::table('akd_berita_acara_ujian')->insert([
                'id_kelas'  =>  $request->id_kelas,
                'tgl_ujian'  =>  $request->tgl_ujian,
                'jenis_ujian'  =>  $request->jenis_ujian,
                'jam_mulai'  =>  $request->jam_mulai,
                'jam_selesai'  =>  $request->jam_selesai,
                'jml_mhs'  =>  $request->jml_mhs,
                'jml_hadir'  =>  $hitunghadir,
                'nim_tidak_hadir'  =>  $request->nim_tidak_hadir
            ]);
            return response()->json(['success' => 'BA Ujian berhasil ditambahkan !']);
        }
    }

    public function ubah_ba_ujian(Request $request)
    {

        $nimhadir = str_replace('#', "','", $request->nim_tidak_hadir);
        $hitunghadir = collect(DB::select("SELECT c.nim FROM akd_detail_krs a 
           JOIN akd_krs b ON a.id_krs=b.id_krs 
           JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
           JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
           JOIN akd_mahasiswa e ON c.nim = e.nim
           WHERE a.id_kelas='" . $request->id_kelas . "' AND c.nim NOT IN('" . $nimhadir . "')"))->count();

        $ubah_ba_ujian = DB::table('akd_berita_acara_ujian')
            ->where('id_ba_ujian', $request->eid)
            ->update([
                'tgl_ujian'  =>  $request->tgl_ujian,
                'jenis_ujian'  =>  $request->jenis_ujian,
                'jam_mulai'  =>  $request->jam_mulai,
                'jam_selesai'  =>  $request->jam_selesai,
                'jml_mhs'  =>  $request->jml_mhs,
                'jml_hadir'  =>  $hitunghadir,
                'nim_tidak_hadir'  =>  $request->nim_tidak_hadir
            ]);
        return $ubah_ba_ujian;
    }

    public function hapus_ba_ujian(Request $request)
    {
        $hapus_ba_ujian = DB::table('akd_berita_acara_ujian')->where('id_ba_ujian', $request->id)->delete();
        return $hapus_ba_ujian;
    }

    public function list_ba_ujian(Request $request)
    {
        $list_ba = DB::select("SELECT *, DATE_FORMAT(akd_berita_acara_ujian.tgl_ujian,'%d-%m-%Y') AS tgl_indo FROM akd_berita_acara_ujian WHERE id_kelas = '$request->id_kelas'");
        return $list_ba;
    }

    public function list_mhs_help_ba_ujian(Request $request)
    {
        $list_mhs_help_ba_ujian = DB::select("SELECT c.nim, nama_mahasiswa
        FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE a.id_kelas='" . $request->id_kelas . "'");
        return $list_mhs_help_ba_ujian;
    }

    public function data_lihat_absen_ujian(Request $request)
    {

        $data_lihat_absen_ujian = ($request->nim != '' || $request->nim != null || $request->nim != 0) ? DB::select("SELECT nim, nama_mahasiswa FROM akd_mahasiswa WHERE nim IN (" . $request->nim . ")") : DB::select("SELECT nim, nama_mahasiswa FROM akd_mahasiswa WHERE nim = '" . $request->nim . "'");

        // $data_lihat_mhs_presensi = DB::select("SELECT nim, nama_mahasiswa FROM akd_mahasiswa WHERE nim IN (" . $request->nim . ")");
        return $data_lihat_absen_ujian;
    }


    public function modal_sks_ambil(Request $request)
    {
        $modal_sks_ambil = DB::select("SELECT * FROM akd_detail_krs
        JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas
        JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
        JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
        WHERE akd_detail_krs.id_krs= '$request->id_krs'");
        return $modal_sks_ambil;
    }


    public function cetakkrs(Request $request)
    {
        $nim = $request->nim;
        $tahun = $request->tahun;
        $semester = $request->semester;
        $cetakkrs = DB::select("SELECT akd_matakuliah.kode_matakuliah,akd_matakuliah.nama_matakuliah,akd_heregistrasi.nim, akd_penawaran_matakuliah.sks_matakuliah, nama_kelas, 
        CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS nama_dosen,akd_matakuliah.smt_matakuliah,akd_matakuliah.sks_teori,akd_matakuliah.sks_praktikum FROM akd_heregistrasi
        JOIN akd_krs ON akd_heregistrasi.id_heregistrasi=akd_krs.id_heregistrasi
        JOIN akd_detail_krs ON akd_krs.id_krs=akd_detail_krs.id_krs
        JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas
        JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar 
        JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
        JOIN simpeg_pegawai ON simpeg_pegawai.id=akd_penawaran_matakuliah.kode_dosen 
        WHERE akd_heregistrasi.nim='" . $nim . "' AND akd_heregistrasi.tahun='" . $tahun . "' AND akd_heregistrasi.semester='" . $semester . "'");
        return $cetakkrs;
    }


    public function cetakkhs(Request $request)
    {

        $check_herregistrasi = collect(DB::select("SELECT akd_heregistrasi.id_heregistrasi 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.krs = 1
        AND akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'"))->first();

        // $id_her = isset($request->id_her) ? $request->id_her : $check_herregistrasi->id_heregistrasi;
        $id_her = $check_herregistrasi->id_heregistrasi;
        $kode_nilai = $request->kode_nilai;

        $data_khs = DB::select("SELECT akd_krs.id_krs, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah AS sks, akd_penawaran_matakuliah.smt_matakuliah AS semester, nama_kelas, 
        kode_ruang, jumlah_peserta, nilai_uts, nilai_huruf_akhir, nilai_akhir_angka, mutu,
        round(akd_predikat_nilai_huruf.mutu * akd_matakuliah.sks_matakuliah,2) AS total_nilai,akd_penawaran_matakuliah.kode_dosen, CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS nama_dosen
        FROM akd_krs
        LEFT JOIN akd_detail_krs ON akd_detail_krs.id_krs=akd_krs.id_krs
        LEFT JOIN akd_predikat_nilai_huruf ON akd_predikat_nilai_huruf.nilai_huruf_akhir = akd_detail_krs.nilai_akhir_huruf AND akd_predikat_nilai_huruf.kode_nilai = " . $kode_nilai . "
        LEFT JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas
        LEFT JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
        LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah=akd_penawaran_matakuliah.id_matakuliah
        LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
        WHERE akd_krs.id_heregistrasi = '" . $id_her . "'");


        return $data_khs;
    }


    public function getmhs_cetak(Request $request)
    {
        $getmhs_cetak = collect(DB::select("SELECT a.nim,a.nama_mahasiswa,b.nama_program_studi, b.kode_program_studi, b.kode_jenjang_pendidikan, b.nama_program_studi, nama_fakultas, tahun_angkatan, CONCAT_WS(' ', ds.gelar_depan, ds.nama, ds.gelar_belakang) AS dosen_wali, CONCAT_WS(' ', dp.gelar_depan, dp.nama, dp.gelar_belakang) AS namaprodi, ds.nidn AS nidndosene, dq.valid_id AS valididdosenwali, CONCAT_WS(' ', dk.gelar_depan, dk.nama, dk.gelar_belakang) AS dekane, qm_fakultas.valid_id AS valididdekane, dk.nidn AS nidndekane, qm_prodi.valid_id AS valididprodi, dp.nidn AS nidnprodi
        FROM akd_mahasiswa a 
        JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi 
        JOIN akd_fakultas c ON c.kode_fakultas=b.kode_fakultas 
        LEFT JOIN simpeg_pegawai ds ON ds.id=a.id_dosen_wali
        LEFT JOIN akd_qrcode dq ON dq.id_dosen = a.id_dosen_wali 
        LEFT JOIN simpeg_pegawai dk ON dk.id=c.pimpinan
        LEFT JOIN simpeg_pegawai dp ON dp.id = b.pimpinan_prodi
        LEFT JOIN akd_qrcode_manajemen qm_prodi ON qm_prodi.id_dosen = b.pimpinan_prodi
        LEFT JOIN akd_qrcode_manajemen qm_fakultas ON qm_fakultas.id_dosen = c.pimpinan
        WHERE a.nim = '" . $request->nim . "'
        ORDER BY a.id_mhs DESC"))->first();
        return $getmhs_cetak;
    }

    public function getkelasdanbaujian_cetak(Request $request)
    {
        $getkelasdanbaujian_cetak = collect(DB::select("SELECT akd_berita_acara_ujian.id_kelas, jenis_ujian,jml_hadir, jml_mhs, nim_tidak_hadir, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah, nama_kelas,CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen,
        IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil TA ', CONCAT_WS('/',tahun, tahun+1) ),CONCAT_WS('','Genap TA ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
         CASE DAYOFWEEK(akd_berita_acara_ujian.tgl_ujian)
            WHEN 1 THEN 'Minggu'
            WHEN 2 THEN 'Senin'
            WHEN 3 THEN 'Selasa'
            WHEN 4 THEN 'Rabu'
            WHEN 5 THEN 'Kamis'
            WHEN 6 THEN 'Jumat'
            WHEN 7 THEN 'Sabtu'
        END AS hari, 
        (SELECT CONCAT(
            DAY(akd_berita_acara_ujian.tgl_ujian),' ',
            CASE MONTH(akd_berita_acara_ujian.tgl_ujian) 
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
            YEAR(akd_berita_acara_ujian.tgl_ujian)
            )) AS tglindo,
        nama_program_studi, nama_fakultas,
        TIME_FORMAT(akd_berita_acara_ujian.jam_mulai, '%H:%i') AS jam_mulai, 
        TIME_FORMAT(akd_berita_acara_ujian.jam_selesai, '%H:%i') AS jam_selesai, kode_ruang
        FROM akd_berita_acara_ujian
        JOIN akd_kelas_kuliah ON akd_berita_acara_ujian.id_kelas = akd_kelas_kuliah.id_kelas
        LEFT JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar = akd_kelas_kuliah.id_tawar
        LEFT JOIN akd_program_studi ON akd_program_studi.kode_program_studi=akd_penawaran_matakuliah.kode_program_studi 
        LEFT JOIN akd_fakultas ON akd_fakultas.kode_fakultas=akd_program_studi.kode_fakultas 
        LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah=akd_penawaran_matakuliah.id_matakuliah
        LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_kelas_kuliah.kode_dosen
        WHERE akd_berita_acara_ujian.id_ba_ujian = '" . $request->id . "'"))->first();

        return $getkelasdanbaujian_cetak;
        // return response()->json(['data' => $getkelasdanbaujian_cetak]);
    }


    public function getkelasmk_cetak(Request $request)
    {
        $getkelasmk_cetak = collect(DB::select("SELECT id_kelas, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah, nama_kelas,sp.nama AS dekan, hari, nama_fakultas, i.nama AS namakaprodi,
CONCAT_WS(' ', e.gelar_depan, e.nama,e.gelar_belakang) AS nama_dosen, CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2,
TIME_FORMAT(jam_mulai, '%H:%i') AS jam_mulai, TIME_FORMAT(jam_selesai, '%H:%i') AS jam_selesai, kode_ruang, akd_program_studi.nama_program_studi, akd_program_studi.file_ttd
            FROM akd_kelas_kuliah
            LEFT JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar = akd_kelas_kuliah.id_tawar
            LEFT JOIN akd_program_studi ON akd_program_studi.kode_program_studi=akd_penawaran_matakuliah.kode_program_studi 
            LEFT JOIN akd_fakultas ON akd_fakultas.kode_fakultas=akd_program_studi.kode_fakultas 
            LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah=akd_penawaran_matakuliah.id_matakuliah
            LEFT JOIN simpeg_pegawai e ON e.id=akd_kelas_kuliah.kode_dosen
            LEFT JOIN simpeg_pegawai h ON h.id=akd_kelas_kuliah.kode_dosen2
            LEFT JOIN simpeg_pegawai i ON i.id=akd_program_studi.pimpinan_prodi
            LEFT JOIN simpeg_pegawai sp ON sp.id = akd_fakultas.pimpinan
            WHERE id_kelas ='" . $request->id_kelas . "'"))->first();
            return $getkelasmk_cetak;
    }

    public function riwayat_mengajar(Request $request)
    {
        $riwayat_mengajar = DB::select("SELECT CONCAT_WS('-', kode_matakuliah, nama_matakuliah) AS makul, 
        IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , 
        CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik, 
        akd_penawaran_matakuliah.tahun_kurikulum, akd_matakuliah.sks_matakuliah, akd_matakuliah.smt_matakuliah, nama_program_studi, nama_kelas
                    FROM akd_penawaran_matakuliah
                    JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
                    JOIN akd_kelas_kuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
                    JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
                    JOIN simpeg_pegawai ON akd_penawaran_matakuliah.kode_dosen = simpeg_pegawai.id
                    WHERE akd_penawaran_matakuliah.kode_dosen='" . $request->id_pegawai . "'
        ORDER BY akd_penawaran_matakuliah.tahun DESC");

        return $riwayat_mengajar;
    }

    public function list_mhs_presensi(Request $request)
    {
        $daftar_mahasiswa = DB::select("SELECT c.nim, nama_mahasiswa,
        (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '" . $request->pertemuan . "' AND hadir LIKE CONCAT('%',c.nim,'%')) AS hadir,
        (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '" . $request->pertemuan . "' AND sakit LIKE CONCAT('%',c.nim,'%')) AS sakit, 
        (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '" . $request->pertemuan . "' AND ijin LIKE CONCAT('%',c.nim,'%')) AS ijin,
        (SELECT COUNT(id_kelas) FROM akd_presensi_mhs WHERE id_kelas=a.id_kelas AND pertemuan = '" . $request->pertemuan . "' AND alpha LIKE CONCAT('%',c.nim,'%')) AS alpha
        FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        WHERE a.id_kelas='" . $request->id_kelas . "' AND c.krs='1' ORDER BY c.nim ASC");

        return $daftar_mahasiswa;
    }

    public function list_mhs_inputnilai(Request $request)
    {
        $daftar_mahasiswa = DB::select("SELECT a.id_detail_krs,e.nim, e.nama_mahasiswa, e.kode_penilaian, g.nama_matakuliah, nilai_uts, a.nilai_akhir_angka,a.nilai_akhir_huruf, f.sks_matakuliah, f.smt_matakuliah, f.tahun_kurikulum, f.id_matakuliah, nilai_uts, nilai_uas, nilai_tugas, nilai_kuis, nilai_praktek, kehadiran, nilai_akhir_angka, nilai_akhir_huruf FROM akd_detail_krs a 
        JOIN akd_krs b ON a.id_krs=b.id_krs 
        JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
        JOIN akd_kelas_kuliah d ON a.id_kelas = d.id_kelas
        JOIN akd_mahasiswa e ON c.nim = e.nim
        JOIN akd_penawaran_matakuliah f ON f.id_tawar = d.id_tawar
        JOIN akd_matakuliah g ON g.id_matakuliah = f.id_matakuliah
        WHERE a.id_kelas='" . $request->id_kelas . "' AND c.krs = 1 ORDER BY e.nim");

        return $daftar_mahasiswa;
    }

    public function persen_nilai_mk(Request $request)
    {
        $persen_nilai_mk = DB::select("SELECT 
                a.nilai_akhir_huruf, 
                COUNT(a.nilai_akhir_huruf) AS jumlah,
                (COUNT(a.nilai_akhir_huruf) * 100.0 / (SELECT COUNT(*) FROM akd_detail_krs WHERE id_kelas='671')) AS persen
            FROM akd_detail_krs a
            JOIN akd_krs b ON a.id_krs = b.id_krs 
            JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
            WHERE a.id_kelas = '" . $request->id_kelas . "' AND c.krs = 1
            GROUP BY a.nilai_akhir_huruf
            ORDER BY persen DESC;");

        return $persen_nilai_mk;
    }

    public function select_predikat_nilai_huruf(Request $request)
    {

        $select_predikat_nilai_huruf = DB::select("SELECT * FROM akd_predikat_nilai_huruf WHERE nilai_huruf_akhir like '%{$request->search}%' ORDER BY nilai_huruf_akhir ASC");

        if (!empty($select_predikat_nilai_huruf[0]->nilai_huruf_akhir)) {
            foreach ($select_predikat_nilai_huruf as $namaselect_predikat_nilai_huruf) {
                $select_predikat_nilai_hurufArray[] = array(
                    "id" => $namaselect_predikat_nilai_huruf->nilai_huruf_akhir,
                    "text" => $namaselect_predikat_nilai_huruf->nilai_huruf_akhir
                );
            }
        } else {
            $select_predikat_nilai_hurufArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_predikat_nilai_hurufArray]);
    }

    public function simpan_nilai_uts(Request $request)
    {
        if (count($request->id_detail_krs) > 0) {
            // var_dump($request);
            foreach ($request->id_detail_krs as $item => $v) {
                $id_detail_krs = $request->id_detail_krs[$item];

                DB::table('akd_detail_krs')
                    ->where('id_detail_krs', $id_detail_krs)
                    ->update([
                        'nilai_uts'  =>  $request->nilai_uts[$item],
                        'dtime_update'  =>  date('Y-m-d H:i:s')
                    ]);
            }
        }
    }
    public function simpan_nilai_uas(Request $request)
    {
        if (count($request->id_detail_krs) > 0) {
            // $cek = [];
            foreach ($request->id_detail_krs as $item => $v) {
                $id_detail_krs = $request->id_detail_krs[$item];

                if (!empty($request->nilai_akhir_huruf[$item])) {
                    $nilai_akhir_huruf = $request->nilai_akhir_huruf[$item];
                    $nilai = collect(DB::select("SELECT mutu FROM akd_predikat_nilai_huruf WHERE nilai_huruf_akhir = '" . $nilai_akhir_huruf . "'"));
                    $nilaimutu = $nilai->first()->mutu;
                } else {
                    $nilaimutu = '';
                }
                DB::table('akd_detail_krs')
                    ->where('id_detail_krs', $id_detail_krs)
                    ->update([
                        'nilai_akhir_huruf'  =>  $request->nilai_akhir_huruf[$item],
                        'nilai_akhir_angka'  =>  $nilaimutu,
                        'dtime_update'  =>  date('Y-m-d H:i:s')
                    ]);

                //transkrip
                $cek_nilai = DB::select("SELECT id_transkrip, id_matakuliah, tahun_kurikulum from akd_transkrip where nim='" . $request->nim[$item] . "' and id_matakuliah='" . $request->id_matakuliah[$item] . "' and tahun_kurikulum='" . $request->tahun_kurikulum[$item] . "'");
                if ($request->nilai_akhir_huruf[$item] == "" || $request->nilai_akhir_huruf[$item] == null) {
                    //cek null
                } else {
                    if (count($cek_nilai) > 0) {
                        DB::table('akd_transkrip')
                            ->where('nim', $request->nim[$item])
                            ->where('id_matakuliah', $request->id_matakuliah[$item])
                            ->where('tahun_kurikulum', $request->tahun_kurikulum[$item])
                            ->update([
                                'nilai'  =>  $request->nilai_akhir_huruf[$item]
                            ]);
                    } else {
                        DB::table('akd_transkrip')->insert([
                            'nim'  =>  $request->nim[$item],
                            'id_matakuliah'  =>  $request->id_matakuliah[$item],
                            'tahun_kurikulum'  =>  $request->tahun_kurikulum[$item],
                            'nilai'  =>  $request->nilai_akhir_huruf[$item]
                        ]);
                    }
                }
            }
        }
    }


    public function simpan_presensi_mhs(Request $request)
    {

        $nimarray                     = $request->nim;
        $absenarray                   = $request->status;
        $id_ba = $request->berita_acara;

        $jml = count($absenarray);
        $nimhadir = '';
        $nimsakit = '';
        $nimijin = '';
        $nimalpha = '';
        for ($i = 0; $i < $jml; $i++) {
            if ($absenarray[$i] == 'Hadir') {
                $nimhadir = $nimhadir . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'Sakit') {
                $nimsakit = $nimsakit . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'Ijin') {
                $nimijin = $nimijin . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'Alpha') {
                $nimalpha = $nimalpha . '#' . $nimarray[$i];
            }
        }

        $ambil_ba = collect(DB::select("SELECT * FROM akd_berita_acara WHERE id = '" . $id_ba . "'"))->first();
        $tglJamnow = $ambil_ba->tgl;
        $pertemuan = $ambil_ba->pertemuan_ke;

        $list_presensi = collect(DB::select("SELECT * FROM akd_presensi_mhs WHERE id_kelas = '$request->id_kls_presensi' AND pertemuan = '" . $pertemuan . "' "))->count();

        if ($list_presensi > 0) {
            return response()->json(['error' => 'Duplikasi data pertemuan pada tanggal yang sama, cek List Presensi !']);
        } else {
            DB::beginTransaction();
            try {

                $id = DB::table('akd_presensi_mhs')->insertGetId([
                    'id_kelas'  =>  $request->id_kls_presensi,
                    'tgl'  =>  $tglJamnow,
                    'pertemuan' => $pertemuan,
                    'hadir' => substr($nimhadir, 1),
                    'sakit' => substr($nimsakit, 1),
                    'ijin'  => substr($nimijin, 1),
                    'alpha' => substr($nimalpha, 1)
                ]);

                $hitunghadir = collect(DB::select("SELECT hadir FROM akd_presensi_mhs WHERE id = '$id' "));

                $hadirArray = explode("#", trim($hitunghadir, '#'));
                $hasil = count($hadirArray);

                DB::table('akd_berita_acara')
                    ->where('id', $id_ba)
                    ->update([
                        'peserta_hadir' => $hasil
                    ]);


                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            }
            return response()->json(['success' => 'Data Presensi berhasil ditambahkan !']);
        }
    }
    public function edit_presensi_mhs(Request $request)
    {
        $nimarray                     = $request->nim;
        $absenarray                   = $request->status;

        $jml = count($absenarray);
        $nimhadir = '';
        $nimsakit = '';
        $nimijin = '';
        $nimalpha = '';
        for ($i = 0; $i < $jml; $i++) {
            if ($absenarray[$i] == 'Hadir') {
                $nimhadir = $nimhadir . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'Sakit') {
                $nimsakit = $nimsakit . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'Ijin') {
                $nimijin = $nimijin . '#' . $nimarray[$i];
            } else if ($absenarray[$i] == 'Alpha') {
                $nimalpha = $nimalpha . '#' . $nimarray[$i];
            }
        }
        DB::beginTransaction();
        try {
            $edit_presensi_mhs = DB::table('akd_presensi_mhs')
                ->where('id', $request->id_presensi)
                ->update([
                    'hadir' => substr($nimhadir, 1),
                    'sakit' => substr($nimsakit, 1),
                    'ijin'  => substr($nimijin, 1),
                    'alpha' => substr($nimalpha, 1)
                ]);

            $hitunghadir = collect(DB::select("SELECT hadir FROM akd_presensi_mhs WHERE id = '" . $request->id_presensi . "'"));
            $hadirArray = explode("#", trim($hitunghadir, '#'));
            $hasil = count($hadirArray); //hasil hadir

            $for_ba = collect(DB::select("SELECT id_kelas, tgl, pertemuan, hadir FROM akd_presensi_mhs WHERE id = '" . $request->id_presensi . "' "))->first(); //kondisi ba

            $queryba = collect(DB::select("SELECT * FROM akd_berita_acara WHERE id_kelas = '" . $for_ba->id_kelas . "' AND tgl = '" . $for_ba->tgl . "' AND pertemuan_ke = '" . $for_ba->pertemuan . "'"))->first(); // pertemuan dan tgl BA dan presensi wajib sama
            if (!$queryba) {
                DB::rollBack();
                return response()->json(['error' => 'Data Berita Acara tidak ditemukan. ']);
            }

            DB::table('akd_berita_acara')
                ->where('id', $queryba->id)
                ->update([
                    'peserta_hadir' => $hasil
                ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
        return response()->json(['success' => 'Data Presensi berhasil diubah !']);
    }


    public function hapus_presensi(Request $request)
    {
        $hapus_presensi = DB::table('akd_presensi_mhs')->where('id', $request->id)->delete();
        return $hapus_presensi;
    }

    public function data_hitung_presensi(Request $request)
    {

        $absensi = DB::select("SELECT * FROM akd_presensi_mhs WHERE id_kelas = '" . $request->id_kelas . "' ORDER BY pertemuan ASC");
        return $absensi;
    }
    public function data_lihat_mhs_presensi(Request $request)
    {

        $data_lihat_mhs_presensi = DB::select("SELECT nim, nama_mahasiswa FROM akd_mahasiswa WHERE nim IN (" . $request->nim . ")");
        return $data_lihat_mhs_presensi;
    }

    public function home_kalenderakademik(Request $request)
    {
        $home_kalenderakademik = DB::select("SELECT kode_kegiatan_akademik,nama_kegiatan,tahun, semester, DATE_FORMAT(tanggal_mulai,'%d-%m-%Y') AS tanggal_mulailook, DATE_FORMAT(tanggal_akhir,'%d-%m-%Y') AS tanggal_akhirlook, background from akd_kalender_akademik where tahun='$request->tahun' and semester='$request->semester' ORDER BY tanggal_mulai ASC");
        return $home_kalenderakademik;
    }
    public function home_kalenderakademikbase(Request $request)
    {
        $home_kalenderakademik = DB::select("SELECT kode_kegiatan_akademik,nama_kegiatan,tahun, semester,tanggal_mulai,tanggal_akhir, background from akd_kalender_akademik where tahun='$request->tahun' and semester='$request->semester' ORDER BY tanggal_mulai ASC");
        return $home_kalenderakademik;
    }


    public function tahunajaran()
    {
        $tahunajaran = DB::select("SELECT * FROM akd_mreg ORDER BY tahun DESC,semester DESC");
        return $tahunajaran;
    }

    public function simpan_tahunajaran(Request $request)
    {

        $simpantahunajaran = DB::table('akd_mreg')->insert([
            'tahun'  =>  $request->tahun,
            'semester'  =>  $request->semester,
            'tahun_akademik'  =>  $request->tahun_akademik,
            'trash'  =>  $request->trash
        ]);
        return $simpantahunajaran;
    }

    public function edit_tahunajaran(Request $request)
    {
        $editstatus = DB::table('akd_mreg')
            ->update([
                'trash'  =>  '0'
            ]);

        $edittahunajaran = DB::table('akd_mreg')
            ->where('id_mreg', $request->id_mreg)
            ->update([
                'tahun'  =>  $request->etahun,
                'semester'  =>  $request->esemester,
                'tahun_akademik'  =>  $request->etahunakademik,
                'trash'  =>  $request->etrash
            ]);
        return $edittahunajaran;
    }

    public function hapus_tahunajaran(Request $request)
    {
        $hapustahunajaran = DB::table('akd_mreg')->where('id_mreg', $request->id_mreg)->delete();
        return $hapustahunajaran;
    }

    public function ubahstatus_tahunajaran(Request $request)
    {
        $editstatus = DB::table('akd_mreg')
            ->update([
                'trash'  =>  '0'
            ]);

        $ubahstatustahunajaran = DB::table('akd_mreg')
            ->where('id_mreg', $request->id_mreg)
            ->update([
                'trash'  =>  $request->send_value
            ]);
        return $ubahstatustahunajaran;
    }

    // makul prasyarat
    public function dropdown_prodifakultas()
    {
        $dropdown_prodifakultas = DB::select("SELECT * FROM akd_program_studi LEFT JOIN akd_fakultas ON akd_program_studi.kode_fakultas=akd_fakultas.kode_fakultas");
        return $dropdown_prodifakultas;
    }
    public function dropdown_prodi()
    {
        $dropdown_prodi = DB::select("SELECT * FROM akd_program_studi ORDER BY id_program_studi ASC");
        return $dropdown_prodi;
    }
    public function data_makulprasyarat()
    {
        $makulprasyarat = DB::select("SELECT id_prasyarat, akd_prasyarat_matakuliah.id_matakuliah, id_matakuliah_prasyarat, mk1.kode_program_studi, mk1.kode_matakuliah AS kode_matakuliah_child, mk1.nama_matakuliah AS nama_matakuliah_child, 
        mk2.kode_matakuliah AS kode_matakuliah_parent, mk2.nama_matakuliah AS nama_matakuliah_parent
        FROM akd_prasyarat_matakuliah 
                JOIN akd_matakuliah AS mk1 ON akd_prasyarat_matakuliah.id_matakuliah = mk1.id_matakuliah
                JOIN akd_matakuliah AS mk2 ON akd_prasyarat_matakuliah.id_matakuliah_prasyarat = mk2.id_matakuliah
                ORDER BY id_prasyarat ASC");
        return $makulprasyarat;
    }

    public function simpan_makulprasyarat(Request $request)
    {
        if (count($request->makul) > 0) {
            foreach ($request->makul as $item => $v) {
                $simpan_makulprasyarat = DB::table('akd_prasyarat_matakuliah')->insert([
                    'id_matakuliah'  =>  $request->makul[$item],
                    'id_matakuliah_prasyarat'  =>  $request->makul_prasyarat[$item]
                ]);
            }
        }
        return $simpan_makulprasyarat;
    }


    public function edit_makulprasyarat(Request $request)
    {
        $editmakulprasyarat = DB::table('akd_prasyarat_matakuliah')
            ->where('id_prasyarat', $request->id_prasyarat)
            ->update([
                'id_matakuliah'  =>  $request->emakul,
                'id_matakuliah_prasyarat'  =>  $request->emakul_prasyarat
            ]);
        return $editmakulprasyarat;
    }

    public function hapus_makulprasyarat(Request $request)
    {
        $hapusmakulprasyarat = DB::table('akd_prasyarat_matakuliah')->where('id_prasyarat', $request->id)->delete();
        return $hapusmakulprasyarat;
    }

    public function select_makul(Request $request)
    {

        $select_makul = DB::select("SELECT * FROM akd_matakuliah WHERE kode_program_studi = '$request->kode_prodi' and nama_matakuliah like '%{$request->search}%' and tahun_kurikulum!='2015'");

        if (!empty($select_makul[0]->id_matakuliah)) {
            foreach ($select_makul as $namaselect_makul) {
                $matkul = $namaselect_makul->tahun_kurikulum . " " . $namaselect_makul->nama_matakuliah;
                $select_makulArray[] = array(
                    "id" => $namaselect_makul->id_matakuliah,
                    "text" => $matkul
                );
            }
        } else {
            $select_makulArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_makulArray]);
    }

    public function select_dosen(Request $request)
    {

        $select_dosen = DB::select("SELECT * FROM simpeg_pegawai WHERE nama like '%{$request->search}%'");

        if (!empty($select_dosen[0]->id)) {
            foreach ($select_dosen as $namaselect_dosen) {
                $select_dosenArray[] = array(
                    "id" => $namaselect_dosen->id,
                    "text" => $namaselect_dosen->nidn . " " . $namaselect_dosen->nama
                );
            }
        } else {
            $select_dosenArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_dosenArray]);
        // return $select_makul;
    }

    public function select_tahunakademik(Request $request)
    {

        $select_tahunakademik = DB::select("SELECT * FROM
        (
        SELECT akd_mreg.*, IF(semester='1', CONCAT_WS(' ', tahun_akademik, 'Ganjil'), CONCAT_WS(' ', tahun_akademik, 'Genap')) AS tahun_ajaran
        FROM akd_mreg ORDER BY tahun DESC
        ) ta
        WHERE tahun_ajaran like '%{$request->search}%' ORDER BY tahun DESC");

        if (!empty($select_tahunakademik[0]->id_mreg)) {
            foreach ($select_tahunakademik as $namaselect_tahunakademik) {
                $select_tahunakademikArray[] = array(
                    "id" => $namaselect_tahunakademik->id_mreg,
                    "text" => $namaselect_tahunakademik->tahun_ajaran
                );
            }
        } else {
            $select_tahunakademikArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_tahunakademikArray]);
        // return $select_tahunakademik;
    }

    public function data_makul_ba(Request $request)
    {

        $makulpenawaran = DB::select("SELECT akd_penawaran_matakuliah.*, akd_matakuliah.*, akd_kelas_kuliah.*, akd_program_studi.*,
            CONCAT_WS('-',DATE_FORMAT(jam_mulai,'%H:%i'), DATE_FORMAT(jam_selesai,'%H:%i')) AS waktu, simpeg_pegawai.*, IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
            CONCAT_WS('', gelar_depan, nama, gelar_belakang) AS fullname,
            (SELECT COUNT(id) AS total_ba FROM akd_berita_acara WHERE id_kelas = akd_kelas_kuliah.id_kelas) AS total_ba,akd_matakuliah.smt_matakuliah AS smtmatkul
            FROM akd_kelas_kuliah
            JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            JOIN simpeg_pegawai ON akd_penawaran_matakuliah.kode_dosen = simpeg_pegawai.id
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND (akd_penawaran_matakuliah.kode_dosen = '" . $request->kode_dosen . "' OR akd_penawaran_matakuliah.kode_dosen2 = '" . $request->kode_dosen . "')");
   
        return $makulpenawaran;
    }

    public function data_makul_ba_ujian(Request $request)
    {

            $makulpenawaran = DB::select("SELECT akd_penawaran_matakuliah.*, akd_matakuliah.*, akd_kelas_kuliah.*, akd_program_studi.*,
            CONCAT_WS('-',DATE_FORMAT(jam_mulai,'%H:%i'), DATE_FORMAT(jam_selesai,'%H:%i')) AS waktu, simpeg_pegawai.*, IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
            CONCAT_WS('', gelar_depan, nama, gelar_belakang) AS fullname,
            (SELECT COUNT(id_ba_ujian) FROM akd_berita_acara_ujian WHERE id_kelas=akd_kelas_kuliah.id_kelas AND jenis_ujian = 'uts') AS bt_uts,
            (SELECT COUNT(id_ba_ujian) FROM akd_berita_acara_ujian WHERE id_kelas=akd_kelas_kuliah.id_kelas AND jenis_ujian = 'uas') AS bt_uas
            FROM akd_kelas_kuliah
            JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            JOIN simpeg_pegawai ON akd_penawaran_matakuliah.kode_dosen = simpeg_pegawai.id
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND (akd_penawaran_matakuliah.kode_dosen = '" . $request->kode_dosen . "' OR akd_penawaran_matakuliah.kode_dosen2 = '" . $request->kode_dosen . "')");
        
        return $makulpenawaran;
    }

    public function data_makul_ba_ujian_kaprodi(Request $request)
    {
        $query = "SELECT akd_matakuliah.kode_matakuliah, 
                    akd_matakuliah.nama_matakuliah, 
                    akd_matakuliah.smt_matakuliah, 
                    akd_matakuliah.sks_matakuliah, 
                    IF(akd_penawaran_matakuliah.semester = '1', 
                        CONCAT_WS('', 'Ganjil ', CONCAT_WS('/', akd_penawaran_matakuliah.tahun, akd_penawaran_matakuliah.tahun+1)), 
                        CONCAT_WS('', 'Genap ', CONCAT_WS('/', akd_penawaran_matakuliah.tahun, akd_penawaran_matakuliah.tahun+1))
                    ) AS tahun_akademik,
                    akd_kelas_kuliah.nama_kelas, 
                    CONCAT_WS('', simpeg_pegawai.gelar_depan, simpeg_pegawai.nama, simpeg_pegawai.gelar_belakang) AS fullname,
                    (SELECT COUNT(id_ba_ujian) 
                     FROM akd_berita_acara_ujian 
                     WHERE id_kelas = akd_kelas_kuliah.id_kelas 
                     AND jenis_ujian = 'uas') AS ba_ujian,
             		(SELECT CASE WHEN COUNT(nilai_akhir_angka) > 0 THEN 1 ELSE 0 END AS status_nilai FROM akd_detail_krs WHERE id_kelas = akd_kelas_kuliah.id_kelas GROUP BY id_kelas) AS penilaian,
                    CONCAT_WS('', dosen1.gelar_depan, dosen1.nama, dosen1.gelar_belakang) AS dosen1,
                    CONCAT_WS('', dosen2.gelar_depan, dosen2.nama, dosen2.gelar_belakang) AS dosen2
                FROM 
                (SELECT id_kelas
                 FROM akd_detail_krs a 
                 JOIN akd_krs b ON a.id_krs=b.id_krs 
                 JOIN akd_heregistrasi c ON b.id_heregistrasi = c.id_heregistrasi
                 WHERE tahun = ? AND semester = ? AND c.krs = 1 
                 GROUP BY a.id_kelas) data_krs 
                JOIN akd_kelas_kuliah 
                    ON akd_kelas_kuliah.id_kelas = data_krs.id_kelas
                JOIN akd_penawaran_matakuliah 
                    ON akd_penawaran_matakuliah.id_tawar = akd_kelas_kuliah.id_tawar
                JOIN akd_matakuliah 
                    ON akd_penawaran_matakuliah.id_matakuliah = akd_matakuliah.id_matakuliah
                JOIN akd_program_studi 
                    ON akd_penawaran_matakuliah.kode_program_studi = akd_program_studi.kode_program_studi 
                LEFT JOIN simpeg_pegawai dosen1 
                    ON akd_penawaran_matakuliah.kode_dosen = dosen1.id
                LEFT JOIN simpeg_pegawai dosen2 
                    ON akd_penawaran_matakuliah.kode_dosen2 = dosen2.id
                JOIN simpeg_pegawai 
                    ON akd_penawaran_matakuliah.kode_dosen = simpeg_pegawai.id
                WHERE akd_penawaran_matakuliah.tahun = ? 
                AND akd_penawaran_matakuliah.semester = ?";
        
        // Inisialisasi parameter
        $params = [$request->tahun, $request->semester, $request->tahun, $request->semester];
        
        // Tambahkan kondisi jika $request->kaprodi tidak null
        if (!is_null($request->kaprodi)) {
            $query .= " AND akd_program_studi.kode_program_studi = ?";
            $params[] = $request->kode_prodi;
        }
        
        // Eksekusi query dengan parameter binding untuk keamanan
        $data_makul_ba_ujian_kaprodi = DB::select($query, $params);

        // var_dump($data_makul_ba_ujian_kaprodi);
        return $data_makul_ba_ujian_kaprodi;
    }


    public function rekap_ba(Request $request)
    {
        $rekap_ba = DB::select("SELECT akd_penawaran_matakuliah.*, akd_matakuliah.*, akd_kelas_kuliah.*, akd_program_studi.*,
        CONCAT_WS('-',DATE_FORMAT(jam_mulai,'%H:%i'), DATE_FORMAT(jam_selesai,'%H:%i')) AS waktu, simpeg_pegawai.*, 
        IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , 
        CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
        CONCAT_WS('', gelar_depan, nama, gelar_belakang) AS fullname,
        (SELECT COUNT(id) AS total_ba FROM akd_berita_acara WHERE id_kelas = akd_kelas_kuliah.id_kelas) AS ba_kehadiran,
        (SELECT COUNT(id_ba_ujian) AS total_uts FROM akd_berita_acara_ujian WHERE jenis_ujian = 'uts' AND id_kelas = akd_kelas_kuliah.id_kelas) AS uts,
        (SELECT COUNT(id_ba_ujian) AS total_uas FROM akd_berita_acara_ujian WHERE jenis_ujian = 'uas' AND id_kelas = akd_kelas_kuliah.id_kelas) AS uas
        FROM akd_kelas_kuliah
            JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            JOIN simpeg_pegawai ON akd_penawaran_matakuliah.kode_dosen = simpeg_pegawai.id
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "'");

        return $rekap_ba;
    }

    public function data_makulpenawaran(Request $request)
    {

        if ($request->tipe == 'Dekanat') {
            $makulpenawaran = DB::select("SELECT akd_penawaran_matakuliah.*, akd_matakuliah.*, akd_kelas_kuliah.*, akd_program_studi.*,
            CONCAT_WS('-',DATE_FORMAT(jam_mulai,'%H:%i'), DATE_FORMAT(jam_selesai,'%H:%i')) AS waktu,CONCAT_WS('-',DATE_FORMAT(ujian_jam_mulai,'%H:%i'), DATE_FORMAT(ujian_jam_selesai,'%H:%i')) AS ujianwaktu, simpeg_pegawai.*, 
            CONCAT_WS('', dosen1.gelar_depan, dosen1.nama, dosen1.gelar_belakang) AS dosen1,
            CONCAT_WS('', dosen2.gelar_depan, dosen2.nama, dosen2.gelar_belakang) AS dosen2
            FROM akd_penawaran_matakuliah
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_kelas_kuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            LEFT JOIN simpeg_pegawai dosen1 ON akd_penawaran_matakuliah.kode_dosen=dosen1.id
            LEFT JOIN simpeg_pegawai dosen2 ON akd_penawaran_matakuliah.kode_dosen2=dosen2.id
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND akd_program_studi.kode_fakultas = '" . $request->kode_fakultas . "' AND akd_program_studi.nama_program_studi LIKE '%" . $request->nama_program_studi . "%'");
        } else if ($request->tipe == 'Dosen') {
            $makulpenawaran = DB::select("SELECT akd_penawaran_matakuliah.*, akd_matakuliah.*, akd_kelas_kuliah.*, akd_program_studi.*,
            CONCAT_WS('-',DATE_FORMAT(jam_mulai,'%H:%i'), DATE_FORMAT(jam_selesai,'%H:%i')) AS waktu,CONCAT_WS('-',DATE_FORMAT(ujian_jam_mulai,'%H:%i'), DATE_FORMAT(ujian_jam_selesai,'%H:%i')) AS ujianwaktu, simpeg_pegawai.*, IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
            CONCAT_WS('', gelar_depan, nama, gelar_belakang) AS fullname
            FROM akd_kelas_kuliah
            JOIN akd_penawaran_matakuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            JOIN simpeg_pegawai ON akd_penawaran_matakuliah.kode_dosen = simpeg_pegawai.id
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND (akd_penawaran_matakuliah.kode_dosen = '" . $request->kode_dosen . "' OR akd_penawaran_matakuliah.kode_dosen2 = '" . $request->kode_dosen . "') AND akd_program_studi.nama_program_studi LIKE '%" . $request->nama_program_studi . "%' ");
        } else if ($request->tipe == '0') {
            $makulpenawaran = DB::select("SELECT akd_penawaran_matakuliah.*, akd_matakuliah.*, akd_kelas_kuliah.*, akd_program_studi.*,
            CONCAT_WS('-',DATE_FORMAT(jam_mulai,'%H:%i'), DATE_FORMAT(jam_selesai,'%H:%i')) AS waktu,CONCAT_WS('-',DATE_FORMAT(ujian_jam_mulai,'%H:%i'), DATE_FORMAT(ujian_jam_selesai,'%H:%i')) AS ujianwaktu, dosen1.id AS id_dosen1,dosen1.nidn AS nidn_dosen1,dosen1.nama AS nama_dosen1,dosen1.gelar_depan AS gelar_depan_dosen1,
            dosen1.gelar_belakang AS gelar_belakang_dosen1,dosen2.id AS id_dosen2,dosen2.nidn AS nidn_dosen2,dosen2.nama AS nama_dosen2,dosen2.gelar_depan AS gelar_depan_dosen2,
            dosen2.gelar_belakang AS gelar_belakang_dosen2,  IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , 
            CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
            CONCAT_WS('', dosen1.gelar_depan, dosen1.nama, dosen1.gelar_belakang) AS dosen1,
            CONCAT_WS('', dosen2.gelar_depan, dosen2.nama, dosen2.gelar_belakang) AS dosen2
            FROM akd_penawaran_matakuliah
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_kelas_kuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            LEFT JOIN simpeg_pegawai dosen1 ON akd_penawaran_matakuliah.kode_dosen=dosen1.id
            LEFT JOIN simpeg_pegawai dosen2 ON akd_penawaran_matakuliah.kode_dosen2=dosen2.id
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "'  AND akd_program_studi.nama_program_studi LIKE '%" . $request->nama_program_studi . "%' ");
        } else {
            $makulpenawaran = DB::select("SELECT akd_penawaran_matakuliah.*, akd_matakuliah.*, akd_kelas_kuliah.*, akd_program_studi.*,
            CONCAT_WS('-',DATE_FORMAT(jam_mulai,'%H:%i'), DATE_FORMAT(jam_selesai,'%H:%i')) AS waktu,CONCAT_WS('-',DATE_FORMAT(ujian_jam_mulai,'%H:%i'), DATE_FORMAT(ujian_jam_selesai,'%H:%i')) AS ujianwaktu, dosen1.id AS id_dosen1,dosen1.nidn AS nidn_dosen1,dosen1.nama AS nama_dosen1,dosen1.gelar_depan AS gelar_depan_dosen1,
            dosen1.gelar_belakang AS gelar_belakang_dosen1,dosen2.id AS id_dosen2,dosen2.nidn AS nidn_dosen2,dosen2.nama AS nama_dosen2,dosen2.gelar_depan AS gelar_depan_dosen2,
            dosen2.gelar_belakang AS gelar_belakang_dosen2,  IF(akd_penawaran_matakuliah.semester = '1', CONCAT_WS('','Ganjil ', CONCAT_WS('/',tahun, tahun+1) ) , 
            CONCAT_WS('','Genap ', CONCAT_WS('/',tahun, tahun+1))) AS tahun_akademik,
            CONCAT_WS('', dosen1.gelar_depan, dosen1.nama, dosen1.gelar_belakang) AS dosen1,
            CONCAT_WS('', dosen2.gelar_depan, dosen2.nama, dosen2.gelar_belakang) AS dosen2
            FROM akd_penawaran_matakuliah
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_kelas_kuliah ON akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            LEFT JOIN simpeg_pegawai dosen1 ON akd_penawaran_matakuliah.kode_dosen=dosen1.id
            LEFT JOIN simpeg_pegawai dosen2 ON akd_penawaran_matakuliah.kode_dosen2=dosen2.id
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "'  AND akd_program_studi.nama_program_studi LIKE '%" . $request->nama_program_studi . "%' ");
        }

        return $makulpenawaran;
    }
    public function simpan_makulpenawaran(Request $request)
    {
        if (count($request->makul) > 0) {
            foreach ($request->makul as $item => $v) {
                if ($request->kode_dosen2 == null) {
                    $matkul = DB::table('akd_matakuliah')->where('id_matakuliah', '=', $request->makul[$item])->first();
                    $getID = DB::table('akd_penawaran_matakuliah')->insertGetId([
                        'tahun'  =>  $request->tahun,
                        'semester'  =>  $request->semester,
                        'id_matakuliah'  =>  $request->makul[$item],
                        'tahun_kurikulum'  =>  $matkul->tahun_kurikulum,
                        'sks_matakuliah'  =>  $matkul->sks_matakuliah,
                        'smt_matakuliah'  =>  $matkul->smt_matakuliah,
                        'kode_program_studi'  =>  $request->kode_prodi,
                        'kode_dosen'  =>  $request->kode_dosen[$item]
                    ]);

                    $getID = DB::table('akd_kelas_kuliah')->insert([
                        'id_tawar'  =>  $getID,
                        'nama_kelas'  =>  $request->kelas[$item],
                        'hari'  =>  $request->hari[$item],
                        'jam_mulai'  =>  $request->jam_mulai[$item],
                        'jam_selesai'  =>  $request->jam_selesai[$item],
                        'kode_ruang'  =>  $request->ruang[$item],
                        'kapasitas_ruang'  =>  $request->kapasitas[$item],
                        'kode_dosen'  =>  $request->kode_dosen[$item]
                    ]);
                } else {
                    $matkul = DB::table('akd_matakuliah')->where('id_matakuliah', '=', $request->makul[$item])->first();
                    $getID = DB::table('akd_penawaran_matakuliah')->insertGetId([
                        'tahun'  =>  $request->tahun,
                        'semester'  =>  $request->semester,
                        'id_matakuliah'  =>  $request->makul[$item],
                        'tahun_kurikulum'  =>  $matkul->tahun_kurikulum,
                        'sks_matakuliah'  =>  $matkul->sks_matakuliah,
                        'smt_matakuliah'  =>  $matkul->smt_matakuliah,
                        'kode_program_studi'  =>  $request->kode_prodi,
                        'kode_dosen'  =>  $request->kode_dosen[$item],
                        'kode_dosen2'  =>  $request->kode_dosen2[$item]
                    ]);

                    $getID = DB::table('akd_kelas_kuliah')->insert([
                        'id_tawar'  =>  $getID,
                        'nama_kelas'  =>  $request->kelas[$item],
                        'hari'  =>  $request->hari[$item],
                        'jam_mulai'  =>  $request->jam_mulai[$item],
                        'jam_selesai'  =>  $request->jam_selesai[$item],
                        'kode_ruang'  =>  $request->ruang[$item],
                        'kapasitas_ruang'  =>  $request->kapasitas[$item],
                        'kode_dosen'  =>  $request->kode_dosen[$item],
                        'kode_dosen2'  =>  $request->kode_dosen2[$item]
                    ]);
                }
            }
        }
    }
    
    public function update_url_rps(Request $request)
    {
        $update_url_rps = DB::table('akd_penawaran_matakuliah')
            ->where('id_tawar', $request->id_tawar)
            ->update([
                'url_rps'  =>  $request->url_rps
            ]);
        return $update_url_rps;
    }
    
    public function edit_makulpenawaran(Request $request)
    {
        DB::table('akd_penawaran_matakuliah')
            ->where('id_tawar', $request->id_tawar)
            ->update([
                'kode_dosen'  =>  $request->enama_dosen,
                'kode_dosen2'  =>  $request->enama_dosen2
            ]);

        DB::table('akd_kelas_kuliah')
            ->where('id_tawar', $request->id_tawar)
            ->where('id_kelas', $request->id_kelas)
            ->update([
                'nama_kelas'  =>  $request->enama_kelas,
                'hari'  =>  $request->ehari,
                'jam_mulai'  =>  $request->ejam_mulai,
                'jam_selesai'  =>  $request->ejam_selesai,
                'kode_ruang'  =>  $request->eruang,
                'kapasitas_ruang'  =>  $request->ekapasitas_ruang,
                'kode_dosen'  =>  $request->enama_dosen,
                'kode_dosen2'  =>  $request->enama_dosen2
            ]);

        // return $editmakulpenawaran;

        // $q_kl = "update akd_kelas_kuliah set nama_kelas='$kelas',hari='$hari',jam_mulai='$jam_mulai',jam_selesai='$jam_selesai',kode_ruang='$ruang',kapasitas_ruang='$kapasitas' where id_kelas='$id_kelas' and id_tawar='$id_tawar'";
        // $x_kl = $conn->query($q_kl);
    }

    public function edit_jadwalujian(Request $request)
    {

        $edit_jadwalujian = DB::table('akd_kelas_kuliah')
            ->where('id_tawar', $request->id_tawar)
            ->where('id_kelas', $request->id_kelas)
            ->update([
                'nama_kelas'  =>  $request->enama_kelas,
                'ujian_hari'  =>  $request->ehari,
                'ujian_tanggal'  =>  $request->etgl,
                'ujian_jam_mulai'  =>  $request->ejam_mulai,
                'ujian_jam_selesai'  =>  $request->ejam_selesai,
                'ujian_kode_ruang'  =>  $request->eruang,
            ]);

        return $edit_jadwalujian;
    }

    public function edit_makulpenawaran_dkn(Request $request)
    {
        DB::table('akd_penawaran_matakuliah')
            ->where('id_tawar', $request->id_tawar)
            ->update([
                'kode_dosen'  =>  $request->enama_dosen,
                'kode_dosen2'  =>  $request->enama_dosen2
            ]);

        $edit_makulpenawaran_dkn = DB::table('akd_kelas_kuliah')
            ->where('id_tawar', $request->id_tawar)
            ->where('id_kelas', $request->id_kelas)
            ->update([
                'nama_kelas'  =>  $request->enama_kelas,
                'hari'  =>  $request->ehari,
                'jam_mulai'  =>  $request->ejam_mulai,
                'jam_selesai'  =>  $request->ejam_selesai,
                'kode_ruang'  =>  $request->eruang,
                'kapasitas_ruang'  =>  $request->ekapasitas_ruang,
                'kode_dosen'  =>  $request->enama_dosen,
                'kode_dosen2'  =>  $request->enama_dosen2
            ]);
        return $edit_makulpenawaran_dkn;
    }


    public function hapus_makulpenawaran(Request $request)
    {
        $hapusmakulprasyarat = DB::table('akd_penawaran_matakuliah')->where('id_tawar', $request->id)->delete();
        $hapus = DB::table('akd_kelas_kuliah')->where('id_tawar', $request->id)->delete();
        return $hapusmakulprasyarat;
    }


    public function data_inputnilaikhs(Request $request)
    {
        $tahun = $request->tahun;
        $smt = $request->semester;
        $inputnilaikhs = DB::select("select * from akd_penawaran_matakuliah,akd_matakuliah,akd_kelas_kuliah,akd_program_studi where akd_penawaran_matakuliah.tahun='$tahun' and akd_penawaran_matakuliah.semester='$smt'
        and akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah and akd_penawaran_matakuliah.id_tawar=akd_kelas_kuliah.id_tawar and akd_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi");
        return $inputnilaikhs;
    }
    // Kegiatan Akademik
    public function kegiatanakademik()
    {
        $kegiatanakademik = DB::select("SELECT * FROM akd_kegiatan where trash='0' order by kode_kegiatan asc");
        return $kegiatanakademik;
    }

    public function simpan_kegiatanakademik(Request $request)
    {

        $simpankegiatanakademik = DB::table('akd_kegiatan')->insert([
            'nama_kegiatan'  =>  $request->nama_kegiatan,
            'trash'  =>  $request->trash
        ]);
        return $simpankegiatanakademik;
    }

    public function edit_kegiatanakademik(Request $request)
    {
        $editkegiatanakademik = DB::table('akd_kegiatan')
            ->where('kode_kegiatan', $request->kode_kegiatan)
            ->update([
                'nama_kegiatan'  =>  $request->enama_kegiatan,
                'trash'  =>  $request->etrash
            ]);
        return $editkegiatanakademik;
    }

    public function hapus_kegiatanakademik(Request $request)
    {
        $hapuskegiatanakademik = DB::table('akd_kegiatan')->where('kode_kegiatan', $request->kode_kegiatan)->delete();
        return $hapuskegiatanakademik;
    }

    public function ubahstatus_kegiatanakademik(Request $request)
    {
        $ubahstatuskegiatanakademik = DB::table('akd_kegiatan')
            ->where('kode_kegiatan', $request->kode_kegiatan)
            ->update([
                'trash'  =>  $request->send_value
            ]);
        return $ubahstatuskegiatanakademik;
    }
    // Fakultas
    public function fakultas()
    {
        $fakultas = DB::select("SELECT * FROM akd_fakultas a JOIN simpeg_pegawai b ON a.pimpinan=b.id where trash='0' order by id_fak asc");
        return $fakultas;
    }
    public function tampilpimpinan()
    {
        $tampilpimpinan = DB::select("SELECT * FROM simpeg_pegawai WHERE kode_jenis='1' order by nama asc");
        return $tampilpimpinan;
    }
    public function edittampilpimpinan()
    {
        $edittampilpimpinan = DB::select("SELECT * FROM simpeg_pegawai WHERE kode_jenis='1' order by nama asc");
        return $edittampilpimpinan;
    }
    public function tampiljenjang()
    {
        $tampiljenjang = DB::select("SELECT * FROM akd_jenjang_pendidikan order by pendidikan_id asc");
        return $tampiljenjang;
    }

    public function simpan_fakultas(Request $request)
    {

        $simpan_fakultas = DB::table('akd_fakultas')->insert([
            'kode_fakultas'  =>  $request->kode_fakultas,
            'nama_fakultas'  =>  $request->nama_fakultas,
            'pimpinan'  =>  $request->pimpinan,
            'pendidikan_id'  =>  $request->pendidikan_id,
            'plt'  =>  $request->plt,
            'trash'  =>  $request->trash
        ]);
        return $simpan_fakultas;
    }

    public function edit_fakultas(Request $request)
    {
        $editfakultas = DB::table('akd_fakultas')
            ->where('id_fak', $request->id_fak)
            ->update([
                'kode_fakultas'  =>  $request->ekode_fakultas,
                'nama_fakultas'  =>  $request->enama_fakultas,
                'pimpinan'  =>  $request->editpimpinan
            ]);
        return $editfakultas;
    }

    public function hapus_fakultas(Request $request)
    {
        $hapusfakultas = DB::table('akd_fakultas')->where('id_fak', $request->id_fak)->delete();
        return $hapusfakultas;
    }

    public function ubahstatus_fakultas(Request $request)
    {
        $ubahstatusfakultas = DB::table('akd_fakultas')
            ->where('id_fak', $request->id_fak)
            ->update([
                'trash'  =>  '1'
            ]);
        return $ubahstatusfakultas;
    }
    // Program Studi
    public function programstudi()
    {
        $programstudi = DB::select("SELECT * FROM akd_program_studi a JOIN simpeg_pegawai b ON a.pimpinan_prodi=b.id JOIN akd_fakultas c ON a.kode_fakultas=c.kode_fakultas order by a.id_program_studi asc");
        return $programstudi;
    }
    public function tampilfakultas()
    {
        $tampilfakultas = DB::select("SELECT * FROM akd_fakultas order by kode_fakultas asc");
        return $tampilfakultas;
    }

    public function simpan_programstudi(Request $request)
    {

        $simpanprogramstudi = DB::table('akd_program_studi')->insert([
            'kode_program_studi'  =>  $request->kode_program_studi,
            'kode_prodi_forlab'  =>  $request->kode_prodi_forlab,
            'kode_fakultas'  =>  $request->kode_fakultas,
            'pimpinan_prodi'  =>  $request->pimpinan_prodi,
            'nama_program_studi'  =>  $request->nama_program_studi,
            'ta_sks_minimal'  =>  $request->ta_sks_minimal,
            'ta_ada_sempro'  =>  $request->ta_ada_sempro,
            'ta_komponen_bayar'  =>  $request->ta_komponen_bayar,
            'ta_minimal_bimbingan'  =>  $request->ta_minimal_bimbingan
        ]);
        return $simpanprogramstudi;
    }

    public function edit_programstudi(Request $request)
    {
        // Build update data with only provided values
        $updateData = [
            'kode_program_studi'  =>  $request->ekode_program_studi,
            'kode_prodi_forlab'  =>  $request->ekode_prodi_forlab,
            'kode_fakultas'  =>  $request->ekode_fakultas,
            'pimpinan_prodi'  =>  $request->epimpinan_prodi,
            'nama_program_studi'  =>  $request->enama_program_studi
        ];
        
        // Only add TA configuration fields if they exist in database and are provided
        // Check which columns actually exist in the table
        $existingColumns = DB::getSchemaBuilder()->getColumnListing('akd_program_studi');
        
        if (in_array('ta_sks_minimal', $existingColumns) && $request->has('eta_sks_minimal') && $request->eta_sks_minimal !== null) {
            $updateData['ta_sks_minimal'] = $request->eta_sks_minimal;
        }
        
        if (in_array('ta_ada_sempro', $existingColumns) && $request->has('eta_ada_sempro') && $request->eta_ada_sempro !== null) {
            $updateData['ta_ada_sempro'] = $request->eta_ada_sempro;
        }
        
        if (in_array('ta_komponen_bayar', $existingColumns) && $request->has('eta_komponen_bayar') && $request->eta_komponen_bayar !== null) {
            $updateData['ta_komponen_bayar'] = $request->eta_komponen_bayar;
        }

        if (in_array('ta_komponen_bayar_ujian', $existingColumns) && $request->has('eta_komponen_bayar_ujian') && $request->eta_komponen_bayar_ujian !== null) {
            $updateData['ta_komponen_bayar_ujian'] = $request->eta_komponen_bayar_ujian;
        }

        if (in_array('ta_is_obe', $existingColumns) && $request->has('eta_is_obe') && $request->eta_is_obe !== null) {
            $updateData['ta_is_obe'] = $request->eta_is_obe;
        }
        
        if ($request->has('eta_minimal_bimbingan') && $request->eta_minimal_bimbingan !== null) {
            if (in_array('ta_minimal_bimbingan', $existingColumns)) {
                $updateData['ta_minimal_bimbingan'] = $request->eta_minimal_bimbingan;
            } elseif (in_array('ta_min_bimbingan', $existingColumns)) {
                $updateData['ta_min_bimbingan'] = $request->eta_minimal_bimbingan;
            }
        }
        
        $editprogramstudi = DB::table('akd_program_studi')
            ->where('id_program_studi', $request->id_program_studi)
            ->update($updateData);
            
        return $editprogramstudi;
    }

    public function hapus_programstudi(Request $request)
    {
        $hapusprogramstudi = DB::table('akd_program_studi')->where('id_program_studi', $request->id_program_studi)->delete();
        return $hapusprogramstudi;
    }

    public function ubahstatus_programstudi(Request $request)
    {
        $ubahstatusprogramstudi = DB::table('akd_program_studi')
            ->where('id_program_studi', $request->id_program_studi)
            ->update([
                'trash'  =>  $request->send_value
            ]);
        return $ubahstatusprogramstudi;
    }
    // Kurikulum
    public function kurikulum()
    {
        $kurikulum = DB::select("SELECT * FROM akd_kurikulum a JOIN akd_program_studi b ON a.kode_prodi=b.kode_program_studi ORDER BY a.id_kurikulum ASC");
        return $kurikulum;
    }

    public function select_kurikulum(Request $request)
    {

        $select_kurikulum = DB::select("SELECT * FROM akd_kurikulum WHERE kode_prodi = '$request->kode_prodi'");

        if (!empty($select_kurikulum[0]->tahun_kurikulum)) {
            foreach ($select_kurikulum as $namaselect_kurikulum) {
                $select_kurikulumArray[] = array(
                    "id" => $namaselect_kurikulum->tahun_kurikulum,
                    "text" => $namaselect_kurikulum->tahun_kurikulum
                );
            }
        } else {
            $select_kurikulumArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_kurikulumArray]);
        // return $select_kurikulum;
    }

    public function select_sifatmatakuliah()
    {

        $select_sifatmatakuliah = DB::select("SELECT * FROM akd_sifat_matakuliah");

        if (!empty($select_sifatmatakuliah[0]->nama_sifat_matakuliah)) {
            foreach ($select_sifatmatakuliah as $namaselect_sifatmatakuliah) {
                $select_sifatmatakuliahArray[] = array(
                    "id" => $namaselect_sifatmatakuliah->kode_sifat_matakuliah,
                    "text" => $namaselect_sifatmatakuliah->nama_sifat_matakuliah
                );
            }
        } else {
            $select_sifatmatakuliahArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_sifatmatakuliahArray]);
        // return $select_sifatmatakuliah;
    }

    public function tampilprogramstudi()
    {

        $tampilprogramstudi = DB::select("SELECT * FROM akd_program_studi order by kode_program_studi asc");

        return $tampilprogramstudi;
    }

    public function simpan_kurikulum(Request $request)
    {
        $simpankurikulum = DB::table('akd_kurikulum')->insert([
            'tahun_kurikulum'  =>  $request->tahun_kurikulum,
            'kode_prodi'  =>  $request->kode_prodi,
            'date'  =>  $request->date,
            'trash'  =>  $request->trash
        ]);
        return $simpankurikulum;
    }

    public function edit_kurikulum(Request $request)
    {
        $editkurikulum = DB::table('akd_kurikulum')
            ->where('id_kurikulum', $request->id_kurikulum)
            ->update([
                'tahun_kurikulum'  =>  $request->etahun_kurikulum,
                'kode_prodi'  =>  $request->ekode_prodi,
                'date'  =>  $request->edate,
                'trash'  =>  $request->etrash
            ]);
        return $editkurikulum;
    }

    public function hapus_kurikulum(Request $request)
    {
        $hapuskurikulum = DB::table('akd_kurikulum')->where('id_kurikulum', $request->id_kurikulum)->delete();
        return $hapuskurikulum;
    }

    public function ubahstatus_kurikulum(Request $request)
    {
        $ubahstatuskurikulum = DB::table('akd_kurikulum')
            ->where('id_kurikulum', $request->id_kurikulum)
            ->update([
                'trash'  =>  $request->send_value
            ]);
        return $ubahstatuskurikulum;
    }
    // Kalender Akademik
    public function kalenderakademik()
    {
        $kurikulum = DB::select("SELECT * FROM akd_kalender_akademik a JOIN akd_kegiatan b ON a.kode_kegiatan_akademik=b.kode_kegiatan ORDER BY a.id DESC");
        return $kurikulum;
    }
    public function tampilkegiatan()
    {
        $tampilkegiatan = DB::select("SELECT * FROM akd_kegiatan order by kode_kegiatan ASC");
        return $tampilkegiatan;
    }

    public function simpan_kalenderakademik(Request $request)
    {
        $kegiatan = DB::table('akd_kegiatan')->where('kode_kegiatan', '=', $request->kode_kegiatan_akademik)->first();
        $simpankalenderakademik = DB::table('akd_kalender_akademik')->insert([
            'kode_kegiatan_akademik'  =>  $request->kode_kegiatan_akademik,
            'nama_kegiatan'  =>  $kegiatan->nama_kegiatan,
            'tanggal_mulai'  =>  $request->tanggal1,
            'tanggal_akhir'  =>  $request->tanggal2,
            'tahun'  =>  $request->tahun,
            'semester'  =>  $request->semester,
            'background' => $request->background
        ]);
        return $simpankalenderakademik;
    }

    public function edit_kalenderakademik(Request $request)
    {
        $editkalenderakademik = DB::table('akd_kalender_akademik')
            ->where('id', $request->id_kalender)
            ->update([
                'kode_kegiatan_akademik'  =>  $request->ekode_kegiatan_akademik,
                'nama_kegiatan'  =>  $request->enama_kegiatan,
                'tanggal_mulai'  =>  $request->etanggal1,
                'tanggal_akhir'  =>  $request->etanggal2
            ]);
        return $editkalenderakademik;
    }

    public function hapus_kalenderakademik(Request $request)
    {
        $hapuskalenderakademik = DB::table('akd_kalender_akademik')->where('id', $request->id)->delete();
        return $hapuskalenderakademik;
    }

    public function ubahstatus_kalenderakademik(Request $request)
    {
        $ubahstatuskalenderakademik = DB::table('akd_kalender_akademik')
            ->where('id', $request->id_kalender)
            ->update([
                'trash'  =>  $request->send_value
            ]);
        return $ubahstatuskalenderakademik;
    }
    // Mata Kuliah
    public function matakuliah(Request $request = null)
    {
        $query = "SELECT *, a.kode_program_studi AS kode_prodi FROM akd_matakuliah a JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi";
        $conditions = [];
        if ($request) {
            if ($request->has('kode_prodi') && !empty($request->kode_prodi)) {
                $conditions[] = "a.kode_program_studi = '" . $request->kode_prodi . "'";
            }
            if ($request->has('tahun_kurikulum') && !empty($request->tahun_kurikulum)) {
                $conditions[] = "a.tahun_kurikulum = '" . $request->tahun_kurikulum . "'";
            }
        }
        if (count($conditions) > 0) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY a.id_matakuliah DESC";

        $matakuliah = DB::select($query);
        return $matakuliah;
    }


    public function simpan_matakuliah(Request $request)
    {
        if (count($request->kode_matakuliah) > 0) {
            foreach ($request->kode_matakuliah as $item => $v) {
                $simpanmatakuliah = DB::table('akd_matakuliah')->insert([
                    'tahun_kurikulum'  =>  $request->tahun_kurikulum[$item],
                    'kode_matakuliah'  =>  $request->kode_matakuliah[$item],
                    'nama_matakuliah'  =>  $request->nama_matakuliah[$item],
                    'nama_matakuliah_inggris'  =>  $request->nama_matakuliah_inggris[$item],
                    'sks_teori'  =>  $request->skst_matakuliah[$item],
                    'sks_praktikum'  =>  $request->sksp_matakuliah[$item],
                    'sks_matakuliah'  =>  $request->sks_matakuliah[$item],
                    'smt_matakuliah'  =>  $request->smt_matakuliah[$item],
                    'kode_sifat_matakuliah'  =>  $request->kode_sifat_matakuliah[$item],
                    'kode_program_studi'  =>  $request->kode_prodi[$item],
                    'kode_fakultas'  =>  $request->kode_fakultas[$item],
                    'kode_bayar'  =>  $request->kode_bayar[$item]
                ]);
            }
        }
        return $simpanmatakuliah;
    }


    public function edit_matakuliah(Request $request)
    {
        $editmakul = DB::table('akd_matakuliah')
            ->where('id_matakuliah', $request->eid_matakuliah)
            ->update([
                'tahun_kurikulum'  =>  $request->ekurikulum,
                'nama_matakuliah'  =>  $request->enama_matakuliah,
                'nama_matakuliah_inggris'  =>  $request->enama_matakuliah_inggris,
                'sks_teori'  =>  $request->eskst_matakuliah,
                'sks_praktikum'  =>  $request->esksp_matakuliah,
                'sks_matakuliah'  =>  $request->esks_matakuliah,
                'smt_matakuliah'  =>  $request->esemester,
                'kode_sifat_matakuliah'  =>  $request->esifatmatakuliah,
                'kode_matakuliah'  =>  $request->ekode_matakuliah,
                'kode_program_studi'  =>  $request->ekode_program_studi,
                'kode_fakultas'  =>  $request->ekode_fakultas,
                'kode_bayar'  =>  $request->ekode_bayar
            ]);
        return $editmakul;
    }
    public function hapus_matakuliah(Request $request)
    {
        $hapusmatakuliah = DB::table('akd_matakuliah')->where('id_matakuliah', $request->id_matakuliah)->delete();
        return $hapusmatakuliah;
    }

    //Input Nilai Mahasiswa
    public function nilaimahasiswa(Request $request)
    {
        $thn = $request->tahunangkatan;
        $nilaimahasiswa = DB::select("SELECT * FROM akd_mahasiswa a LEFT JOIN adm_jalur_pmb b ON a.kode_jalur_pmb=b.kode_jalur_pmb LEFT JOIN akd_program_pendidikan c ON a.kode_program_pendidikan=c.kode_program_pendidikan LEFT JOIN akd_program_studi d ON a.kode_program_studi=d.kode_program_studi WHERE a.tahun_angkatan LIKE '%$thn%' ORDER BY a.nim DESC");
        return $nilaimahasiswa;
    }
    public function tampiltahunangkatan()
    {
        $tampiltahunangkatan = DB::select("SELECT tahun_angkatan, kode_penilaian FROM akd_mahasiswa WHERE lulus='0' group by tahun_angkatan order by tahun_angkatan desc");
        return $tampiltahunangkatan;
    }
    public function tampiltahunangkatanmaba()
    {
        $tampiltahunangkatan = DB::select("SELECT tahun AS tahun_angkatan FROM adm_camaba group by tahun order by tahun desc");
        return $tampiltahunangkatan;
    }
    public function tampiltahunakademik()
    {
        $tampiltahunakademik = DB::select("SELECT tahun,semester FROM akd_heregistrasi GROUP BY tahun,semester");
        return $tampiltahunakademik;
    }
    public function simpan_nilaimahasiswa(Request $request)
    {
        if (count($request->makul) > 0) {
            foreach ($request->makul as $item => $v) {
                $getID = DB::table('akd_penawaran_matakuliah')->insertGetId([
                    'tahun'  =>  $request->tahun[$item],
                    'semester'  =>  $request->semester[$item],
                    'id_matakuliah'  =>  $request->id_matakuliah[$item],
                    'tahun_kurikulum'  =>  $request->tahun_kurikulum[$item],
                    'sks_matakuliah'  =>  $request->sks_matakuliah[$item],
                    'smt_matakuliah'  =>  $request->smt_matakuliah[$item],
                    'kode_program_studi'  =>  $request->kode_program_studi[$item],
                    'kode_dosen'  =>  $request->kode_dosen[$item]
                ]);

                $getID = DB::table('akd_kelas_kuliah')->insert([
                    'id_tawar'  =>  $getID,
                    'nama_kelas'  =>  $request->semester[$item],
                    'hari'  =>  $request->id_matakuliah[$item],
                    'jam_mulai'  =>  $request->tahun_kurikulum[$item],
                    'jam_selesai'  =>  $request->sks_matakuliah[$item],
                    'kode_ruang'  =>  $request->smt_matakuliah[$item],
                    'kapasitas_ruang'  =>  $request->kode_program_studi[$item],
                    'kode_dosen'  =>  $request->kode_dosen[$item]
                ]);
            }
        }
    }

    // Dosen
    public function dosen(Request $request)
    {
        if ($request->kaprodi != null){
            $dosen = DB::select("SELECT *,simpeg_pegawai.id AS id_pegdosen, CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, 
                    kode_prodi, nama_program_studi, PASSWORD, akd_program_studi.kode_fakultas, dosen_wali
                    FROM user_dosen 
                    LEFT JOIN simpeg_pegawai ON user_dosen.id_pegawai = simpeg_pegawai.id
                    LEFT JOIN akd_program_studi ON akd_program_studi.kode_program_studi = simpeg_pegawai.kode_prodi
                    LEFT JOIN akd_fakultas ON akd_fakultas.kode_fakultas = akd_program_studi.kode_fakultas
                    LEFT JOIN simpeg_jenis ON simpeg_jenis.id = simpeg_pegawai.kode_jenis
                    LEFT JOIN mst_agama ON mst_agama.kode_agama = simpeg_pegawai.id_agama
                    WHERE kode_jenis='1' AND akd_program_studi.kode_program_studi = '".$request->kode_prodi."' ORDER BY dosen_wali DESC");

        }else{
            $dosen = DB::select("SELECT *,simpeg_pegawai.id AS id_pegdosen, CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, 
                    kode_prodi, nama_program_studi, PASSWORD, akd_program_studi.kode_fakultas, dosen_wali
                    FROM user_dosen 
                    LEFT JOIN simpeg_pegawai ON user_dosen.id_pegawai = simpeg_pegawai.id
                    LEFT JOIN akd_program_studi ON akd_program_studi.kode_program_studi = simpeg_pegawai.kode_prodi
                    LEFT JOIN akd_fakultas ON akd_fakultas.kode_fakultas = akd_program_studi.kode_fakultas
                    LEFT JOIN simpeg_jenis ON simpeg_jenis.id = simpeg_pegawai.kode_jenis
                    LEFT JOIN mst_agama ON mst_agama.kode_agama = simpeg_pegawai.id_agama
                    WHERE kode_jenis='1' ORDER BY dosen_wali DESC");
        
        }
        
        return $dosen;
    }

    public function qrdosen()
    {
        $dosen = DB::select("SELECT akd_qrcode.id AS qrcode_id,akd_qrcode.valid_id AS valid_id, simpeg_pegawai.id as id_dosen, simpeg_pegawai.nidn, akd_qrcode.jenis, akd_qrcode.qrcode, simpeg_pegawai.nama AS nama_dosen FROM simpeg_pegawai LEFT JOIN akd_qrcode ON akd_qrcode.id_dosen = simpeg_pegawai.id WHERE simpeg_pegawai.nidn IS NOT NULL");
        return $dosen;
    }
    public function qrdosenmanajemen()
    {
        $dosen = DB::select("SELECT akd_qrcode_manajemen.id AS qrcode_id, akd_fakultas.nama_fakultas, akd_qrcode_manajemen.valid_id AS valid_id, simpeg_pegawai.id AS id_pegawai, simpeg_pegawai.nidn, akd_qrcode_manajemen.jenis, akd_qrcode_manajemen.qrcode, simpeg_pegawai.nama AS nama_dosen, 'Fakultas' AS sumber_data FROM akd_fakultas LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_fakultas.pimpinan LEFT JOIN akd_qrcode_manajemen ON akd_qrcode_manajemen.id_dosen = simpeg_pegawai.id WHERE simpeg_pegawai.nidn IS NOT NULL UNION ALL SELECT akd_qrcode_manajemen.id AS qrcode_id, akd_program_studi.nama_program_studi AS nama_fakultas, akd_qrcode_manajemen.valid_id AS valid_id, simpeg_pegawai.id AS id_pegawai, simpeg_pegawai.nidn, akd_qrcode_manajemen.jenis, akd_qrcode_manajemen.qrcode, simpeg_pegawai.nama AS nama_dosen, 'Prodi' AS sumber_data FROM akd_program_studi LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_program_studi.pimpinan_prodi LEFT JOIN akd_qrcode_manajemen ON akd_qrcode_manajemen.id_dosen = simpeg_pegawai.id WHERE simpeg_pegawai.nidn IS NOT NULL");
        return $dosen;
    }

    public function edit_password_dosen(Request $request)
    {
        $edit_password_dosen = DB::table('user_dosen')
            ->where('id_pegawai', $request->id_peg)
            ->update([
                'password'  =>  md5($request->epasswordbaru)
            ]);
        return $edit_password_dosen;
    }

    public function edit_password_dekanadmin(Request $request)
    {
        $edit_password_dekanadmin = DB::table('user')
            ->where('username', $request->username)
            ->update([
                'pass'  =>  md5($request->password_baru)
            ]);
        return $edit_password_dekanadmin;
    }

    // Mahasiswa
    public function mahasiswa(Request $request)
    {
        $thn = $request->tahunangkatan;
        $mahasiswa = DB::select("SELECT *,a.no_pendaftaran AS pendaftaran_mhs,a.tempat_lahir AS tempat_lahir_mhs,b.email AS email_mhs,b.nik AS nik_mhs,b.nisn AS nisn_mhs,
        o.nama AS dosen_wali_mhs,a.tanggal_lahir AS tanggal_lahir_mhs,a.alamat_asal AS alamat_asal_mhs,a.jenis_kelamin AS jk_mhs,c.nama_program_studi AS prodi_mhs,
        a.kode_agama AS agama_mhs,b.kode_provinsi AS provinsi_mhs,b.kode_kabupaten AS kabupaten_mhs,b.rt AS rt_mhs,b.rw AS rw_mhs,b.kode_pos AS kode_pos_mhs,
        b.alamat_asal AS alamat_mhs,
        n.id AS status_pernikahan,d.nama_provinsi AS nama_provinsi_mhs,h.nama AS nama_ayah,i.nama AS nama_ibu,h.rt AS rt_ayah,h.rw AS rw_ayah,h.kode_pos AS kode_pos_ayah,
        h.alamat AS alamat_ayah,l.pekerjaan_singkatan AS pekerjaan_ayah
                ,i.rt AS rt_ibu,i.rw AS rw_ibu,h.kode_agama AS agama_ayah,i.kode_agama AS agama_ibu,h.pendidikan_id AS jenjangpendidikan_ayah,
                i.pendidikan_id AS jenjangpendidikan_ibu,
                i.kode_pos AS kode_pos_ibu,i.alamat AS alamat_ibu,m.pekerjaan_singkatan AS pekerjaan_ibu,h.kode_pekerjaan AS kode_pekerjaan_ayah,i.kode_pekerjaan AS kode_pekerjaan_ibu,
                h.kode_penghasilan AS kode_penghasilan_ayah,i.kode_penghasilan AS kode_penghasilan_ibu,h.alamat AS alamat_ayah,i.alamat AS alamat_ibu FROM akd_mahasiswa a 
                JOIN adm_camaba b ON a.no_pendaftaran=b.no_pendaftaran 
                LEFT JOIN akd_program_studi c ON a.kode_program_studi=c.kode_program_studi 
                LEFT JOIN adm_provinsi d ON b.kode_provinsi=d.kode_provinsi 
                LEFT JOIN akd_fakultas e ON b.kode_fakultas=e.kode_fakultas 
                LEFT JOIN mst_agama f ON b.kode_agama=f.kode_agama 
                LEFT JOIN mst_kewarganegaraan g ON b.kode_kewarganegaraan=g.kode_kewarganegaraan 
                LEFT JOIN akd_ortu_ayah h ON a.nim=h.nim 
                LEFT JOIN akd_ortu_ibu i ON a.nim=i.nim 
                LEFT JOIN adm_jalur_pmb j ON b.kode_jalur_pmb=j.kode_jalur_pmb 
                LEFT JOIN akd_program_pendidikan k ON a.kode_program_pendidikan=k.kode_program_pendidikan 
                LEFT JOIN mst_pekerjaan l ON h.kode_pekerjaan=l.kode_pekerjaan 
                LEFT JOIN mst_pekerjaan m ON i.kode_pekerjaan=m.kode_pekerjaan 
                LEFT JOIN mst_status_nikah n ON a.status_nikah=n.id 
                LEFT JOIN simpeg_pegawai o ON a.id_dosen_wali=o.id
                WHERE a.tahun_angkatan LIKE '%$thn%' ORDER BY a.tahun_angkatan DESC -- and lulus='0'");
        return $mahasiswa;
    }
    // Password Mahasiswa
    public function passwordmahasiswa(Request $request)
    {
        $thn = $request->tahunangkatan;
        $passwordmahasiswa = DB::select("SELECT * FROM akd_mahasiswa a LEFT JOIN adm_camaba b ON a.no_pendaftaran=b.no_pendaftaran JOIN adm_jalur_pmb c ON a.kode_jalur_pmb=c.kode_jalur_pmb JOIN akd_program_studi e ON a.kode_program_studi=e.kode_program_studi WHERE a.tahun_angkatan LIKE '%$thn%' ORDER BY a.id_mhs DESC");
        return $passwordmahasiswa;
    }

    public function edit_passwordmahasiswamhs(Request $request)
    {
        $editpasswordmahasiswamhs = DB::table('akd_mahasiswa')
            ->where('id_mhs', $request->id_mhs)
            ->update([
                'password_mhs'  =>  md5($request->eulangipassword)
            ]);
        return $editpasswordmahasiswamhs;
    }

    public function edit_passwordmahasiswaortu(Request $request)
    {
        $editpasswordmahasiswaortu = DB::table('akd_mahasiswa')
            ->where('id_mhs', $request->id_mhs1)
            ->update([
                'password_ortu'  =>  $request->eulangipasswordbaru1
            ]);
        return $editpasswordmahasiswaortu;
    }
    public function ceknimterakhir(Request $request)
    {
        $ceknimterakhir = collect(DB::select("SELECT * FROM akd_mahasiswa WHERE kode_program_studi='" . $request->kode_program_studi . "' AND tahun_angkatan='" . $request->tahun_angkatan . "' ORDER BY SUBSTRING(nim,-3) DESC"))->first();
        return $ceknimterakhir;
    }
    // Registrasi
    public function registrasi(Request $request)
    {
        $thn = $request->tahunangkatan;
        $registrasi = DB::select("SELECT e.id_mhs,a.status_keu,a.id_camaba,a.no_pendaftaran,a.tanggal_pendaftaran,e.nim,a.tahun,a.semester,e.tahun_kurikulum,e.tgl_registrasi,e.model_pembayaran,g.nama_program_pendidikan,a.nama_camaba,a.tempat_lahir,a.tanggal_lahir,c.kode_jalur_pmb,c.nama_jalur,b.kode_program_studi,b.nama_program_studi,g.status,e.lulus,e.nim,a.kode_program_pendidikan,e.semester AS smtmhs,e.tahun_angkatan,e.jenis_pembayaran FROM adm_camaba a 
        LEFT JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi 
        LEFT JOIN adm_jalur_pmb c ON a.kode_jalur_pmb=c.kode_jalur_pmb 
        LEFT JOIN akd_program_pendidikan g ON a.kode_program_pendidikan=g.kode_program_pendidikan
        LEFT JOIN akd_mahasiswa e ON a.no_pendaftaran=e.no_pendaftaran WHERE a.tahun LIKE '%$thn%' ORDER BY e.nim,a.id_camaba DESC
        ");
        return $registrasi;
    }

    public function edit_registrasi(Request $request)
    {
        $no_pendft = $request->eno_pendaftaran;
        $tgl_reg = $request->etanggal_heregistrasi;
        $tgl_reg_f = date('Y-m-d', strtotime($tgl_reg));
        $nim = $request->enim;
        $program = $request->ekode_program_pendidikan;
        // $prodi = $_POST['prodi'];
        $kurikulum = $request->etahun_kurikulum;
        $mo_pemb = $request->emodel_pembayaran;
        // echo "$tgl_reg_f";
        $l_ex = DB::table("adm_camaba")->where('no_pendaftaran', $no_pendft)->first();
        $thn = $l_ex->tahun;
        $keg_pmb = $l_ex->gelombang_kegiatan_pmb;
        $semester = $l_ex->semester;
        $k_pmb = $l_ex->kode_jalur_pmb;
        $k_prodi = $program;
        $k_prostu = $l_ex->kode_program_studi;
        $tm_lhir = addslashes($l_ex->tempat_lahir);
        $tg_lhir = $l_ex->tanggal_lahir;
        $nm_cm = addslashes($l_ex->nama_camaba);
        $al_lkal = addslashes($l_ex->alamat_asal);
        $k_ag = $l_ex->kode_agama;
        // $s_bea = $l_ex->status_beasiswa;
        $j_kel = $l_ex->jenis_kelamin;
        $kode_fak = $l_ex->kode_fakultas;
        $pss_o = "up45";
        $passwd_ortu = md5($pss_o);
        $passwd_mhs = md5($pss_o);

        $num = DB::table("akd_mahasiswa")->where('nim', $nim)->count();
        if ($num > 0) {
            $editregistrasi = DB::table('akd_mahasiswa')
                ->where('no_pendaftaran', $request->eno_pendaftaran)
                ->update([
                    'nim'  =>  $request->enim,
                    'tahun_kurikulum'  =>  $request->etahun_kurikulum,
                    'model_pembayaran'  =>  $request->emodel_pembayaran,
                    'tgl_registrasi'  =>  $request->etanggal_heregistrasi
                ]);
        } else {
            $editregistrasi = DB::statement("INSERT INTO akd_mahasiswa(nim,no_pendaftaran,tgl_registrasi,tahun_angkatan,semester,kode_jalur_pmb,kode_program_pendidikan,kode_program_studi,kode_fakultas,tempat_lahir,tanggal_lahir,nama_mahasiswa,alamat_asal,kode_agama,jenis_kelamin,tahun_kurikulum,password_ortu,password_mhs,model_pembayaran,trash) 
            VALUES ('$nim','$no_pendft','$tgl_reg_f','$thn','$semester','$k_pmb','$k_prodi','$k_prostu','$kode_fak','$tm_lhir','$tg_lhir','$nm_cm','$al_lkal','$k_ag','$j_kel','$kurikulum','$passwd_ortu','$passwd_mhs','$mo_pemb','1')");
        }

        return $editregistrasi;
    }
    // Her Registrasi
    public function herregistrasi(Request $request)
    {
        $tahun = $request->tahun;
        $smt = $request->semester;
        $thn = $request->tahunangkatan;
        $nama_prodinya = $request->nama_prodinya;
        $herregistrasi = DB::select("SELECT a.*,b.*,l.telp,l.email,c.id_heregistrasi,c.tanggal_heregistrasi,c.status_her,e.status,f.nama_jenis_her,f.kode_jenis_her,c.batas_sks,c.sks_ambil,c.tahunher,c.smther,(SELECT nim FROM keu_tagihan WHERE tahun='$tahun' AND semester='$smt' AND nama_biaya LIKE '%SPP%' AND STATUS='1' AND nim=a.nim LIMIT 1) AS cekspptetap
        ,(SELECT nim FROM akd_dispensasi WHERE tahun='$tahun' AND semester='$smt' AND jenis='KRS' AND nim=a.nim LIMIT 1) AS cek_dispen FROM akd_mahasiswa a 
        LEFT JOIN adm_camaba l ON l.no_pendaftaran=a.no_pendaftaran
        JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi 
        JOIN akd_program_pendidikan e ON a.kode_program_pendidikan=e.kode_program_pendidikan 
        LEFT JOIN (SELECT akd_heregistrasi.id_heregistrasi,akd_heregistrasi.tanggal_heregistrasi,akd_heregistrasi.status_her,akd_heregistrasi.nim,akd_heregistrasi.kode_jenis_her,g.batas_sks,g.sks_ambil,akd_heregistrasi.tahun as tahunher,akd_heregistrasi.semester as smther FROM akd_heregistrasi 
        JOIN akd_krs g ON akd_heregistrasi.id_heregistrasi=g.id_heregistrasi WHERE tahun='$tahun' AND semester='$smt') c ON a.nim=c.nim 
        -- LEFT JOIN (SELECT * FROM akd_dispensasi WHERE tahun='$tahun' AND semester='$smt' AND jenis='KRS') d ON a.nim=d.nim 
        -- LEFT JOIN (SELECT * FROM keu_tagihan WHERE tahun='$tahun' AND semester='$smt' AND nama_biaya LIKE '%SPP%' AND status='1') mn ON a.nim=mn.nim
        LEFT JOIN akd_jenis_heregistrasi f ON c.kode_jenis_her=f.kode_jenis_her WHERE a.lulus='0' AND a.tahun_angkatan LIKE '%$thn%' AND b.nama_program_studi LIKE '%$nama_prodinya%' ORDER BY a.tahun_angkatan DESC");
        return $herregistrasi;
    }
    public function batassksher(Request $request)
    {
        $tahun = $request->tahun;
        $smt = $request->semester;
        $thn = $request->tahunangkatan;
        $no_induk = $request->nim;
        $batas_sks = 0;
        if ($smt == "1") {
            $tahun_sblm = $tahun - 1;
            $semester_sblm = 2;
        } else {
            $semester_sblm = 1;
            $tahun_sblm = $tahun;
        }

        if ($thn == date('Y')) {
            $batas_sks = 24;
        } else {
            $x_bs = DB::select("select akd_penawaran_matakuliah.sks_matakuliah, akd_detail_krs.nilai_akhir_angka from akd_krs,akd_detail_krs,akd_kelas_kuliah,akd_penawaran_matakuliah,akd_heregistrasi where akd_heregistrasi.nim = '$no_induk' and akd_krs.id_heregistrasi=akd_heregistrasi.id_heregistrasi and akd_krs.id_krs=akd_detail_krs.id_krs and akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas and akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar and akd_heregistrasi.tahun = '$tahun_sblm' and akd_heregistrasi.semester = '$semester_sblm'");
            $nilai_akhir_ips = 0;
            $jml_sks_ips = 0;
            $jml_mutu_ips = 0;
            $ips = 0;
            $ips_f = 0;
            foreach ($x_bs as $d_bs) {
                $sks_ips = $d_bs->sks_matakuliah;
                $jml_sks_ips += $sks_ips;
                $nilai_angka = $d_bs->nilai_akhir_angka;
                $jml_mutu_ips += $nilai_angka;
                $nilai_ips = $sks_ips * $nilai_angka;
                $nilai_akhir_ips += $nilai_ips;
                $ips = $nilai_akhir_ips / $jml_sks_ips;
            }
            if ($ips == 0) {
                $ips_f = 0;
            } else {
                $ips_f = number_format($ips, 2);
            }

            if ($ips_f >= 3.00) {
                $batas_sks = 24;
            } elseif ($ips_f >= 2.50) {
                $batas_sks = 21;
            } elseif ($ips_f >= 2.00) {
                $batas_sks = 18;
            } elseif ($ips_f >= 1.50) {
                $batas_sks = 15;
            } else {
                $batas_sks = 12;
            }
        }
        return $batas_sks;
    }

    public function edit_herregistrasi(Request $request)
    {
        $editherregistrasi = DB::table('akd_heregistrasi')
            ->where('id_heregistrasi', $request->eid_heregistrasi)
            ->update([
                'kode_jenis_her'  =>  $request->enama_jenis_her
            ]);
        $editherregistrasi1 = DB::table('akd_krs')
            ->where('id_heregistrasi', $request->eid_heregistrasi)
            ->update([
                'batas_sks'  =>  $request->ebatas_sks
            ]);
        return $editherregistrasi1;
    }

    public function simpan_herregistrasi(Request $request)
    {

        $cekher = DB::table('akd_heregistrasi')->where('nim', $request->nim)->where('tahun', $request->tahun1)->where('semester', $request->semester1)->count();
        if ($cekher == 0) {
            $simpanheregistrasi = DB::table('akd_heregistrasi')->insert([
                'id_bayar'  =>  '1',
                'nim'  =>  $request->nim,
                'tahun'  =>  $request->tahun1,
                'semester'  =>  $request->semester1,
                'tanggal_heregistrasi'  =>  $request->tanggal_heregistrasi1,
                'kode_jenis_her'  =>  $request->nama_jenis_her,
                'status_her'  =>  '1',
                'krs'  => '0'
            ]);
            $last2 = DB::table('akd_heregistrasi')->orderBy('id_heregistrasi', 'DESC')->first();
            $simpanheregistrasi1 = DB::table('akd_krs')->insert([
                'id_heregistrasi'  =>  $last2->id_heregistrasi,
                'batas_sks'  =>  $request->batas_sks
            ]);
        } else {
            $simpanheregistrasi = false;
        }
        return $simpanheregistrasi;
    }
    // User
    public function user()
    {
        $user = DB::select("SELECT b.id_group,a.nm_module AS nama_m,a.username,a.nama,b.nm_module AS jabatan,c.* FROM USER a LEFT JOIN group_user b ON a.kode_group=b.id_group LEFT JOIN akd_fakultas c ON a.kode_fakultas=c.kode_fakultas ORDER BY a.id_user ASC");
        return $user;
    }

    public function simpan_user(Request $request)
    {

        $simpanuser = DB::table('user')->insert([
            'username'  =>  $request->username,
            'password'  =>  $request->password,
            'nama'  =>  $request->nama,
            'kode_fakultas'  =>  $request->kode_fakultas,
            'kode_group'  =>  $request->kode_group
        ]);
        return $simpanuser;
    }

    public function edit_user(Request $request)
    {
        $edituser = DB::table('user')
            ->where('id_user', $request->id_user)
            ->update([
                'username'  =>  $request->eusername,
                'password'  =>  $request->epassword,
                'nama'  =>  $request->enama,
                'kode_fakultas'  =>  $request->ekode_fakultas,
                'kode_group'  =>  $request->ekode_group
            ]);
        return $edituser;
    }

    public function hapus_user(Request $request)
    {
        $hapususer = DB::table('user')->where('id_user', $request->id_user)->delete();
        return $hapususer;
    }

    public function ubahstatus_user(Request $request)
    {
        $ubahstatususer = DB::table('user')
            ->where('id_user', $request->id_user)
            ->update([
                'aktif'  =>  $request->send_value
            ]);
        return $ubahstatususer;
    }
    // Daftar Hadir Kuliah
    public function daftarhadirkuliah(Request $request)
    {
        $tahun = $request->tahun;
        $semester = $request->semester;
        $programstudi = $request->nama_program_studi;
        $daftarhadirkuliah = DB::select("SELECT * FROM (SELECT a.id_tawar,c.kode_matakuliah,c.nama_matakuliah,c.sks_matakuliah,b.nama_program_studi,d.nama_kelas,CONCAT_WS(' ', e.gelar_depan, e.nama,e.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2,(SELECT COUNT(id_detail_krs) AS jml FROM akd_detail_krs,akd_krs,akd_heregistrasi,akd_mahasiswa WHERE akd_detail_krs.id_kelas=d.id_kelas AND akd_detail_krs.id_krs=akd_krs.id_krs AND akd_krs.id_heregistrasi=akd_heregistrasi.id_heregistrasi AND akd_heregistrasi.tahun='$tahun' AND akd_heregistrasi.semester='$semester' AND akd_heregistrasi.nim=akd_mahasiswa.nim) AS jmlhpsrta FROM akd_penawaran_matakuliah a JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi 
            JOIN akd_matakuliah c ON c.id_matakuliah=a.id_matakuliah 
            JOIN akd_kelas_kuliah d ON d.id_tawar=a.id_tawar
            LEFT JOIN simpeg_pegawai e ON e.id=a.kode_dosen
            LEFT JOIN simpeg_pegawai h ON h.id=a.kode_dosen2 WHERE a.tahun='" . $tahun . "' 
            AND a.semester ='" . $semester . "' AND b.nama_program_studi LIKE '%" . $programstudi . "%') AS tbl1 WHERE jmlhpsrta > 0 ");

        return $daftarhadirkuliah;
    }
    // Daftar Hadir Ujian
    public function daftarhadirujian(Request $request)
    {
        // $nim = $request->nim;
        $tahun = $request->tahun;
        $nama_program_studi = $request->nama_program_studi;
        $semester = $request->semester;
        if ($request->jabatan == 'Dekanat') {
            $daftarhadirujian = DB::select("SELECT kode_matakuliah, nama_matakuliah, nama_program_studi, nama_kelas, jumlah_peserta, 
            CONCAT_WS(' ', simpeg_pegawai.gelar_depan, simpeg_pegawai.nama,simpeg_pegawai.gelar_belakang) AS nama_dosen, 
            CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2 FROM akd_detail_krs
            JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas
            JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
            LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
            LEFT JOIN simpeg_pegawai h ON h.id=akd_penawaran_matakuliah.kode_dosen2
            JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_program_studi ON akd_penawaran_matakuliah.kode_program_studi=akd_program_studi.kode_program_studi 
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND akd_program_studi.kode_fakultas='" . $request->kode_fakultas . "' GROUP BY akd_detail_krs.id_kelas
            ");
        } else {
            $daftarhadirujian = DB::select("SELECT *,CONCAT_WS(' ', e.gelar_depan, e.nama,e.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2,(SELECT COUNT(nilai_akhir_huruf) AS nilai FROM akd_detail_krs aa WHERE d.id_kelas=aa.id_kelas) AS jumlah_nilai,(SELECT COUNT(nilai_uts) AS nilai FROM akd_detail_krs aa WHERE d.id_kelas=aa.id_kelas) AS jumlah_nilaiuts FROM akd_penawaran_matakuliah a JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi 
            JOIN akd_matakuliah c ON c.id_matakuliah=a.id_matakuliah 
            JOIN akd_kelas_kuliah d ON d.id_tawar=a.id_tawar
            LEFT JOIN simpeg_pegawai e ON e.id=a.kode_dosen
            LEFT JOIN simpeg_pegawai h ON h.id=a.kode_dosen2
            JOIN akd_kelas_kuliah f ON f.id_tawar=a.id_tawar WHERE a.tahun LIKE '%" . $tahun . "%' 
            AND a.semester LIKE '%" . $semester . "%' AND b.nama_program_studi LIKE '%" . $nama_program_studi . "%'");
        }
        return $daftarhadirujian;
    }
    // Kartu Ujian
    public function kartuujian(Request $request)
    {
        $tahun = $request->tahun;
        $semester = $request->semester;
        $thn = $request->tahun_angkatan;
        $filterUas = $request->filter_uas; // Ambil nilai filter UAS
        $cekheruts = DB::table('keu_batas_her')->where('id_batas', '2')->first();
        $batasuts = $cekheruts->tahun . "" . $cekheruts->bulanangka;
        $cekheruas = DB::table('keu_batas_her')->where('id_batas', '3')->first();
        $batasuas = $cekheruas->tahun . "" . $cekheruas->bulanangka;

        $query = "SELECT *, 
    (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) < '$batasuts' AND STATUS=0 AND nim=a.nim) AS nunggakuts,
    (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) = '$batasuts' AND STATUS=1 AND nim=a.nim) AS patokanuts,
    (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) < '$batasuas' AND STATUS=0 AND nim=a.nim) AS nunggakuas,
    (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) = '$batasuas' AND STATUS=1 AND nim=a.nim) AS patokanuas,
    (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE nama_biaya = 'Ujian Akhir Semester' AND STATUS=1 AND nim=a.nim AND semester='$semester' 
    AND tahun='$tahun') AS cekuase 
    FROM akd_mahasiswa a 
    JOIN akd_heregistrasi b ON b.nim=a.nim 
    JOIN akd_program_pendidikan c ON c.kode_program_pendidikan=a.kode_program_pendidikan 
    JOIN akd_program_studi d ON d.kode_program_studi=a.kode_program_studi 
    WHERE b.tahun LIKE '%$tahun%' AND b.semester='$semester' AND a.tahun_angkatan LIKE '%$thn%' 
    AND b.krs='1'";

        // Tambahkan filter UAS berdasarkan pilihan dropdown
        if ($filterUas == 'Bisa') {
            $query .= " AND (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) < '$batasuas' AND STATUS=0 AND nim=a.nim) = 0 
                 AND (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) = '$batasuas' AND STATUS=1 AND nim=a.nim) > 0 
                 AND (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE nama_biaya = 'Ujian Akhir Semester' AND STATUS=1 AND nim=a.nim AND semester='$semester' 
                 AND tahun='$tahun') > 0";
        } elseif ($filterUas == 'Belum Bisa') {
            $query .= " AND ((SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) < '$batasuas' AND STATUS=0 AND nim=a.nim) > 0 
                 OR (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) = '$batasuas' AND STATUS=1 AND nim=a.nim) = 0 
                 OR (SELECT COUNT(id_tagihan) FROM keu_tagihan WHERE nama_biaya = 'Ujian Akhir Semester' AND STATUS=1 AND nim=a.nim AND semester='$semester' 
                 AND tahun='$tahun') = 0)";
        }

        $kartuujian = DB::select($query);
        return response()->json($kartuujian);
    }
    public function dropdown_angkatan()
    {
        $dropdown_angkatan = DB::select("SELECT tahun_angkatan FROM akd_mahasiswa GROUP BY tahun_angkatan DESC");
        return $dropdown_angkatan;
    }
    // Kartu Hasil Studi
    public function hasilstudi(Request $request)
    {
        $tahun = $request->tahun;
        $semester = $request->semester;
        $thn = $request->tahun_angkatan;
        $hasilstudi = DB::select("SELECT * FROM akd_mahasiswa a 
        LEFT JOIN akd_heregistrasi b ON b.nim=a.nim 
        LEFT JOIN akd_program_pendidikan c ON c.kode_program_pendidikan=a.kode_program_pendidikan 
        LEFT JOIN akd_program_studi d ON d.kode_program_studi=a.kode_program_studi WHERE b.tahun LIKE '%$tahun%' AND b.semester='$semester' AND a.tahun_angkatan LIKE '%$thn%'
         AND b.krs='1'");
        return $hasilstudi;
    }
    // Dosen Wali
    public function dosenwali()
    {
        $dosenwali = DB::select("SELECT id_userpeg, id_pegawai, email_login, nidn, CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, 
        kode_prodi, nama_program_studi, password, akd_program_studi.kode_fakultas, dosen_wali
        FROM user_dosen 
        INNER JOIN simpeg_pegawai ON user_dosen.id_pegawai = simpeg_pegawai.id
        INNER JOIN akd_program_studi ON akd_program_studi.kode_program_studi = simpeg_pegawai.kode_prodi
        INNER JOIN akd_fakultas ON akd_fakultas.kode_fakultas = akd_program_studi.kode_fakultas
        WHERE dosen_wali='1' ORDER BY dosen_wali DESC");
        return $dosenwali;
    }


    public function edit_dosenwali(Request $request)
    {
        $editdosenwali = DB::table('akd_mahasiswa')
            ->where('nim', $request->nim)
            ->update([
                'status_wali'  =>  '0'
            ]);
        return $editdosenwali;
    }
    //Laporan Her registrasi

    public function lapherregistrasi(Request $request)
    {
        $pecah = explode("|", $request->tahunakademik);
        $thn = $pecah[0];
        $smt = $pecah[1];
        $lapherregistrasi = DB::select("SELECT * FROM akd_heregistrasi a JOIN akd_mahasiswa b ON a.nim=b.nim JOIN akd_program_studi c ON b.kode_program_studi=c.kode_program_studi where 
        a.tahun='" . $thn . "' and
        a.semester='" . $smt . "' and
        a.kode_jenis_her='1'");
        return $lapherregistrasi;
    }
    public function dropdown_akademik()
    {
        $dropdown_akademik = DB::select("SELECT tahun FROM akd_heregistrasi GROUP BY tahun DESC");
        return $dropdown_akademik;
    }
    public function kewarganegaraan()
    {
        $kewarganegaraan = DB::select("SELECT tahun FROM akd_heregistrasi GROUP BY tahun DESC");
        return $kewarganegaraan;
    }
    //Dispensasi
    public function dispensasi(Request $request)
    {
        $pecah = explode("|", $request->tahunakademik);
        $thn = $pecah[0];
        $smt = $pecah[1];
        $dispensasi = DB::select("SELECT * FROM akd_dispensasi a JOIN akd_mahasiswa b ON a.nim=b.nim JOIN akd_program_studi c ON b.kode_program_studi=c.kode_program_studi where 
        a.tahun='" . $thn . "' and
        a.semester='" . $smt . "'");
        return $dispensasi;
    }
    //Laporan IPK Mahasiswa Detail
    public function lap_ipk_Mahasiswa_detail(Request $request)
    {
        $kode_program_studi = $request->kode_program_studi;

        // $lap_ipk_Mahasiswa_detail = DB::select("SELECT * FROM akd_mahasiswa,akd_heregistrasi,akd_krs WHERE akd_mahasiswa.kode_program_studi='" . $kode_program_studi . "' 
        // AND akd_heregistrasi.tahun='2019' AND akd_heregistrasi.semester='2' AND akd_mahasiswa.nim=akd_heregistrasi.nim 
        // AND akd_heregistrasi.id_heregistrasi=akd_krs.id_heregistrasi AND akd_krs.waktu_krs IS NOT NULL ORDER BY akd_mahasiswa.nim ASC");
        $lap_ipk_Mahasiswa = DB::table('akd_mahasiswa')->where('lulus', '=', '0')->where('kode_program_studi', '=', $kode_program_studi);



        foreach ($lap_ipk_Mahasiswa->get() as $row) {
            $nimnya = $row->nim;
            $lap_ipk = DB::select("SELECT akd_matakuliah.sks_matakuliah,akd_transkrip.nim,MIN(akd_transkrip.nilai) AS nilai
            FROM akd_transkrip JOIN akd_matakuliah ON akd_transkrip.id_matakuliah=akd_matakuliah.id_matakuliah WHERE akd_transkrip.nim='$nimnya' 
            GROUP BY akd_matakuliah.id_matakuliah ORDER BY akd_transkrip.id_matakuliah ASC");

            $sksjum = 0;
            $total = 0;

            foreach ($lap_ipk as $row2) {
                $nilaangka = DB::table('akd_predikat_nilai_huruf')->where('nilai_huruf_akhir', '=', $row2->nilai)->first();
                $sksjum = $sksjum + $row2->sks_matakuliah;
                $total = $total + ($nilaangka->mutu * $row2->sks_matakuliah);
            }
            $ipk = round((intval($total) / intval($sksjum == 0 ? 1 : $sksjum)), 2);
            // $ipk = intval($total);


            //ips hitung
            $sksjumips = 0;
            $totalips = 0;
            // $lap_ips = DB::select("SELECT a.nilai_akhir_angka ,g.sks_matakuliah 
            // FROM akd_detail_krs a JOIN akd_krs b JOIN akd_heregistrasi c JOIN akd_kelas_kuliah d JOIN akd_penawaran_matakuliah e JOIN akd_matakuliah g
            // WHERE a.id_krs=b.id_krs AND b.id_heregistrasi=c.id_heregistrasi AND a.id_kelas=d.id_kelas AND d.id_tawar=e.id_tawar AND e.id_matakuliah=g.id_matakuliah AND c.nim='$nimnya' AND c.tahun='2022' AND c.semester='1'");
            // foreach ($lap_ips as $row22) {
            //     $totalips = $totalips + ($row22->nilai_akhir_angka * $row2->sks_matakuliah);
            //     $sksjumips = $sksjum + $row22->sks_matakuliah;
            // }
            $ips = round((intval($totalips) / intval($sksjumips == 0 ? 1 : $sksjumips)), 2);
            $json[] = ["nim" => $row->nim, "nama" => $row->nama_mahasiswa, "sksjum" => $sksjum, "tot" => $total, "ipk" => $ipk, "sksjumips" => $sksjumips, "totips" => $totalips, "ips" => $ips];
        }
        if ($lap_ipk_Mahasiswa->count() > 0) {
            $hasil['data'] = $json;
        } else {
            $hasil['data'] = [];
        }

        return $hasil;
        // $kode_program_studi = $request->kode_program_studi;

        // $lap_ipk_Mahasiswa_detail = DB::select("SELECT * FROM akd_mahasiswa WHERE akd_mahasiswa.kode_program_studi='" . $kode_program_studi . "' 
        // AND akd_mahasiswa.lulus='0' ORDER BY akd_mahasiswa.nim DESC");

        // return $lap_ipk_Mahasiswa_detail;
    }
    //Form Input Calon Mahasiswa
    public function forminputcamaba(Request $request)
    {
        $kode_program_studi = $request->kode_program_studi;
        $forminputcamaba = DB::select("SELECT * FROM akd_mahasiswa,akd_heregistrasi,akd_krs where akd_mahasiswa.kode_program_studi='" . $kode_program_studi . "' and akd_heregistrasi.tahun='2022' and akd_heregistrasi.semester='1' and akd_mahasiswa.nim=akd_heregistrasi.nim and akd_heregistrasi.id_heregistrasi=akd_krs.id_heregistrasi and akd_krs.waktu_krs is not null ORDER BY akd_mahasiswa.nim ASC");
        return $forminputcamaba;
    }
    // Transkip Nilai
    public function transkipnilai(Request $request)
    {
        $query = DB::table('akd_mahasiswa as m')
            ->select(
                'm.nim as nim',
                'm.nama_mahasiswa as nama_mahasiswa',
                'm.tahun_angkatan as tahun_angkatan',
                'p.nama_program_pendidikan',
                's.nama_program_studi',
                'j.nama_jenjang_pendidikan'
            )
            ->join('akd_program_pendidikan as p', 'm.kode_program_pendidikan', '=', 'p.kode_program_pendidikan')
            ->join('akd_program_studi as s', 'm.kode_program_studi', '=', 's.kode_program_studi')
            ->leftJoin('akd_jenjang_pendidikan as j', 's.kode_jenjang_pendidikan', '=', 'j.kode_jenjang_pendidikan')
            ->where('m.tahun_angkatan', $request->tahunangkatan)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('akd_transkrip as t')
                  ->whereColumn('t.nim', 'm.nim');
            });

        if ($request->kode_prodi) {
            $query->where('m.kode_program_studi', $request->kode_prodi);
        }

        $results = $query->get()->toArray();

        $active_tahun = intval($request->tahun);
        $active_semester = intval($request->semester);

        foreach ($results as $row) {
            $angkatan = intval($row->tahun_angkatan);
            if ($angkatan > 0 && $active_tahun > 0 && $active_semester > 0) {
                $row->semester = ($active_tahun - $angkatan) * 2 + $active_semester;
            } else {
                $row->semester = 1;
            }
        }

        return $results;
    }
    // Transkip Akademik
    public function transkipakademik(Request $request)
    {
        $query = DB::table('akd_mahasiswa as m')
            ->select(
                'm.nim as nm',
                'm.nama_mahasiswa as namamhs',
                'p.nama_program_pendidikan',
                's.nama_program_studi',
                'a.*'
            )
            ->join('akd_program_pendidikan as p', 'm.kode_program_pendidikan', '=', 'p.kode_program_pendidikan')
            ->join('akd_program_studi as s', 'm.kode_program_studi', '=', 's.kode_program_studi')
            ->leftJoin('akd_kelengkapan_transkrip as a', 'a.nim', '=', 'm.nim')
            ->where('m.tahun_angkatan', $request->tahunangkatan)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('akd_transkrip as t')
                  ->whereColumn('t.nim', 'm.nim');
            });

        if ($request->kode_prodi) {
            $query->where('m.kode_program_studi', $request->kode_prodi);
        }

        return $query->get()->toArray();
    }

    public function cetaktranskipakademik(Request $request)
    {

        $transkipakademik = DB::select("SELECT MIN(akd_transkrip.nilai) AS biji,akd_transkrip.*,akd_matakuliah.*,akd_predikat_nilai_huruf.* FROM akd_transkrip,akd_matakuliah,akd_predikat_nilai_huruf WHERE akd_transkrip.nim='" . $request->nim . "' AND akd_transkrip.id_matakuliah=akd_matakuliah.id_matakuliah
            AND akd_transkrip.nilai=akd_predikat_nilai_huruf.nilai_huruf_akhir GROUP BY akd_matakuliah.id_matakuliah ORDER BY akd_transkrip.id_matakuliah ASC");
        return $transkipakademik;
    }

    public function gettranskipakademik_cetak(Request $request)
    {
        $gettranskipakademik_cetak = DB::select("SELECT * FROM akd_mahasiswa,adm_camaba,akd_fakultas,akd_program_studi,akd_kelengkapan_transkrip WHERE akd_mahasiswa.nim='" . $request->nim . "' AND 
        akd_mahasiswa.no_pendaftaran=adm_camaba.no_pendaftaran AND akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi
        AND akd_mahasiswa.kode_fakultas=akd_fakultas.kode_fakultas AND akd_kelengkapan_transkrip.nim=akd_mahasiswa.nim");
        return $gettranskipakademik_cetak;
    }
    public function tampilno_transkipakademik()
    {
        $tampilno_transkipakademik = collect(DB::select("SELECT * FROM akd_kelengkapan_transkrip ORDER BY id DESC"))->first();
        return $tampilno_transkipakademik;
    }
    // Daftar Maba
    public function daftarmaba(Request $request)
    {
        $thn = $request->tahunangkatan;
        $daftarmaba = DB::select("SELECT *,b.no_pendaftaran AS pendaftaran_mhs,b.tempat_lahir AS tempat_lahir_mhs,b.email AS email_mhs,b.nik AS nik_mhs,b.nisn AS nisn_mhs,
        b.tanggal_lahir AS tanggal_lahir_mhs,b.alamat_asal AS alamat_asal_mhs,b.jenis_kelamin AS jk_mhs,c.nama_program_studi AS prodi_mhs,
        b.kode_agama AS agama_mhs,b.kode_provinsi AS provinsi_mhs,b.kode_kabupaten AS kabupaten_mhs,b.rt AS rt_mhs,b.rw AS rw_mhs,b.kode_pos AS kode_pos_mhs,
        b.alamat_asal AS alamat_mhs,
        d.nama_provinsi AS nama_provinsi_mhs,h.nama AS nama_ayah,i.nama AS nama_ibu,h.rt AS rt_ayah,h.rw AS rw_ayah,h.kode_pos AS kode_pos_ayah,
        h.alamat AS alamat_ayah,l.pekerjaan_singkatan AS pekerjaan_ayah
                ,i.rt AS rt_ibu,i.rw AS rw_ibu,h.kode_agama AS agama_ayah,i.kode_agama AS agama_ibu,h.pendidikan_id AS jenjangpendidikan_ayah,
                i.pendidikan_id AS jenjangpendidikan_ibu,
                i.kode_pos AS kode_pos_ibu,i.alamat AS alamat_ibu,m.pekerjaan_singkatan AS pekerjaan_ibu,h.kode_pekerjaan AS kode_pekerjaan_ayah,i.kode_pekerjaan AS kode_pekerjaan_ibu,
                h.kode_penghasilan AS kode_penghasilan_ayah,i.kode_penghasilan AS kode_penghasilan_ibu,h.alamat AS alamat_ayah,i.alamat AS alamat_ibu 
                FROM adm_camaba b
                LEFT JOIN akd_program_studi c ON b.kode_program_studi=c.kode_program_studi 
                LEFT JOIN adm_provinsi d ON b.kode_provinsi=d.kode_provinsi 
                LEFT JOIN akd_fakultas e ON b.kode_fakultas=e.kode_fakultas 
                LEFT JOIN mst_agama f ON b.kode_agama=f.kode_agama 
                LEFT JOIN mst_kewarganegaraan g ON b.kode_kewarganegaraan=g.kode_kewarganegaraan 
                LEFT JOIN adm_ortu_ayah h ON b.no_pendaftaran=h.no_pendaftaran 
                LEFT JOIN adm_ortu_ibu i ON b.no_pendaftaran=i.no_pendaftaran 
                LEFT JOIN adm_jalur_pmb j ON b.kode_jalur_pmb=j.kode_jalur_pmb 
                LEFT JOIN akd_program_pendidikan k ON b.kode_program_pendidikan=k.kode_program_pendidikan 
                LEFT JOIN mst_pekerjaan l ON h.kode_pekerjaan=l.kode_pekerjaan 
                LEFT JOIN mst_pekerjaan m ON i.kode_pekerjaan=m.kode_pekerjaan WHERE b.tahun LIKE '%$thn%' 
        ");
        return $daftarmaba;
    }
    // Mahasiswa Lulusan 1
    public function mahasiswalulusan1()
    {
        $mahasiswalulusan1 = DB::select("SELECT * FROM akd_mahasiswa JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi where lulus='0' ORDER BY id_mhs DESC
        ");
        return $mahasiswalulusan1;
    }
    // Mahasiswa Lulusan 2
    public function mahasiswalulusan2()
    {
        $mahasiswalulusan2 = DB::select("SELECT * FROM akd_mahasiswa JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi where lulus='1' OR lulus='2' OR lulus='3' ORDER BY id_mhs DESC
        ");
        return $mahasiswalulusan2;
    }

    public function status_lulus_mahasiswa(Request $request)
    {
        $status_lulus_mahasiswa = DB::table('akd_mahasiswa')
            ->where('id_mhs', $request->id_mhs)
            ->update([
                'lulus'  =>  "1"
            ]);
        return $status_lulus_mahasiswa;
    }

    public function status_mengundurkan_diri_mahasiswa(Request $request)
    {
        $status_mengundurkan_diri_mahasiswa = DB::table('akd_mahasiswa')
            ->where('id_mhs', $request->id_mhs)
            ->update([
                'lulus'  =>  "2"
            ]);
        return $status_mengundurkan_diri_mahasiswa;
    }

    public function status_dikeluarkan_mahasiswa(Request $request)
    {
        $status_dikeluarkan_mahasiswa = DB::table('akd_mahasiswa')
            ->where('id_mhs', $request->id_mhs)
            ->update([
                'lulus'  =>  "3"
            ]);
        return $status_dikeluarkan_mahasiswa;
    }

    public function status_batal_mahasiswa(Request $request)
    {
        $status_batal_mahasiswa = DB::table('akd_mahasiswa')
            ->where('id_mhs', $request->id_mhs)
            ->update([
                'lulus'  =>  "0"
            ]);
        return $status_batal_mahasiswa;
    }
    public function tampilkegiatanakademik()
    {
        $tampilkegiatanakademik = DB::select("SELECT * FROM akd_kegiatan ORDER BY kode_kegiatan ASC");
        return $tampilkegiatanakademik;
    }
    public function cetakkartuhasilstudi(Request $request)
    {
        // $cetakkartuhasilstudi = DB::select("SELECT * FROM akd_mahasiswa
        // JOIN akd_heregistrasi ON akd_mahasiswa.nim=akd_heregistrasi.nim
        // JOIN akd_program_pendidikan ON akd_mahasiswa.kode_program_pendidikan=akd_program_pendidikan.kode_program_pendidikan
        // JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi
        // WHERE akd_mahasiswa.nim='" . $nim . "' AND akd_heregistrasi.tahun='" . $tahun . "' AND akd_heregistrasi.semester='" . $semester . "' AND akd_heregistrasi.krs='1'");
        // return $cetakkartuhasilstudi;
    }
    public function cetaktranskipnilai(Request $request)
    {
        $thn = $request->tahunangkatan;
        $transkipnilai = DB::select("SELECT a.nim,a.nama_mahasiswa,c.nama_program_pendidikan,d.nama_program_studi FROM akd_mahasiswa a 
        JOIN akd_program_pendidikan c ON c.kode_program_pendidikan=a.kode_program_pendidikan 
        JOIN akd_program_studi d ON d.kode_program_studi=a.kode_program_studi WHERE a.tahun_angkatan='$thn'");

        return $transkipnilai;
    }
    public function tampilsemester()
    {
        $tampilsemester = DB::select("SELECT semester FROM akd_mahasiswa group by semester asc");
        return $tampilsemester;
    }
    public function edittampilfakultas()
    {
        $edittampilfakultas = DB::select("SELECT * FROM akd_fakultas WHERE trash='0' order by nama_fakultas asc");
        return $edittampilfakultas;
    }
    public function edittampilprogramstudi()
    {
        $edittampilprogramstudi = DB::select("SELECT * FROM akd_program_studi order by nama_program_studi asc");
        return $edittampilprogramstudi;
    }
    // public function tampilmhs(Request $request)
    // {
    //     // $thn = $request->tahunangkatan;
    //     $tampilmhs = DB::select("SELECT * FROM akd_detail_krs,akd_krs,akd_heregistrasi,akd_mahasiswa where akd_detail_krs.id_kelas='$id_kelas' and akd_detail_krs.id_krs=akd_krs.id_krs and akd_krs.id_heregistrasi=akd_heregistrasi.id_heregistrasi
    //     and akd_heregistrasi.nim=akd_mahasiswa.nim and akd_heregistrasi.tahun='2020' and akd_heregistrasi.semester='$2' order by akd_mahasiswa.nim asc");

    //     return $tampilmhs;
    // }
    public function tampilprodi_perfak(Request $request)
    {
        $kode_fakultas = $request->kode_fakultas;
        $tampilprodi = DB::select("select * from akd_program_studi where kode_fakultas='$kode_fakultas'");

        return $tampilprodi;
    }
    public function tampilperprodi(Request $request)
    {
        $kode_program_studi = $request->kode_program_studi;
        $tampilprodi = DB::select("select * from akd_program_studi where kode_program_studi='$kode_program_studi'");

        return $tampilprodi;
    }
    public function tampiljalurpmb()
    {
        $tampiljalurpmb = DB::select("select * from adm_jalur_pmb order by kode_jalur_pmb asc");

        return $tampiljalurpmb;
    }
    public function tampilprovinsi()
    {
        $tampilprovinsi = DB::select("select * from adm_provinsi order by nama_provinsi asc");

        return $tampilprovinsi;
    }
    public function cekkalenderbatasinputnilai(Request $request)
    {
        $tahun = $request->tahun;
        $smt = $request->smt;
        $cekk = collect(DB::select("SELECT * FROM akd_kalender_akademik WHERE kode_kegiatan_akademik='22' AND tahun='$tahun' AND semester='$smt'"))->first();
        $cektanggal = strtotime(date('Y-m-d'));
        $cekmulai = strtotime($cekk->tanggal_mulai);
        $cekakhir = strtotime($cekk->tanggal_akhir);
        $cekkalender = ['tanggalsekarang' => $cektanggal, 'tanggalmulai' => $cekmulai, 'tanggalakhir' => $cekakhir];
        return $cekkalender;
    }


    public function ubahstatus_camaba(Request $request)
    {
        // $ubahstatuscamaba = DB::table('adm_camaba')
        //     ->where('id_camaba', $request->id_camaba)
        //     ->update([
        //         'trash'  =>  '1'
        //     ]);

        $ubahstatuscamaba = DB::table('adm_camaba')->where('id_camaba', $request->id_camaba)->delete();
        return $ubahstatuscamaba;
    }
    // Daftar Maba
    public function detail_camaba(Request $request)
    {
        $id_camaba = $request->id_camaba;
        $detail_camaba = DB::select("SELECT *,b.no_pendaftaran AS pendaftaran_mhs,b.tempat_lahir AS tempat_lahir_mhs,b.email AS email_mhs,b.nik AS nik_mhs,b.nisn AS nisn_mhs,
        b.tanggal_lahir AS tanggal_lahir_mhs,b.alamat_asal AS alamat_asal_mhs,b.jenis_kelamin AS jk_mhs,c.nama_program_studi AS prodi_mhs,
        b.kode_agama AS agama_mhs,b.kode_provinsi AS provinsi_mhs,b.kode_kabupaten AS kabupaten_mhs,b.rt AS rt_mhs,b.rw AS rw_mhs,b.kode_pos AS kode_pos_mhs,
        b.alamat_asal AS alamat_mhs,
        d.nama_provinsi AS nama_provinsi_mhs,h.nama AS nama_ayah,i.nama AS nama_ibu,h.rt AS rt_ayah,h.rw AS rw_ayah,h.kode_pos AS kode_pos_ayah,
        h.alamat AS alamat_ayah,l.pekerjaan_singkatan AS pekerjaan_ayah
                ,i.rt AS rt_ibu,i.rw AS rw_ibu,h.kode_agama AS agama_ayah,i.kode_agama AS agama_ibu,h.pendidikan_id AS jenjangpendidikan_ayah,
                i.pendidikan_id AS jenjangpendidikan_ibu,
                i.kode_pos AS kode_pos_ibu,i.alamat AS alamat_ibu,m.pekerjaan_singkatan AS pekerjaan_ibu,h.kode_pekerjaan AS kode_pekerjaan_ayah,i.kode_pekerjaan AS kode_pekerjaan_ibu,
                h.kode_penghasilan AS kode_penghasilan_ayah,i.kode_penghasilan AS kode_penghasilan_ibu,h.alamat AS alamat_ayah,i.alamat AS alamat_ibu 
                FROM adm_camaba b
                LEFT JOIN akd_program_studi c ON b.kode_program_studi=c.kode_program_studi 
                LEFT JOIN adm_provinsi d ON b.kode_provinsi=d.kode_provinsi 
                LEFT JOIN akd_fakultas e ON b.kode_fakultas=e.kode_fakultas 
                LEFT JOIN mst_agama f ON b.kode_agama=f.kode_agama 
                LEFT JOIN mst_kewarganegaraan g ON b.kode_kewarganegaraan=g.kode_kewarganegaraan 
                LEFT JOIN akd_ortu_ayah h ON b.no_pendaftaran=h.no_pendaftaran 
                LEFT JOIN akd_ortu_ibu i ON b.no_pendaftaran=i.no_pendaftaran 
                LEFT JOIN adm_jalur_pmb j ON b.kode_jalur_pmb=j.kode_jalur_pmb 
                LEFT JOIN akd_program_pendidikan k ON b.kode_program_pendidikan=k.kode_program_pendidikan 
                LEFT JOIN mst_pekerjaan l ON h.kode_pekerjaan=l.kode_pekerjaan 
                LEFT JOIN mst_pekerjaan m ON i.kode_pekerjaan=m.kode_pekerjaan WHERE b.id_camaba='$id_camaba'");
        return $detail_camaba;
    }
    // Master Nilai
    public function select_nilai(Request $request)
    {

        $select_nilai = DB::select("SELECT * FROM akd_predikat_nilai_huruf WHERE nilai_huruf_akhir like '%{$request->search}%'");

        if (!empty($select_nilai[0]->nilai_huruf_akhir)) {
            foreach ($select_nilai as $namaselect_nilai) {
                $select_nilaiArray[] = array(
                    "id" => $namaselect_nilai->nilai_huruf_akhir,
                    "text" => $namaselect_nilai->nilai_huruf_akhir
                );
            }
        } else {
            $select_nilaiArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_nilaiArray]);
    }

    public function simpan_nilai_akhir1(Request $request)
    {
        // var_dump($request);
        if (count($request->makul) > 0) {
            foreach ($request->makul as $item => $v) {
                $matkul = DB::table('akd_matakuliah')->where('id_matakuliah', '=', $request->makul)->first();
                $simpan_nilai_akhir = DB::table('akd_transkrip')->insert([
                    'nim'  =>  $request->nim,
                    'id_matakuliah'  =>  $request->makul[$item],
                    'tahun_kurikulum'  =>  $matkul->tahun_kurikulum,
                    'nilai'  =>  $request->nilai_huruf_akhir[$item],
                    'nilai_uts'  => ""
                ]);
            }
        }
    }
    public function tampilkabupaten(Request $request)
    {
        $kd_provinsi = $request->kd_provinsi;
        $tampilkabupaten = DB::select("select * from adm_kabupaten WHERE kd_provinsi='$kd_provinsi'");

        return $tampilkabupaten;
    }

    public function simpan_camaba(Request $request)
    {
        $tglJamnow = date('Y-m-d');

        $pendaftaran = DB::table('adm_camaba')
            ->select(DB::raw('MAX(no_pendaftaran) as id'))->where('tahun', '=', $request->tahun)->first();
        $no_pendft = $pendaftaran->id;
        $noAwal = substr($no_pendft, 0, 8);
        $noUrut = substr($no_pendft, 8, 4);
        $noUrut++;
        $no_pendaftaran = $noAwal . sprintf("%04s", $noUrut);

        $fakultas = DB::table('akd_program_studi')->where('kode_program_studi', '=', $request->nama_program_studi)->first();
        $simpancamaba = DB::table('adm_camaba')->insert([
            'no_pendaftaran'  =>  $no_pendaftaran,
            'tahun'  =>  $request->tahun,
            'nik'  =>  $request->nik,
            'semester'  =>  $request->semester,
            'gelombang_kegiatan_pmb'  =>  "1",
            'kode_jalur_pmb'  =>  $request->jalur_pmb,
            'kode_program_pendidikan'  =>  $request->program_pendidikan,
            'kode_program_studi'  =>  $request->nama_program_studi,
            'kode_fakultas'  =>  $fakultas->kode_fakultas,
            'tanggal_pendaftaran'  =>  $tglJamnow,
            'tanggal_diterima'  =>  $tglJamnow,
            'status_diterima'  =>  "1",
            'nama_camaba'  =>  $request->nama_lengkap,
            'tempat_lahir'  =>  $request->tempat_lahir,
            'tanggal_lahir'  =>  $request->tgl_lahir,
            'jenis_kelamin'  =>  $request->jenis_kelamin,
            'kode_agama'  =>  $request->agama_maba,
            'kode_kewarganegaraan'  =>  $request->kewarganegaraan,
            'alamat_asal'  =>  $request->alamat_asal,
            'rt'  =>  $request->rt,
            'rw'  =>  $request->rw,
            'kode_kabupaten'  =>  $request->kabupaten,
            'kode_provinsi'  =>  $request->provinsi,
            'kode_pos'  =>  $request->kode_pos,
            'telp'  =>  $request->no_telepon,
            'email'  =>  $request->email,
            'pendidikan_terakhir'  =>  $request->pendidikan_terakhir,
            'alamat_slta'  =>  $request->alamat_sekolah,
            'jurusan_slta'  =>  $request->jurusan,
            'no_ijazah_slta'  =>  $request->no_ijazah,
            'nisn'  =>  $request->nisn,
            'tahun_ijazah_slta'  =>  $request->tahun_ijazah,
            'jenis_bayar'  =>  $request->jenisbayar
        ]);
        $simpanayah = DB::table('akd_ortu_ayah')->insert([
            'no_pendaftaran'  =>  $no_pendaftaran,
            'nama'  =>  $request->nama_ayah,
            'alamat'  =>  $request->alamat_asal,
            'rt'  =>  $request->rt,
            'rw'  =>  $request->rw,
            'kode_kabupaten'  =>  $request->kabupatenortu,
            'kode_propinsi'  =>  $request->provinsiortu,
            'kode_pos'  =>  $request->kode_pos,
            'kode_agama'  =>  $request->agama_ayah,
            'kode_pekerjaan'  =>  $request->pekerjaan_ayah,
            'pendidikan_id'  =>  $request->pendidikan_ayah,
            'kode_penghasilan'  =>  $request->penghasilan,
            'telepon_ayah'  =>  $request->no_telepon
        ]);
        $simpanibu = DB::table('akd_ortu_ibu')->insert([
            'no_pendaftaran'  =>  $no_pendaftaran,
            'nama'  =>  $request->nama_ibu,
            'alamat'  =>  $request->alamat_asal,
            'rt'  =>  $request->rt,
            'rw'  =>  $request->rw,
            'kode_kabupaten'  =>  $request->kabupatenortu,
            'kode_propinsi'  =>  $request->provinsiortu,
            'kode_pos'  =>  $request->kode_pos,
            'kode_agama'  =>  $request->agama_ibu,
            'kode_pekerjaan'  =>  $request->pekerjaan_ibu,
            'pendidikan_id'  =>  $request->pendidikan_ibu,
            'kode_penghasilan'  =>  $request->penghasilan,
            'telepon'  =>  $request->no_telepon
        ]);
        return $simpancamaba;
    }

    public function edit_mahasiswa(Request $request)
    {
        $editmahasiswa = DB::table('akd_mahasiswa')
            ->where('id_mhs', $request->id_mhs11)
            ->update([
                'kode_jalur_pmb'  =>  $request->editjalurpmb,
                // 'kode_program_studi'  =>  $request->nama_program_studi,
                'kode_fakultas'  =>  $request->nama_fakultas11,
                'tempat_lahir'  =>  $request->tempat_lahir11,
                'tanggal_lahir'  =>  $request->tgl_lahir11,
                'nama_mahasiswa'  =>  $request->nama_mahasiswa11,
                'alamat_asal'  =>  $request->alamat_asal11,
                'kode_agama'  =>  $request->editagama,
                'status_nikah'  =>  $request->status_nikah11,
                'jenis_kelamin'  =>  $request->editjkmhs
            ]);
        DB::table('adm_camaba')
            ->where('no_pendaftaran', $request->no_pendaftaran11)
            ->update([
                'nik'  =>  $request->nik11,
                'kode_jalur_pmb'  =>  $request->editjalurpmb,
                // 'kode_program_studi'  =>  $request->nama_program_studi,
                'nama_camaba'  =>  $request->nama_mahasiswa11,
                'tempat_lahir'  =>  $request->tempat_lahir11,
                'tanggal_lahir'  =>  $request->tgl_lahir11,
                'jenis_kelamin'  =>  $request->editjkmhs,
                'kode_agama'  =>  $request->editagama,
                'kode_kewarganegaraan'  =>  $request->kode_kewarganegaraan11,
                'alamat_asal'  =>  $request->alamat_asal11,
                'rt'  =>  $request->rt11,
                'rw'  =>  $request->rw11,
                'kode_kabupaten'  =>  $request->kabupaten11,
                'kode_provinsi'  =>  $request->provinsi11,
                'kode_pos'  =>  $request->kode_pos_mhs,
                'telp'  =>  $request->telp11,
                'email'  =>  $request->email111,
                'pendidikan_terakhir'  =>  $request->pendidikan_terakhir11,
                'jurusan_slta'  =>  $request->jurusan_slta11,
                'no_ijazah_slta'  =>  $request->no_ijazah_slta11,
                'tahun_ijazah_slta'  =>  $request->tahun_ijazah_slta11,
                'alamat_slta'  =>  $request->alamat_slta11
            ]);

        DB::table('akd_ortu_ayah')
            ->where('no_pendaftaran', $request->no_pendaftaran11)
            ->update([
                'nama'  =>  $request->nama_ayah11,
                'alamat'  =>  $request->alamat_ayah11,
                'rt'  =>  $request->rt_ayah11,
                'rw'  =>  $request->rw_ayah11,
                'kode_kabupaten'  =>  $request->kabupatenortu11,
                'kode_propinsi'  =>  $request->provinsiortu11,
                'kode_pos'  =>  $request->kode_pos_ayah11,
                'kode_agama'  =>  $request->kode_agama_ayah11,
                'kode_pekerjaan'  =>  $request->pekerjaan_ayah11,
                'pendidikan_id'  =>  $request->jenjang_pendidikan_ayah11,
                'kode_penghasilan'  =>  $request->kode_penghasilan_ayah11,
                'telepon_ayah'  =>  $request->telepon_ayah11
            ]);

        DB::table('akd_ortu_ibu')
            ->where('no_pendaftaran', $request->no_pendaftaran11)
            ->update([
                'nama'  =>  $request->nama_ibu11,
                'alamat'  =>  $request->alamat_ayah11,
                'rt'  =>  $request->rt_ayah11,
                'rw'  =>  $request->rw_ayah11,
                'kode_kabupaten'  =>  $request->kabupatenortu11,
                'kode_propinsi'  =>  $request->provinsiortu11,
                'kode_pos'  =>  $request->kode_pos_ayah11,
                'kode_agama'  =>  $request->kode_agama_ibu11,
                'kode_pekerjaan'  =>  $request->pekerjaan_ibu11,
                'pendidikan_id'  =>  $request->jenjang_pendidikan_ibu11,
                'kode_penghasilan'  =>  $request->kode_penghasilan_ibu11,
                'telepon'  =>  $request->telepon_ibu11
            ]);
        return $editmahasiswa;
    }

    public function edit_camaba(Request $request)
    {
        $edit_camaba =
            DB::table('adm_camaba')
            ->where('id_camaba', $request->id_camaba)
            ->update([
                'nik'  =>  $request->nik11,
                'nisn'  =>  $request->nisn_mhs11,
                'no_pendaftaran'  =>  $request->no_pendaftaran11,
                'kode_jalur_pmb'  =>  $request->editjalurpmb,
                // 'kode_program_studi'  =>  $request->nama_program_studi,
                'nama_camaba'  =>  $request->nama_mahasiswa11,
                'tempat_lahir'  =>  $request->tempat_lahir11,
                'tanggal_lahir'  =>  $request->tgl_lahir11,
                'jenis_kelamin'  =>  $request->editjkmhs,
                'kode_agama'  =>  $request->editagama,
                'kode_kewarganegaraan'  =>  $request->kode_kewarganegaraan11,
                'alamat_asal'  =>  $request->alamat_asal11,
                'rt'  =>  $request->rt11,
                'rw'  =>  $request->rw11,
                'kode_kabupaten'  =>  $request->kabupaten11,
                'kode_provinsi'  =>  $request->provinsi11,
                'kode_pos'  =>  $request->kode_pos_mhs,
                'telp'  =>  $request->telp11,
                'email'  =>  $request->email111,
                'pendidikan_terakhir'  =>  $request->pendidikan_terakhir11,
                'jurusan_slta'  =>  $request->jurusan_slta11,
                'no_ijazah_slta'  =>  $request->no_ijazah_slta11,
                'tahun_ijazah_slta'  =>  $request->tahun_ijazah_slta11,
                'alamat_slta'  =>  $request->alamat_slta11
            ]);

        DB::table('akd_ortu_ayah')
            ->where('no_pendaftaran', $request->no_pendaftaran11)
            ->update([
                'nama'  =>  $request->nama_ayah11,
                'alamat'  =>  $request->alamat_ayah11,
                'rt'  =>  $request->rt_ayah11,
                'rw'  =>  $request->rw_ayah11,
                'kode_kabupaten'  =>  $request->kabupatenortu11,
                'kode_propinsi'  =>  $request->provinsiortu11,
                'kode_pos'  =>  $request->kode_pos_ayah11,
                'kode_agama'  =>  $request->kode_agama_ayah11,
                'kode_pekerjaan'  =>  $request->pekerjaan_ayah11,
                'pendidikan_id'  =>  $request->jenjang_pendidikan_ayah11,
                'kode_penghasilan'  =>  $request->kode_penghasilan_ayah11,
                'telepon_ayah'  =>  $request->telepon_ayah11
            ]);

        DB::table('akd_ortu_ibu')
            ->where('no_pendaftaran', $request->no_pendaftaran11)
            ->update([
                'nama'  =>  $request->nama_ibu11,
                'alamat'  =>  $request->alamat_ayah11,
                'rt'  =>  $request->rt_ayah11,
                'rw'  =>  $request->rw_ayah11,
                'kode_kabupaten'  =>  $request->kabupatenortu11,
                'kode_propinsi'  =>  $request->provinsiortu11,
                'kode_pos'  =>  $request->kode_pos_ayah11,
                'kode_agama'  =>  $request->kode_agama_ibu11,
                'kode_pekerjaan'  =>  $request->pekerjaan_ibu11,
                'pendidikan_id'  =>  $request->jenjang_pendidikan_ibu11,
                'kode_penghasilan'  =>  $request->kode_penghasilan_ibu11,
                'telepon'  =>  $request->telepon_ibu11
            ]);
        return $edit_camaba;
    }
    public function cetakdaftarhadirkuliah(Request $request)
    {

        $daftarhadir = DB::select("SELECT * FROM akd_penawaran_matakuliah a JOIN akd_matakuliah b ON a.id_matakuliah=b.id_matakuliah
        JOIN akd_program_studi e ON e.kode_program_studi=a.kode_program_studi
        JOIN akd_kelas_kuliah d ON a.id_tawar=d.id_tawar 
        JOIN simpeg_pegawai c ON c.id=a.kode_dosen WHERE a.id_tawar='" . $request->id_tawar . "'");


        return $daftarhadir;
    }
    public function cetakdaftarhadirkuliah1(Request $request)
    {

        $daftarhadir = DB::select("SELECT * FROM akd_penawaran_matakuliah a JOIN akd_kelas_kuliah b ON a.id_tawar=b.id_tawar
        JOIN akd_detail_krs c ON c.id_kelas=b.id_kelas
        JOIN akd_krs d ON d.id_krs=c.id_krs
        JOIN akd_heregistrasi e ON e.id_heregistrasi=d.id_heregistrasi
        JOIN akd_mahasiswa f ON f.nim=e.nim WHERE a.id_tawar='" . $request->id_tawar . "' ORDER BY e.nim");


        return $daftarhadir;
    }

    public function getdaftarhadirkuliah_cetak(Request $request)
    {
        $getdaftarhadirkuliah_cetak = DB::select("SELECT *,CONCAT_WS(' ', e.gelar_depan, e.nama,e.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2,i.nama AS namakaprodi,nama_program_studi,j.nama AS namadekan,g.kode_fakultas as kodefak FROM akd_kelas_kuliah a JOIN akd_penawaran_matakuliah b ON a.id_tawar=b.id_tawar
        LEFT JOIN simpeg_pegawai e ON e.id=a.kode_dosen
        LEFT JOIN simpeg_pegawai h ON h.id=b.kode_dosen2
        JOIN akd_program_studi d ON b.kode_program_studi=d.kode_program_studi
        JOIN akd_matakuliah f ON b.id_matakuliah=f.id_matakuliah
        JOIN akd_fakultas g ON d.kode_fakultas=g.kode_fakultas
        LEFT JOIN simpeg_pegawai i ON i.id=d.pimpinan_prodi
        LEFT JOIN simpeg_pegawai j ON j.id=g.pimpinan
        WHERE a.id_tawar ='" . $request->id_tawar . "'");
        return $getdaftarhadirkuliah_cetak;
        // return response()->json(['urut' => $getmhs_cetak]);
    }
    public function getdaftarhadirkuliah_cetak1(Request $request)
    {
        $getdaftarhadirkuliah_cetak = DB::select("SELECT *,CONCAT_WS(' ', e.gelar_depan, e.nama,e.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2,i.nama AS namakaprodi,j.nama AS namadekan,i.nidn AS nidnkaprodi,j.nidn AS nidndekan,g.valid_id AS validdekan,d.valid_id AS validkaprodi,g.kode_fakultas as kodefak FROM akd_kelas_kuliah a JOIN akd_penawaran_matakuliah b ON a.id_tawar=b.id_tawar
        LEFT JOIN simpeg_pegawai e ON e.id=a.kode_dosen
        LEFT JOIN simpeg_pegawai h ON h.id=a.kode_dosen2
        JOIN akd_program_studi d ON b.kode_program_studi=d.kode_program_studi
        JOIN akd_matakuliah f ON b.id_matakuliah=f.id_matakuliah
        JOIN akd_fakultas g ON d.kode_fakultas=g.kode_fakultas
        LEFT JOIN simpeg_pegawai i ON i.id=d.pimpinan_prodi
        LEFT JOIN simpeg_pegawai j ON j.id=g.pimpinan
        WHERE a.id_kelas ='" . $request->id_kelas . "'");
        return $getdaftarhadirkuliah_cetak;

    }
    public function cetakkartuujian(Request $request)
    {

        $daftarhadir = DB::select("SELECT * FROM akd_mahasiswa a JOIN akd_heregistrasi b ON a.nim=b.nim
        JOIN akd_krs c ON c.id_heregistrasi=b.id_heregistrasi
        JOIN akd_detail_krs d ON d.id_krs=c.id_krs
        JOIN akd_kelas_kuliah e ON e.id_kelas=d.id_kelas
        JOIN akd_penawaran_matakuliah f ON f.id_tawar=e.id_tawar
        JOIN akd_matakuliah g ON g.id_matakuliah=f.id_matakuliah WHERE a.nim='" . $request->nim . "'");


        return $daftarhadir;
    }
    public function cetakdaftarhadirujian(Request $request)
    {

        $daftarhadir = DB::select("SELECT * FROM akd_program_studi a JOIN akd_matakuliah b ON a.kode_program_studi=b.kode_program_studi
        JOIN akd_penawaran_matakuliah e ON e.kode_program_studi=a.kode_program_studi
        JOIN akd_kelas_kuliah d ON e.id_tawar=d.id_tawar 
        JOIN simpeg_pegawai c ON c.id=e.kode_dosen WHERE e.id_tawar='" . $request->id_tawar . "'");


        return $daftarhadir;
    }
    public function cetakdaftarhadirujian1(Request $request)
    {

        $daftarhadirujian = DB::select("SELECT * FROM akd_penawaran_matakuliah a JOIN akd_kelas_kuliah b ON a.id_tawar=b.id_tawar
        JOIN akd_detail_krs c ON c.id_kelas=b.id_kelas
        JOIN akd_krs d ON d.id_krs=c.id_krs
        JOIN akd_heregistrasi e ON e.id_heregistrasi=d.id_heregistrasi
        JOIN akd_mahasiswa f ON f.nim=e.nim WHERE a.id_tawar='" . $request->id_tawar . "' ORDER BY e.nim");


        return $daftarhadirujian;
    }

    public function getdaftarhadirujian_cetak(Request $request)
    {
        $getdaftarhadirujian_cetak = DB::select("SELECT *,CONCAT_WS(' ', e.gelar_depan, e.nama,e.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2,
        (SELECT CONCAT_WS(' ', gelar_depan, nama, gelar_belakang) AS nama FROM simpeg_pegawai aa WHERE g.pimpinan=aa.id) AS dekane,
        (SELECT tgl_ujian FROM akd_berita_acara_ujian WHERE akd_berita_acara_ujian.id_kelas=a.id_kelas) AS tgl_ba_ujian
        FROM akd_kelas_kuliah a 
        	JOIN akd_penawaran_matakuliah b ON a.id_tawar=b.id_tawar
        	JOIN simpeg_pegawai e ON e.id=a.kode_dosen
        	LEFT JOIN simpeg_pegawai h ON b.kode_dosen2=h.id
        	JOIN akd_program_studi d ON b.kode_program_studi=d.kode_program_studi
        	JOIN akd_matakuliah f ON b.id_matakuliah=f.id_matakuliah
        	JOIN akd_fakultas g ON d.kode_fakultas=g.kode_fakultas
        	WHERE a.id_tawar ='" . $request->id_tawar . "'");
        return $getdaftarhadirujian_cetak;
    }
    public function cetakkartuujian1(Request $request)
    {
        $kartuujian = DB::select("SELECT *,CONCAT_WS(' ', i.gelar_depan, i.nama,i.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2 FROM akd_mahasiswa a JOIN akd_heregistrasi b ON a.nim=b.nim
        JOIN akd_krs c ON c.id_heregistrasi=b.id_heregistrasi
        JOIN akd_detail_krs d ON d.id_krs=c.id_krs
        JOIN akd_kelas_kuliah e ON e.id_kelas=d.id_kelas
        JOIN akd_penawaran_matakuliah f ON f.id_tawar=e.id_tawar
        JOIN akd_matakuliah g ON g.id_matakuliah=f.id_matakuliah
        LEFT JOIN simpeg_pegawai h ON h.id=f.kode_dosen2
        LEFT JOIN simpeg_pegawai i ON i.id=f.kode_dosen WHERE a.nim='" . $request->nim . "' AND b.semester='" . $request->semester . "' AND b.tahun='" . $request->tahun . "'");
        return $kartuujian;
    }
    public function cetakdaftarhadirujianjamak(Request $request)
    {
        $daftarhadir = DB::select("SELECT * FROM akd_penawaran_matakuliah a JOIN akd_kelas_kuliah b ON a.id_tawar=b.id_tawar
        JOIN akd_detail_krs c ON c.id_kelas=b.id_kelas
        JOIN akd_krs d ON d.id_krs=c.id_krs
        JOIN akd_heregistrasi e ON e.id_heregistrasi=d.id_heregistrasi
        JOIN akd_mahasiswa f ON f.nim=e.nim WHERE a.id_tawar='" . $request->id_tawar . "' ORDER BY e.nim");
        return $daftarhadir;
    }

    public function getkartuujian_cetak(Request $request)
    {
        $getkartuujian_cetak = DB::select("SELECT *, CONCAT_WS(' ', gelar_depan, nama, gelar_belakang) AS dekan FROM akd_mahasiswa a JOIN akd_heregistrasi b ON a.nim=b.nim
        JOIN akd_krs c ON c.id_heregistrasi=b.id_heregistrasi
        JOIN akd_detail_krs d ON d.id_krs=c.id_krs
        JOIN akd_kelas_kuliah e ON e.id_kelas=d.id_kelas
        JOIN akd_penawaran_matakuliah f ON f.id_tawar=e.id_tawar
        JOIN akd_program_studi g ON g.kode_program_studi=a.kode_program_studi
        JOIN akd_fakultas h ON h.kode_fakultas=g.kode_fakultas
        LEFT JOIN simpeg_pegawai ds ON ds.id=h.pimpinan WHERE b.tahun = '" . $request->tahun . "' AND b.semester='" . $request->semester . "'
         AND b.krs='1' AND a.nim='" . $request->nim . "' GROUP BY a.nim");
        return $getkartuujian_cetak;
    }

    public function select_makulprasyarat(Request $request)
    {

        $select_makulprasyarat = DB::select("SELECT * FROM akd_matakuliah WHERE kode_program_studi = '$request->kode_prodi' and nama_matakuliah like '%{$request->search}%' and tahun_kurikulum!='2015'");

        if (!empty($select_makulprasyarat[0]->id_matakuliah)) {
            foreach ($select_makulprasyarat as $namaselect_makulprasyarat) {
                $select_makulprasyaratArray[] = array(
                    "id" => $namaselect_makulprasyarat->id_matakuliah,
                    "text" => $namaselect_makulprasyarat->nama_matakuliah
                );
            }
        } else {
            $select_makulprasyaratArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $select_makulprasyaratArray]);
        // return $select_makulprasyarat;
    }

    public function edit_transkipnilai(Request $request)
    {
        // $tgl = (isset($request->tgl)) ? Carbon::createFromFormat('d-m-Y', $request->tgl)->format('Y-m-d') : '';
        $nim =  $request->enim;
        $list_ba = collect(DB::select("SELECT * FROM akd_kelengkapan_transkrip WHERE nim = '$nim'"))->count();

        if ($list_ba > 0) {
            $edittranskipnilai = DB::table('akd_kelengkapan_transkrip')
                ->where('nim', $request->enim)
                ->update([
                    'no_transkrip'  =>  $request->no_transkip,
                    'no_sk'  =>  $request->no_sk_banpt,
                    'status_akreditasi'  =>  $request->status_akreditasi
                ]);
        } else {
            DB::table('akd_kelengkapan_transkrip')->insert([
                'nim'  =>  $request->enim,
                'no_transkrip'  =>  $request->no_transkip,
                'no_sk'  =>  $request->no_sk_banpt,
                'status_akreditasi'  =>  $request->status_akreditasi
            ]);
            // $statuslulus = DB::table('akd_mahasiswa')
            // ->where('nim', $request->enim)
            //     ->update([
            //         'lulus'  =>  '1'
            //     ]);
        }
        return response()->json(['success' => 'Berhasil ditambahkan !']);
    }

    public function ubahstatus_registrasi(Request $request)
    {
        $hapusregistrasi = DB::table('akd_mahasiswa')->where('no_pendaftaran', $request->no_pendaftaran)->delete();
        return $hapusregistrasi;
    }
    public function edittampilkurikulum()
    {
        $edittampilkurikulum = DB::select("SELECT * FROM akd_kurikulum WHERE trash='0' GROUP BY tahun_kurikulum");
        return $edittampilkurikulum;
    }
    public function edittampiljeniskelamin()
    {
        $edittampiljeniskelamin = DB::select("SELECT * FROM mst_jenis_kelamin");
        return $edittampiljeniskelamin;
    }
    public function edittampiljenisher()
    {
        $edittampiljenisher = DB::select("SELECT * FROM akd_jenis_heregistrasi");
        return $edittampiljenisher;
    }

    public function hapus_herregistrasi(Request $request)
    {
        $hapusherregistrasi = DB::table('akd_heregistrasi')->where('id_heregistrasi', $request->id_heregistrasi)->delete();
        $hapusherregistrasi = DB::table('akd_krs')->where('id_heregistrasi', $request->id_heregistrasi)->delete();
        return $hapusherregistrasi;
    }
    // KRS Mahasiswa
    public function krsmahasiswa(Request $request)
    {
        $tahun = $request->tahun;
        $semester = $request->semester;
        $thn = $request->tahun_angkatan;
        $krsmahasiswa = DB::select("SELECT * FROM akd_heregistrasi a 
        JOIN akd_krs b ON b.id_heregistrasi=a.id_heregistrasi 
        JOIN akd_mahasiswa c ON c.nim=a.nim 
        JOIN akd_program_studi d ON c.kode_program_studi=d.kode_program_studi WHERE a.tahun LIKE '%$tahun%' AND a.semester='$semester' AND c.tahun_angkatan LIKE '%$thn%' ORDER BY c.nim ASC");
        return $krsmahasiswa;
    }
    public function cetakkrsmahasiswa(Request $request)
    {

        $daftarhadir = DB::select("SELECT * FROM akd_mahasiswa a JOIN akd_heregistrasi b ON a.nim=b.nim
        JOIN akd_krs c ON c.id_heregistrasi=b.id_heregistrasi
        JOIN akd_detail_krs d ON d.id_krs=c.id_krs
        JOIN akd_kelas_kuliah e ON e.id_kelas=d.id_kelas
        JOIN akd_penawaran_matakuliah f ON f.id_tawar=e.id_tawar
        JOIN akd_matakuliah g ON g.id_matakuliah=f.id_matakuliah WHERE a.nim='" . $request->nim . "'");


        return $daftarhadir;
    }
    public function cetakkrsmahasiswa1(Request $request)
    {
        $kartuujian = DB::select("SELECT *,CONCAT_WS(' ', g.gelar_depan, g.nama,g.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2 FROM akd_heregistrasi a 
        JOIN akd_krs b ON b.id_heregistrasi=a.id_heregistrasi 
        JOIN akd_detail_krs c ON c.id_krs=b.id_krs 
        JOIN akd_kelas_kuliah d ON d.id_kelas=c.id_kelas 
        JOIN akd_penawaran_matakuliah e ON e.id_tawar=d.id_tawar 
        JOIN akd_matakuliah f ON f.id_matakuliah=e.id_matakuliah
        LEFT JOIN simpeg_pegawai g ON g.id=e.kode_dosen
        LEFT JOIN simpeg_pegawai h ON h.id=e.kode_dosen2 WHERE a.nim='" . $request->nim . "' AND a.semester='" . $request->semester . "' AND a.tahun='" . $request->tahun . "'");
        return $kartuujian;
    }

    public function getkrsmahasiswa_cetak(Request $request)
    {
        $getkrsmahasiswa_cetak = DB::select("SELECT * FROM akd_mahasiswa a JOIN akd_heregistrasi b ON a.nim=b.nim
        JOIN akd_krs c ON c.id_heregistrasi=b.id_heregistrasi
        JOIN akd_detail_krs d ON d.id_krs=c.id_krs
        JOIN akd_kelas_kuliah e ON e.id_kelas=d.id_kelas
        JOIN akd_penawaran_matakuliah f ON f.id_tawar=e.id_tawar
        JOIN akd_program_studi g ON g.kode_program_studi=a.kode_program_studi
        JOIN akd_fakultas h ON h.kode_fakultas=g.kode_fakultas WHERE b.tahun = '" . $request->tahun . "' AND b.semester='" . $request->semester . "' AND a.nim='" . $request->nim . "' GROUP BY a.nim");
        return $getkrsmahasiswa_cetak;
    }
    public function cetakkartuhasilstudi1(Request $request)
    {
        $kartuhasilstudi = DB::select("SELECT *,CONCAT_WS(' ', i.gelar_depan, i.nama,i.gelar_belakang) AS nama_dosen,CONCAT_WS(' ', h.gelar_depan, h.nama,h.gelar_belakang) AS nama_dosen2 FROM akd_mahasiswa a 
            JOIN akd_heregistrasi b ON a.nim=b.nim
            JOIN akd_krs c ON c.id_heregistrasi=b.id_heregistrasi
            JOIN akd_detail_krs d ON d.id_krs=c.id_krs
            JOIN akd_kelas_kuliah e ON e.id_kelas=d.id_kelas
            JOIN akd_penawaran_matakuliah f ON f.id_tawar=e.id_tawar
            JOIN akd_matakuliah g ON g.id_matakuliah=f.id_matakuliah
            LEFT JOIN akd_predikat_nilai_huruf ON d.nilai_akhir_huruf = akd_predikat_nilai_huruf.nilai_huruf_akhir AND akd_predikat_nilai_huruf.kode_nilai = a.kode_penilaian
            LEFT JOIN simpeg_pegawai h ON h.id=f.kode_dosen2
            LEFT JOIN simpeg_pegawai i ON i.id=f.kode_dosen WHERE b.krs=1 AND a.nim='" . $request->nim . "' AND b.semester='" . $request->semester . "' AND b.tahun='" . $request->tahun . "'");
        return $kartuhasilstudi;
    }
    public function getSeluruhKHS1(Request $request)
    {
        $nim = $request->nim;
    
        $query = "SELECT 
                    d.nilai_akhir_huruf, 
                    akd_predikat_nilai_huruf.mutu, 
                    g.sks_matakuliah,
                    g.kode_matakuliah,
                    g.nama_matakuliah,
                    b.tahun,
                    b.semester,
                    CONCAT_WS(' ', i.gelar_depan, i.nama, i.gelar_belakang) AS nama_dosen,
                    CONCAT_WS(' ', h.gelar_depan, h.nama, h.gelar_belakang) AS nama_dosen2
                FROM akd_mahasiswa a  
                JOIN akd_heregistrasi b ON a.nim = b.nim
                JOIN akd_krs c ON c.id_heregistrasi = b.id_heregistrasi
                JOIN akd_detail_krs d ON d.id_krs = c.id_krs
                JOIN akd_kelas_kuliah e ON e.id_kelas = d.id_kelas
                JOIN akd_penawaran_matakuliah f ON f.id_tawar = e.id_tawar
                JOIN akd_matakuliah g ON g.id_matakuliah = f.id_matakuliah
                LEFT JOIN akd_predikat_nilai_huruf ON d.nilai_akhir_huruf = akd_predikat_nilai_huruf.nilai_huruf_akhir 
                    AND akd_predikat_nilai_huruf.kode_nilai = a.kode_penilaian
                LEFT JOIN simpeg_pegawai h ON h.id = f.kode_dosen2
                LEFT JOIN simpeg_pegawai i ON i.id = f.kode_dosen
                WHERE b.krs = 1 
                    AND a.nim = ?";
    
        return DB::select($query, [$nim]);
    }
    public function getkartuhasilstudi_cetak(Request $request)
    {
        $getkartuhasilstudi_cetak = DB::select("SELECT *,(SELECT CONCAT_WS(' ', gelar_depan,nama,gelar_belakang) as namanya FROM  simpeg_pegawai WHERE id=g.pimpinan_prodi) as nama_kaprodi,(SELECT CONCAT_WS(' ', gelar_depan,nama,gelar_belakang) as namanya FROM  simpeg_pegawai WHERE id=a.id_dosen_wali) as nama_dpa FROM akd_mahasiswa a JOIN akd_heregistrasi b ON a.nim=b.nim
        JOIN akd_krs c ON c.id_heregistrasi=b.id_heregistrasi
        JOIN akd_detail_krs d ON d.id_krs=c.id_krs
        JOIN akd_kelas_kuliah e ON e.id_kelas=d.id_kelas
        JOIN akd_penawaran_matakuliah f ON f.id_tawar=e.id_tawar
        JOIN akd_program_studi g ON g.kode_program_studi=a.kode_program_studi
        JOIN akd_fakultas h ON h.kode_fakultas=g.kode_fakultas WHERE b.tahun = '" . $request->tahun . "' AND b.semester='" . $request->semester . "'
         AND b.krs='1' AND a.nim='" . $request->nim . "' GROUP BY a.nim");
        return $getkartuhasilstudi_cetak;
    }

    public function list_sksambil_already(Request $request)
    {
        $list_sksambil_already = DB::select("SELECT * FROM akd_detail_krs a 
        JOIN akd_kelas_kuliah b ON a.id_kelas=b.id_kelas 
        JOIN akd_penawaran_matakuliah c ON b.id_tawar=c.id_tawar 
        JOIN akd_matakuliah d ON c.id_matakuliah=d.id_matakuliah 
        WHERE a.id_krs = '" . $request->id_krs . "'");
        return $list_sksambil_already;
    }

    public function list_sksbayar_already(Request $request)
    {
        $list_sksbayar_already = DB::select("SELECT * FROM akd_detail_krs a 
        JOIN akd_kelas_kuliah b ON a.id_kelas=b.id_kelas 
        JOIN akd_penawaran_matakuliah c ON b.id_tawar=c.id_tawar 
        JOIN akd_matakuliah d ON c.id_matakuliah=d.id_matakuliah 
        WHERE a.id_krs = '" . $request->id_krs . "'");
        return $list_sksbayar_already;
    }
    public function cetaktranskipnilai1(Request $request)
    {

        $transkipnilai1 = DB::select("SELECT MIN(akd_transkrip.nilai) AS biji,akd_transkrip.*,akd_matakuliah.*,akd_predikat_nilai_huruf.*,MAX(akd_predikat_nilai_huruf.mutu) AS mutu 
            FROM akd_transkrip
            JOIN akd_matakuliah ON akd_transkrip.id_matakuliah=akd_matakuliah.id_matakuliah
            JOIN akd_predikat_nilai_huruf ON akd_transkrip.nilai=akd_predikat_nilai_huruf.nilai_huruf_akhir
            WHERE akd_transkrip.nim='" . $request->nim . "' 
              AND akd_transkrip.id_matakuliah IN (
                  SELECT DISTINCT pm.id_matakuliah 
                  FROM akd_detail_krs dk
                  JOIN akd_krs k ON dk.id_krs=k.id_krs
                  JOIN akd_heregistrasi h ON k.id_heregistrasi=h.id_heregistrasi
                  JOIN akd_kelas_kuliah kk ON dk.id_kelas=kk.id_kelas
                  JOIN akd_penawaran_matakuliah pm ON kk.id_tawar=pm.id_tawar
                  WHERE h.nim='" . $request->nim . "'
              )
            GROUP BY akd_matakuliah.id_matakuliah 
            ORDER BY akd_matakuliah.smt_matakuliah,akd_transkrip.id_matakuliah ASC");

        return $transkipnilai1;
    }
    public function cetaktranskipnilaikurikulum(Request $request)
    {
        $cekkur = DB::table('akd_mahasiswa')->where('nim', $request->nim)->first();
        $kur = $cekkur->tahun_kurikulum;
        $prodi = $cekkur->kode_program_studi;


        $transkipnilai1 = DB::select("SELECT a.*,tb.biji as nilai_huruf_akhir,tb.mutu FROM akd_matakuliah a LEFT JOIN (SELECT MIN(akd_transkrip.nilai) AS biji,akd_transkrip.*,MAX(akd_predikat_nilai_huruf.mutu) AS mutu 
FROM akd_transkrip
JOIN akd_matakuliah ON akd_transkrip.id_matakuliah=akd_matakuliah.id_matakuliah
JOIN akd_predikat_nilai_huruf ON akd_transkrip.nilai=akd_predikat_nilai_huruf.nilai_huruf_akhir 
WHERE akd_transkrip.nim='" . $request->nim . "' 
  AND akd_transkrip.id_matakuliah IN (
      SELECT DISTINCT pm.id_matakuliah 
      FROM akd_detail_krs dk
      JOIN akd_krs k ON dk.id_krs=k.id_krs
      JOIN akd_heregistrasi h ON k.id_heregistrasi=h.id_heregistrasi
      JOIN akd_kelas_kuliah kk ON dk.id_kelas=kk.id_kelas
      JOIN akd_penawaran_matakuliah pm ON kk.id_tawar=pm.id_tawar
      WHERE h.nim='" . $request->nim . "'
  )
GROUP BY akd_matakuliah.id_matakuliah ORDER BY akd_transkrip.id_matakuliah ASC
) AS tb ON a.id_matakuliah=tb.id_matakuliah WHERE a.tahun_kurikulum='$kur' AND a.kode_program_studi='$prodi' ORDER BY a.id_matakuliah");


        return $transkipnilai1;
    }

    public function gettranskipnilai_cetak(Request $request)
    {
        $gettranskipnilai_cetak = DB::select("SELECT akd_mahasiswa.*,adm_camaba.*,akd_program_studi.*,akd_fakultas.*,akd_kelengkapan_transkrip.*,CONCAT(gelar_depan,' ',simpeg_pegawai.nama,', ',gelar_belakang) AS namakaprodi,(SELECT CONCAT(gelar_depan,' ',nama,' ',gelar_belakang) FROM simpeg_pegawai WHERE id=akd_fakultas.pimpinan) AS namadekan,(SELECT COALESCE(nip, nidn, '-') FROM simpeg_pegawai WHERE id=akd_fakultas.pimpinan) AS nipdekan,
        DATE_FORMAT(
            COALESCE(
                (SELECT u.tanggal_ujian 
                 FROM akd_skripsi_berita_acara ba 
                 JOIN akd_skripsi_ujian u ON ba.id_skripsi_ujian = u.id 
                 WHERE ba.nim = akd_mahasiswa.nim 
                   AND ba.status = 'selesai' 
                   AND ba.keputusan IN ('lulus', 'lulus_dengan_perbaikan') 
                 ORDER BY ba.id DESC LIMIT 1), 
                akd_kelengkapan_transkrip.tanggal_lulus
            ), 
            '%d-%m-%Y'
        ) AS tgllulus,
        DATE_FORMAT(tanggal_yudicium, '%d-%m-%Y') AS tglyud,DATE_FORMAT(tgl_registrasi, '%d-%m-%Y') AS tglmasuk,DATE_FORMAT(akd_mahasiswa.tanggal_lahir, '%d-%m-%Y') AS tgllahir FROM akd_mahasiswa JOIN adm_camaba ON 
        akd_mahasiswa.no_pendaftaran=adm_camaba.no_pendaftaran JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi
        JOIN akd_fakultas ON akd_mahasiswa.kode_fakultas=akd_fakultas.kode_fakultas  LEFT JOIN  akd_kelengkapan_transkrip ON akd_kelengkapan_transkrip.nim=akd_mahasiswa.nim LEFT JOIN simpeg_pegawai ON akd_program_studi.pimpinan_prodi=simpeg_pegawai.id WHERE akd_mahasiswa.nim='" . $request->nim . "' ");
        return $gettranskipnilai_cetak;
    }
    public function tampilno_transkip()
    {
        $tampilno_transkip = collect(DB::select("SELECT * FROM akd_kelengkapan_transkrip ORDER BY id DESC"))->first();
        return $tampilno_transkip;
    }

    public function save_mhs_dosenwali(Request $request)
    {

        // if (count($request->nim) > 0) {
        //     foreach ($request->nim as $item => $v) {
        DB::table('akd_mahasiswa')
            ->where('nim', $request->nim)
            ->update([
                'id_dosen_wali' => $request->id_pegawai
            ]);
        //     }
        // }

        DB::table('user_dosen')
            ->where('id_pegawai', $request->id_pegawai)
            ->update([
                'dosen_wali' => 1
            ]);


        return true;
    }

    // Mahasiswa
    public function daftar_mahasiswa(Request $request)
    {
        $daftar_mahasiswa = DB::select("SELECT a.nim,a.nama_mahasiswa,b.nama_program_studi, b.kode_program_studi, tahun_angkatan
        FROM akd_mahasiswa a 
        JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi 
        WHERE b.kode_program_studi = '" . $request->kode_prodi . "' AND a.lulus='0' AND a.nim NOT IN 
        (SELECT nim FROM akd_mahasiswa WHERE id_dosen_wali !=0 AND nim IS NOT NULL)
        ORDER BY a.id_mhs DESC");
        return $daftar_mahasiswa;
    }

    public function list_mhs_already(Request $request)
    {
        $list_mhs_already = DB::select("SELECT a.nim,a.nama_mahasiswa,b.nama_program_studi, b.kode_program_studi, tahun_angkatan
        FROM akd_mahasiswa a 
        JOIN akd_program_studi b ON a.kode_program_studi=b.kode_program_studi 
        WHERE b.kode_program_studi = '" . $request->kode_prodi . "' AND id_dosen_wali='" . $request->id_pegawai . "'
        ORDER BY a.id_mhs DESC");
        return $list_mhs_already;
    }

    public function hapus_mhs_dosen_wali(Request $request)
    {
        $query = DB::table('akd_mahasiswa')
            ->where('nim', $request->nim)
            ->update([
                'id_dosen_wali' => 0
            ]);

        return $query;
    }

    public function nonaktif_mhs_dosenwali(Request $request)
    {


        $cek_mhs = DB::select("SELECT nim
        FROM akd_mahasiswa WHERE id_dosen_wali = '" . $request->id_pegawai . "'");

        if (count($cek_mhs) > 0) {
            foreach ($cek_mhs as $item) {
                DB::table('akd_mahasiswa')
                    ->where('nim', $item->nim)
                    ->update([
                        'id_dosen_wali' => 0
                    ]);
            }
        }

        DB::table('user_dosen')
            ->where('id_pegawai', $request->id_pegawai)
            ->update([
                'dosen_wali' => 0
            ]);


        return true;
    }

    public function edittranskipakademik(Request $request)
    {
        // $tgl = (isset($request->tgl)) ? Carbon::createFromFormat('d-m-Y', $request->tgl)->format('Y-m-d') : '';
        $nim =  $request->enim;
        $list_ba = collect(DB::select("SELECT * FROM akd_kelengkapan_transkrip WHERE nim = '$nim'"))->count();

        if ($list_ba > 0) {
            $edittranskipnilai = DB::table('akd_kelengkapan_transkrip')
                ->where('nim', $request->enim)
                ->update([
                    'no_transkrip'  =>  $request->no_transkip,
                    'no_sk'  =>  $request->no_sk_banpt,
                    'status_akreditasi'  =>  $request->status_akreditasi,
                    'tanggal_yudicium'  =>  $request->tgl_yudisium,
                    'tanggal_lulus'  =>  $request->tgl_lulus,
                    'judul_skripsi_indo'  =>  $request->judul_skripsi_indo,
                    'no_ijazah'  =>  $request->no_ijazah,
                    'judul_skripsi_inggris'  =>  $request->judul_skripsi_inggris
                ]);
        } else {
            DB::table('akd_kelengkapan_transkrip')->insert([
                'nim'  =>  $request->enim,
                'no_transkrip'  =>  $request->no_transkip,
                'no_sk'  =>  $request->no_sk_banpt,
                'status_akreditasi'  =>  $request->status_akreditasi,
                'tanggal_yudicium'  =>  $request->tgl_yudisium,
                'tanggal_lulus'  =>  $request->tgl_lulus,
                'judul_skripsi_indo'  =>  $request->judul_skripsi_indo,
                'no_ijazah'  =>  $request->no_ijazah,
                'judul_skripsi_inggris'  =>  $request->judul_skripsi_inggris,
                'keterangan'  =>  'A'
            ]);
            DB::table('akd_mahasiswa')
                ->where('nim', $request->enim)
                ->update([
                    'lulus'  =>  '1'
                ]);
            // $statuslulus = DB::table('akd_mahasiswa')
            // ->where('nim', $request->enim)
            //     ->update([
            //         'lulus'  =>  '1'
            //     ]);
        }
        return response()->json(['success' => 'Berhasil ditambahkan !']);
    }

    public function ceknimakademik(Request $request)
    {
        $nim =  $request->nim;
        $ceknimakademik = DB::table('akd_kelengkapan_transkrip')->where('nim', $nim)->get();
        return $ceknimakademik;
    }
    public function edittampilagama()
    {
        $edittampilagama = DB::select("SELECT * FROM mst_agama");
        return $edittampilagama;
    }
    public function edittampilkelas()
    {
        $edittampilkelas = DB::select("SELECT * FROM akd_program_pendidikan");
        return $edittampilkelas;
    }
    public function edittampilstatusnikah()
    {
        $edittampilstatusnikah = DB::select("SELECT * FROM mst_status_nikah");
        return $edittampilstatusnikah;
    }
    public function edittampiljalurpmb()
    {
        $edittampiljalurpmb = DB::select("SELECT * FROM adm_jalur_pmb");
        return $edittampiljalurpmb;
    }
    public function edittampilkewarganegaraan()
    {
        $edittampilkewarganegaraan = DB::select("SELECT * FROM mst_kewarganegaraan");
        return $edittampilkewarganegaraan;
    }
    public function edittampiljenjangpendidikan()
    {
        $edittampiljenjangpendidikan = DB::select("SELECT * FROM mst_pendidikan");
        return $edittampiljenjangpendidikan;
    }
    public function edittampiljenispekerjaan()
    {
        $edittampiljenispekerjaan = DB::select("SELECT * FROM mst_pekerjaan");
        return $edittampiljenispekerjaan;
    }
    public function edittampilpenghasilan()
    {
        $edittampilpenghasilan = DB::select("SELECT * FROM mst_penghasilan");
        return $edittampilpenghasilan;
    }
    public function tampiljenistinggal()
    {
        $tampiljenistinggal = DB::select("SELECT * FROM mst_tinggal");
        return $tampiljenistinggal;
    }
    public function tampiltransportasi()
    {
        $tampiltransportasi = DB::select("SELECT * FROM mst_transportasi");
        return $tampiltransportasi;
    }
    public function tampiljalurpendaftaran()
    {
        $tampiljalurpendaftaran = DB::select("SELECT * FROM mst_jalur_pendaftaran");
        return $tampiljalurpendaftaran;
    }
    public function tampiljenispendaftaran()
    {
        $tampiljenispendaftaran = DB::select("SELECT * FROM mst_jenis_pendaftaran");
        return $tampiljenispendaftaran;
    }
    public function modal_sks_ambil2(Request $request)
    {
        $modal_sks_ambil2 = DB::select("SELECT g.smt_matakuliah,c.semester,c.tahun,c.nim,SUM(g.sks_matakuliah) AS sks,ROUND(SUM(a.nilai_akhir_angka * g.sks_matakuliah)/SUM(g.sks_matakuliah),2) AS ips 
        FROM akd_detail_krs a JOIN akd_krs b JOIN akd_heregistrasi c JOIN akd_kelas_kuliah d JOIN akd_penawaran_matakuliah e JOIN akd_matakuliah g
        WHERE a.id_krs=b.id_krs AND b.id_heregistrasi=c.id_heregistrasi AND a.id_kelas=d.id_kelas AND d.id_tawar=e.id_tawar AND e.id_matakuliah=g.id_matakuliah AND c.nim='$request->nim' GROUP BY c.tahun,c.semester");
        return $modal_sks_ambil2;
    }

    public function modal_ips_ambil(Request $request)
    {
        $modal_ips_ambil = DB::select("SELECT tabel1.nim,SUM(tabel1.sks_matakuliah) AS sks,ROUND(SUM(tabel1.sks_matakuliah * akd_predikat_nilai_huruf.mutu)/SUM(tabel1.sks_matakuliah),2) AS ipk FROM (SELECT akd_matakuliah.sks_matakuliah,akd_transkrip.nim,MIN(akd_transkrip.nilai) AS nilai
        FROM akd_transkrip JOIN akd_matakuliah ON akd_transkrip.id_matakuliah=akd_matakuliah.id_matakuliah WHERE akd_transkrip.nim='$request->nim' 
        GROUP BY akd_matakuliah.id_matakuliah ORDER BY akd_transkrip.id_matakuliah ASC) AS tabel1 JOIN akd_predikat_nilai_huruf ON tabel1.nilai=akd_predikat_nilai_huruf.nilai_huruf_akhir");
        return $modal_ips_ambil;
    }
    public function edittampilkegiatanakademik()
    {
        $edittampilkegiatanakademik = DB::select("SELECT * FROM akd_kalender_akademik WHERE NOT nama_kegiatan='null' GROUP BY nama_kegiatan ORDER BY kode_kegiatan_akademik ASC");
        return $edittampilkegiatanakademik;
    }
}

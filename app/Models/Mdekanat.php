<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class Mdekanat extends Model
{
    use HasFactory;

    public function data_acckrs(Request $request)
    {

        if ($request->dosen_wali == 1) {

            $data_acckrs = DB::select("SELECT akd_heregistrasi.id_heregistrasi, akd_krs.id_krs, akd_mahasiswa.nim,akd_mahasiswa.nama_mahasiswa, akd_krs.ip_kumulatif, akd_krs.batas_sks, akd_krs.sks_bayar, akd_krs.sks_ambil, akd_heregistrasi.krs, akd_program_studi.nama_program_studi, akd_heregistrasi.tahun, akd_heregistrasi.semester, IFNULL(adm_camaba.telp,'-') AS no_hp,akd_heregistrasi.valid_id,
            (SELECT IFNULL(SUM(akd_matakuliah.sks_matakuliah), 0) AS total_sks FROM akd_detail_krs
                    JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas
                    JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
                    JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
                    WHERE akd_detail_krs.id_krs=akd_krs.id_krs) as total_sks_ambil
                        FROM
            akd_heregistrasi
            INNER JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi
            INNER JOIN akd_mahasiswa ON akd_heregistrasi.nim = akd_mahasiswa.nim
            INNER JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi = akd_program_studi.kode_program_studi
            LEFT JOIN adm_camaba ON adm_camaba.no_pendaftaran = akd_mahasiswa.no_pendaftaran
            WHERE
            akd_heregistrasi.tahun = '" . $request->tahun . "' AND
            akd_heregistrasi.semester = '" . $request->semester . "' AND
            akd_mahasiswa.id_dosen_wali = '" . $request->id_dosen . "' GROUP BY akd_mahasiswa.nim ORDER BY akd_heregistrasi.krs ASC");
        } else {

            $data_acckrs = DB::select("SELECT akd_heregistrasi.id_heregistrasi, akd_krs.id_krs, akd_mahasiswa.nim,akd_mahasiswa.nama_mahasiswa, akd_krs.ip_kumulatif, akd_krs.batas_sks, akd_krs.sks_bayar, akd_krs.sks_ambil, akd_heregistrasi.krs, akd_program_studi.nama_program_studi, akd_heregistrasi.tahun, akd_heregistrasi.semester, IFNULL(adm_camaba.telp,'-') AS no_hp,akd_heregistrasi.valid_id,
            (SELECT IFNULL(SUM(akd_matakuliah.sks_matakuliah), 0) AS total_sks FROM akd_detail_krs
                    JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas
                    JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
                    JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah
                    WHERE akd_detail_krs.id_krs=akd_krs.id_krs) as total_sks_ambil
            FROM
            akd_heregistrasi
            INNER JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi
            INNER JOIN akd_mahasiswa ON akd_heregistrasi.nim = akd_mahasiswa.nim
            INNER JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi = akd_program_studi.kode_program_studi
            LEFT JOIN adm_camaba ON adm_camaba.no_pendaftaran = akd_mahasiswa.no_pendaftaran
            WHERE
            akd_heregistrasi.tahun = '" . $request->tahun . "' AND
            akd_heregistrasi.semester = '" . $request->semester . "' AND
            akd_mahasiswa.kode_fakultas = '" . $request->kode_fakultas . "' GROUP BY akd_mahasiswa.nim ORDER BY akd_heregistrasi.krs ASC");
        }
        return $data_acckrs;
    }

    public function data_transkripnilai(Request $request)
    {
        $data_transkripnilai = DB::select("SELECT * FROM akd_mahasiswa 
        JOIN akd_transkrip ON akd_mahasiswa.nim=akd_transkrip.nim
        JOIN akd_program_pendidikan ON akd_mahasiswa.kode_program_pendidikan=akd_program_pendidikan.kode_program_pendidikan
        JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi 
        WHERE akd_mahasiswa.tahun_angkatan='" . $request->tahun . "' 
        AND akd_mahasiswa.kode_fakultas='" . $request->kode_fakultas . "' 
        GROUP BY akd_transkrip.nim");
        return $data_transkripnilai;
    }

    public function ubahstatus_acckrs(Request $request)
    {


        $ubahstatus_acckrs = DB::table('akd_heregistrasi')
            ->where('id_heregistrasi', $request->id_her)
            ->update([
                'krs'  =>  $request->value
            ]);

        return $ubahstatus_acckrs;
    }

    public function dosenwali(Request $request)
    {
        $dosenwali = DB::select("SELECT id_userpeg, id_pegawai, email_login, nidn, CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, 
        kode_prodi, nama_program_studi, password, akd_program_studi.kode_fakultas, dosen_wali
        FROM user_dosen 
        INNER JOIN simpeg_pegawai ON user_dosen.id_pegawai = simpeg_pegawai.id
        INNER JOIN akd_program_studi ON akd_program_studi.kode_program_studi = simpeg_pegawai.kode_prodi
        INNER JOIN akd_fakultas ON akd_fakultas.kode_fakultas = akd_program_studi.kode_fakultas
        WHERE akd_program_studi.kode_fakultas = '" . $request->kode_fakultas . "' ORDER BY dosen_wali DESC");
        return $dosenwali;
    }

    public function daftarmhs_pa(Request $request)
    {
        $daftarmhs_pa = DB::select("SELECT akd_mahasiswa.nim, nama_mahasiswa, tahun_angkatan,nama_agama,nama_program_pendidikan, nama_program_studi, adm_camaba.telp AS no_hp, CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen_wali, IF(sks_ambil > 0, 'KRS','Tidak KRS') AS status_krs,tbl1.nim AS cekher,akd_mahasiswa.semester
        FROM akd_mahasiswa 
        LEFT JOIN adm_camaba ON adm_camaba.no_pendaftaran = akd_mahasiswa.no_pendaftaran
        LEFT JOIN mst_agama ON akd_mahasiswa.kode_agama = mst_agama.kode_agama
        LEFT JOIN akd_program_pendidikan ON akd_mahasiswa.kode_program_pendidikan = akd_program_pendidikan.kode_program_pendidikan
        LEFT JOIN (SELECT akd_heregistrasi.nim,akd_krs.sks_ambil FROM akd_heregistrasi
        LEFT JOIN akd_krs ON akd_krs.id_heregistrasi = akd_heregistrasi.id_heregistrasi WHERE akd_heregistrasi.tahun = '" . $request->tahun . "' AND akd_heregistrasi.semester='" . $request->semester . "') AS tbl1 ON akd_mahasiswa.nim=tbl1.nim 
        LEFT JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi = akd_program_studi.kode_program_studi
        LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_mahasiswa.id_dosen_wali
        WHERE akd_mahasiswa.lulus=0 AND akd_program_studi.kode_fakultas = '" . $request->kode_fakultas . "' ORDER BY tahun_angkatan DESC");

        return $daftarmhs_pa;
    }

    public function daftarmhs_prodi(Request $request)
    {
        $daftarmhs_prodi = DB::select("SELECT akd_mahasiswa.nim, nama_mahasiswa, tahun_angkatan, nama_agama, nama_program_pendidikan, nama_program_studi, adm_camaba.telp AS no_hp, CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama, gelar_belakang) AS dosen_wali, IF(sks_ambil > 0, 'KRS','Tidak KRS') AS status_krs, tbl1.nim AS cekher, akd_mahasiswa.semester
        FROM akd_mahasiswa 
        LEFT JOIN adm_camaba ON adm_camaba.no_pendaftaran = akd_mahasiswa.no_pendaftaran
        LEFT JOIN mst_agama ON akd_mahasiswa.kode_agama = mst_agama.kode_agama
        LEFT JOIN akd_program_pendidikan ON akd_mahasiswa.kode_program_pendidikan = akd_program_pendidikan.kode_program_pendidikan
        LEFT JOIN (SELECT akd_heregistrasi.nim, akd_krs.sks_ambil FROM akd_heregistrasi
        LEFT JOIN akd_krs ON akd_krs.id_heregistrasi = akd_heregistrasi.id_heregistrasi WHERE akd_heregistrasi.tahun = '" . $request->tahun . "' AND akd_heregistrasi.semester='" . $request->semester . "') AS tbl1 ON akd_mahasiswa.nim=tbl1.nim 
        LEFT JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi = akd_program_studi.kode_program_studi
        LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_mahasiswa.id_dosen_wali
        WHERE akd_mahasiswa.lulus=0 AND akd_mahasiswa.kode_program_studi = '" . $request->kode_program_studi . "' ORDER BY tahun_angkatan DESC");

        return $daftarmhs_prodi;
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
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND akd_program_studi.kode_fakultas = '" . $request->kode_fakultas . "'");

        return $makulpenawaran;
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
        WHERE b.kode_program_studi = '" . $request->kode_prodi . "' AND id_dosen_wali='" . $request->id . "'
        ORDER BY a.id_mhs DESC");
        return $list_mhs_already;
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

    public function hapus_mhs_dosen_wali(Request $request)
    {
        $query = DB::table('akd_mahasiswa')
            ->where('nim', $request->nim)
            ->update([
                'id_dosen_wali' => 0
            ]);

        return $query;
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

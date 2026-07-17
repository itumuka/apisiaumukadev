<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;


class Mmahasiswa extends Model
{
    use HasFactory;

    public function cek_bisa_krs(Request $request)
    {
        $smtr = $request->semester;
        $tahun = $request->tahun;
        $nim = $request->nim;

        $cek_kalender = collect(DB::select("SELECT * FROM akd_kalender_akademik WHERE kode_kegiatan_akademik='2' AND semester='$smtr' AND tahun='$tahun' ORDER BY tahun DESC"))->first();
        $cekbeasiswa = collect(DB::select("SELECT * FROM keu_beasiswa_mahasiswa WHERE status_aktif='1' AND nim='$nim'"))->count();

        $cek_her = collect(DB::select("SELECT akd_heregistrasi.id_heregistrasi 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $nim . "' 
        AND akd_heregistrasi.tahun = '" . $tahun . "' 
        AND akd_heregistrasi.semester='" . $smtr . "'"))->first();

        return response()->json([
            'kalendar' => $cek_kalender,
            'her' => $cek_her,
            'beasiswa' => $cekbeasiswa
        ]);
    }
    public function cek_bisa_cetak_kartuujian(Request $request)
    {
        $smtr = $request->semester;
        $tahun = $request->tahun;
        $nim = $request->nim;

        $cekheruts = DB::table('keu_batas_her')->where('id_batas', '2')->first();
        $batasuts = $cekheruts->tahun . "" . $cekheruts->bulanangka;
        $cekheruas = DB::table('keu_batas_her')->where('id_batas', '3')->first();
        $batasuas = $cekheruas->tahun . "" . $cekheruas->bulanangka;

        $cek_kalender_uts = collect(DB::select("SELECT * FROM akd_kalender_akademik WHERE kode_kegiatan_akademik='5' AND semester='$smtr' AND tahun='$tahun' ORDER BY tahun DESC"))->first();

        $cek_kalender_uas = collect(DB::select("SELECT * FROM akd_kalender_akademik WHERE kode_kegiatan_akademik='8' AND semester='$smtr' AND tahun='$tahun' ORDER BY tahun DESC"))->first();

        // $cek_her = collect(DB::select("SELECT akd_heregistrasi.id_heregistrasi 
        // FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        // WHERE akd_heregistrasi.nim ='" . $nim . "' 
        // AND akd_heregistrasi.tahun = '" . $tahun . "' 
        // AND akd_heregistrasi.semester='" . $smtr . "'"))->first();

        $cek_her = collect(DB::select("SELECT akd_heregistrasi.id_heregistrasi 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $nim . "' 
        AND akd_heregistrasi.tahun = '" . $tahun . "' 
        AND akd_heregistrasi.semester='" . $smtr . "'"))->first();


        // $statusuts = DB::select("SELECT nim FROM keu_tagihan WHERE nim='" . $request->nim . "' AND tahun='" . $tahun . "' AND semester='" . $smtr . "' AND ( nama_biaya LIKE 'SPP VARIABLE%' OR nama_biaya LIKE '%SPP Tetap Kelas Pegawai%' OR nama_biaya LIKE '%PEMBIAYAAN SPP BPE%' ) AND status='1'");
        // $cekstatusuts = collect($statusuts)->count();

        // cek uts
        $cekutsnunggak = collect(DB::select("SELECT id_tagihan FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) < '$batasuts' AND STATUS=0 AND nim='" . $nim . "'"))->count();
        $cekutspatokan = collect(DB::select("SELECT id_tagihan FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) = '$batasuts' AND STATUS=1 AND nim='" . $nim . "'"))->count();
        $cekdispenuts = collect(DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $nim . "' AND semester='$smtr' AND tahun='$tahun' AND jenis='UTS'"))->count();
        $cekstatusuts = 0;
        if ($cekutsnunggak == 0 && $cekutspatokan > 0) {
            $cekstatusuts = 1;
        } else {
            if ($cekdispenuts > 0) {
                $cekstatusuts = 1;
            } else {
                $cekstatusuts = 0;
            }
        }
        // end uts


        // cek uas
        $cekuasnunggak = collect(DB::select("SELECT id_tagihan FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) < '$batasuas' AND STATUS=0 AND nim='" . $nim . "'"))->count();
        $cekuaspatokan = collect(DB::select("SELECT id_tagihan FROM keu_tagihan WHERE CONCAT(kd_tahun,kd_bulan) = '$batasuas' AND STATUS=1 AND nim='" . $nim . "'"))->count();
        $cekbayaruas = collect(DB::select("SELECT id_tagihan FROM keu_tagihan WHERE nama_biaya = 'Ujian Akhir Semester' AND STATUS=1 AND nim='" . $nim . "' AND semester='$smtr' AND tahun='$tahun'"))->count();
        $cekdispenuas = collect(DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $nim . "' AND semester='$smtr' AND tahun='$tahun' AND jenis='UAS'"))->count();
        $cekstatusuas = 0;
        if ($cekuasnunggak == 0 && $cekuaspatokan > 0 && $cekbayaruas == 1) {
            $cekstatusuas = 1;
        } else {
            if ($cekdispenuas > 0) {
                $cekstatusuas = 1;
            } else {
                $cekstatusuas = 0;
            }
        }
        // end uas


        // dispensasi
        $querydispen = DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $request->nim . "' AND tahun='" . $tahun . "' AND semester='" . $smtr . "' AND jenis='UTS'");
        $cekdispen = collect($querydispen)->count();

        $querydispenuas = DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $request->nim . "' AND tahun='" . $tahun . "' AND semester='" . $smtr . "' AND jenis='UAS'");
        $cekdispenuas = collect($querydispenuas)->count();

        $cekbeasiswa = collect(DB::select("SELECT * FROM keu_beasiswa_mahasiswa WHERE status_aktif='1' AND nim='$nim'"))->count();

        return response()->json([
            'kalendar_uts_tanggal_mulai' => date('Y-m-d', strtotime("-15 day", strtotime($cek_kalender_uts->tanggal_mulai))),
            'kalendar_uts_tanggal_akhir' => date('Y-m-d', strtotime("+1 day", strtotime($cek_kalender_uts->tanggal_akhir))),
            'kalendar_uas_tanggal_mulai' => date('Y-m-d', strtotime("-15 day", strtotime($cek_kalender_uas->tanggal_mulai))),
            'kalendar_uas_tanggal_akhir' => date('Y-m-d', strtotime("+1 day", strtotime($cek_kalender_uas->tanggal_akhir))),
            'her' => $cek_her,
            'cekstatusuas' => $cekstatusuas,
            'cekstatusuts' => $cekstatusuts,
            'cekdispen' => $cekdispen,
            'cekdispenuas' => $cekdispenuas,
            'cekbeasiswa' => $cekbeasiswa
        ]);
    }
    public function cek_bisa_revisikrs(Request $request)
    {
        $smtr = $request->semester;
        $tahun = $request->tahun;

        $cek_kalender = collect(DB::select("SELECT * FROM akd_kalender_akademik WHERE kode_kegiatan_akademik='3' AND semester='$smtr' AND tahun='$tahun' ORDER BY tahun DESC"))->first();

        return  $cek_kalender;
    }

    public function filter_khs(Request $request)
    {

        $filterkhs = DB::select("SELECT * FROM 
        (
                SELECT akd_heregistrasi.id_heregistrasi,tahun, semester, IF(semester='1', CONCAT_WS(' ', tahun, 'Ganjil'), CONCAT_WS(' ', tahun, 'Genap')) AS tahun_ajaran 
                FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
                WHERE akd_heregistrasi.nim='" . Session::get('username') . "'
        ) ta WHERE tahun_ajaran LIKE '%{$request->search}%'");

        if (!empty($filterkhs[0]->id_heregistrasi)) {
            foreach ($filterkhs as $namafilterkhs) {
                $filterkhsArray[] = array(
                    "id" => $namafilterkhs->id_heregistrasi,
                    "text" => $namafilterkhs->tahun_ajaran
                );
            }
        } else {
            $filterkhsArray[] = array(
                "id" => '',
                "text" => '',
            );
        }
        return response()->json(['data' => $filterkhsArray]);
        // return $filterkhs;
    }

    public function select_khs(Request $request)
    {

        $select_khs = DB::select("SELECT akd_heregistrasi.id_heregistrasi,tahun, semester, IF(semester='1', CONCAT_WS(' ', tahun, 'Ganjil'), CONCAT_WS(' ', tahun, 'Genap')) AS tahun_ajaran 
                FROM akd_heregistrasi
                WHERE akd_heregistrasi.nim='" . $request->nim . "' and akd_heregistrasi.krs=1");

        return $select_khs;
    }

    public function datakhsold(Request $request)
    {

        $check_herregistrasi = collect(DB::select("SELECT akd_heregistrasi.id_heregistrasi 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'"))->first();

        $id_her = isset($request->id_her) ? $request->id_her : $check_herregistrasi->id_heregistrasi;

        $get_data = DB::connection('mysql')->select('SELECT akd_krs.id_krs, kode_matakuliah, nama_matakuliah, akd_matakuliah.sks_matakuliah AS sks, akd_penawaran_matakuliah.smt_matakuliah AS semester, nama_kelas, akd_detail_krs.id_kelas, 
        kode_ruang, jumlah_peserta, nilai_uts, nilai_huruf_akhir,
        ROUND(akd_predikat_nilai_huruf.mutu * akd_matakuliah.sks_matakuliah,2) AS total_nilai, ROUND(akd_matakuliah.sks_matakuliah*mutu,2) AS kum_sksmutu
        FROM akd_krs
        LEFT JOIN akd_detail_krs ON akd_detail_krs.id_krs = akd_krs.id_krs
        LEFT JOIN akd_predikat_nilai_huruf ON akd_predikat_nilai_huruf.nilai_huruf_akhir = akd_detail_krs.nilai_akhir_huruf
        LEFT JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
        LEFT JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
        LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        WHERE akd_krs.id_heregistrasi = "' . $id_her . '"');

        return $get_data;
    }

    public function datakhs(Request $request)
    {

        $check_herregistrasi = collect(DB::select("SELECT akd_heregistrasi.id_heregistrasi 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.krs = 1 
        AND akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'"))->first();

        $id_her = isset($request->id_her) ? $request->id_her : $check_herregistrasi->id_heregistrasi;
        $kode_nilai = $request->kode_nilai;
        // $data = [];
        $get_data = DB::connection('mysql')->select('SELECT akd_krs.id_krs, kode_matakuliah, nama_matakuliah, akd_matakuliah.sks_matakuliah AS sks, akd_penawaran_matakuliah.smt_matakuliah AS semester, nama_kelas, akd_detail_krs.id_kelas, kode_ruang, jumlah_peserta, nilai_uts, nilai_uas, nilai_tugas, nilai_kuis, nilai_praktek, kehadiran, nilai_akhir_angka, nilai_akhir_huruf, nilai_huruf_akhir, ROUND(akd_predikat_nilai_huruf.mutu * akd_matakuliah.sks_matakuliah, 2) AS total_nilai, ROUND(akd_matakuliah.sks_matakuliah * mutu, 2) AS kum_sksmutu
            FROM akd_krs
            JOIN akd_detail_krs ON akd_detail_krs.id_krs = akd_krs.id_krs
            LEFT JOIN akd_predikat_nilai_huruf 
                   ON akd_predikat_nilai_huruf.nilai_huruf_akhir = akd_detail_krs.nilai_akhir_huruf
                   AND akd_predikat_nilai_huruf.kode_nilai = "' . $kode_nilai . '"
            JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
            LEFT JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
            LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        WHERE akd_krs.id_heregistrasi = "' . $id_her . '" ORDER BY id_kelas ASC');

        return $get_data;
    }

    public function ambilkrs(Request $request)
    {
        $q_herhit = collect(DB::select("SELECT SUM(sks_matakuliah) AS jumlah FROM (SELECT sks_matakuliah 
        FROM akd_transkrip,akd_matakuliah 
        WHERE akd_transkrip.nim ='" . $request->nim . "' AND akd_matakuliah.id_matakuliah=akd_transkrip.id_matakuliah
        GROUP BY akd_transkrip.id_matakuliah) AS tbl1"))->first();
        $mhscek = collect(DB::select("SELECT tahun_kurikulum, kode_penilaian FROM akd_mahasiswa WHERE nim='" . $request->nim . "'"))->first();

        $jumlahskstotalmhs = $q_herhit->jumlah;

        $batassks = collect(DB::select("SELECT * 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'"))->first();
        if (intval($jumlahskstotalmhs) >= 100 && intval($jumlahskstotalmhs) < 122) {
            $makul = DB::select("SELECT id_kelas, nim, akd_penawaran_matakuliah.id_tawar, akd_matakuliah.id_matakuliah, hari, CONCAT_WS(' s/d ', jam_mulai, jam_selesai) AS jam, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah AS sks,  akd_penawaran_matakuliah.smt_matakuliah, nama_kelas, 
            CONCAT(gelar_depan,' ',simpeg_pegawai.nama,IF(gelar_belakang IS NULL,' ',IF(gelar_belakang ='',' ',', ')),gelar_belakang) AS dosen, kode_ruang, jumlah_peserta, akd_kelas_kuliah.kapasitas_ruang, 
            (akd_kelas_kuliah.kapasitas_ruang-jumlah_peserta) AS sisakuota, 
            akd_penawaran_matakuliah.tahun, akd_penawaran_matakuliah.kode_program_studi, nilai, (SELECT krs FROM akd_heregistrasi WHERE tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND nim='" . $request->nim . "') AS statuskrs,
            (SELECT COUNT(id_detail_krs) AS cekpilih FROM  akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND id_kelas=akd_kelas_kuliah.id_kelas) AS cek_pilih,
			(SELECT nilai_akhir_huruf FROM akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas) AS na_huruf
            FROM akd_penawaran_matakuliah
            JOIN akd_kelas_kuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
            JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
            LEFT JOIN (SELECT akd_transkrip.id_matakuliah, nim, nilai, nilai_uts FROM akd_matakuliah 
            LEFT JOIN akd_transkrip ON akd_matakuliah.id_matakuliah = akd_transkrip.id_matakuliah
            WHERE nim = '" . $request->nim . "') cek_nilai ON akd_matakuliah.id_matakuliah = cek_nilai.id_matakuliah
            JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' 
            AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND akd_matakuliah.tahun_kurikulum='" . $mhscek->tahun_kurikulum . "' 
            AND akd_penawaran_matakuliah.kode_program_studi='" . $request->kode_prodi . "' AND akd_matakuliah.nama_matakuliah NOT LIKE 'skripsi%'
            ORDER BY akd_penawaran_matakuliah.smt_matakuliah,akd_matakuliah.id_matakuliah ASC
            ");
        } else if (intval($jumlahskstotalmhs) >= 122) {
            $makul = DB::select("SELECT id_kelas, nim, akd_penawaran_matakuliah.id_tawar, akd_matakuliah.id_matakuliah, hari, CONCAT_WS(' s/d ', jam_mulai, jam_selesai) AS jam, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah AS sks,  akd_penawaran_matakuliah.smt_matakuliah, nama_kelas, 
            CONCAT(gelar_depan,' ',simpeg_pegawai.nama,IF(gelar_belakang IS NULL,' ',IF(gelar_belakang ='',' ',', ')),gelar_belakang) AS dosen, kode_ruang, jumlah_peserta, akd_kelas_kuliah.kapasitas_ruang, 
            (akd_kelas_kuliah.kapasitas_ruang-jumlah_peserta) AS sisakuota, 
            akd_penawaran_matakuliah.tahun, akd_penawaran_matakuliah.kode_program_studi, nilai, (SELECT krs FROM akd_heregistrasi WHERE tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND nim='" . $request->nim . "') AS statuskrs,
            (SELECT COUNT(id_detail_krs) AS cekpilih FROM  akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND id_kelas=akd_kelas_kuliah.id_kelas) AS cek_pilih,
			(SELECT nilai_akhir_huruf FROM akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas) AS na_huruf
            FROM akd_penawaran_matakuliah
            JOIN akd_kelas_kuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
            JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
            LEFT JOIN (SELECT akd_transkrip.id_matakuliah, nim, nilai, nilai_uts FROM akd_matakuliah 
            LEFT JOIN akd_transkrip ON akd_matakuliah.id_matakuliah = akd_transkrip.id_matakuliah
            WHERE nim = '" . $request->nim . "') cek_nilai ON akd_matakuliah.id_matakuliah = cek_nilai.id_matakuliah
            JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' 
            AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND akd_matakuliah.tahun_kurikulum='" . $mhscek->tahun_kurikulum . "' 
            AND akd_penawaran_matakuliah.kode_program_studi='" . $request->kode_prodi . "'
            ORDER BY akd_penawaran_matakuliah.smt_matakuliah,akd_matakuliah.id_matakuliah ASC
            ");
        } else {
            $makul = DB::select("SELECT id_kelas, nim, akd_penawaran_matakuliah.id_tawar, akd_matakuliah.id_matakuliah, hari, CONCAT_WS(' s/d ', jam_mulai, jam_selesai) AS jam, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah AS sks,  akd_penawaran_matakuliah.smt_matakuliah, nama_kelas, 
            CONCAT(gelar_depan,' ',simpeg_pegawai.nama,IF(gelar_belakang IS NULL,' ',IF(gelar_belakang ='',' ',', ')),gelar_belakang) AS dosen, kode_ruang, jumlah_peserta, akd_kelas_kuliah.kapasitas_ruang, 
            (akd_kelas_kuliah.kapasitas_ruang-jumlah_peserta) AS sisakuota, 
            akd_penawaran_matakuliah.tahun, akd_penawaran_matakuliah.kode_program_studi, nilai, (SELECT krs FROM akd_heregistrasi WHERE tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND nim='" . $request->nim . "') AS statuskrs,
            (SELECT COUNT(id_detail_krs) AS cekpilih FROM  akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND id_kelas=akd_kelas_kuliah.id_kelas) AS cek_pilih,
			(SELECT nilai_akhir_huruf FROM akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas) AS na_huruf
            FROM akd_penawaran_matakuliah
            JOIN akd_kelas_kuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
            JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
            LEFT JOIN (SELECT akd_transkrip.id_matakuliah, nim, nilai, nilai_uts FROM akd_matakuliah 
            LEFT JOIN akd_transkrip ON akd_matakuliah.id_matakuliah = akd_transkrip.id_matakuliah
            WHERE nim = '" . $request->nim . "') cek_nilai ON akd_matakuliah.id_matakuliah = cek_nilai.id_matakuliah
            JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
            WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' 
            AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND akd_matakuliah.tahun_kurikulum='" . $mhscek->tahun_kurikulum . "' 
            AND akd_penawaran_matakuliah.kode_program_studi='" . $request->kode_prodi . "' 
            ORDER BY akd_penawaran_matakuliah.smt_matakuliah,akd_matakuliah.id_matakuliah ASC");
//             $makul = DB::select("SELECT id_kelas, nim, akd_penawaran_matakuliah.id_tawar, akd_matakuliah.id_matakuliah, hari, CONCAT_WS(' s/d ', jam_mulai, jam_selesai) AS jam, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah AS sks,  akd_penawaran_matakuliah.smt_matakuliah, nama_kelas, 
//             CONCAT(gelar_depan,' ',simpeg_pegawai.nama,IF(gelar_belakang IS NULL,' ',IF(gelar_belakang ='',' ',', ')),gelar_belakang) AS dosen, kode_ruang, jumlah_peserta, akd_kelas_kuliah.kapasitas_ruang, 
//             (akd_kelas_kuliah.kapasitas_ruang-jumlah_peserta) AS sisakuota, 
//             akd_penawaran_matakuliah.tahun, akd_penawaran_matakuliah.kode_program_studi, nilai, (SELECT krs FROM akd_heregistrasi WHERE tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND nim='" . $request->nim . "') AS statuskrs,
//             (SELECT COUNT(id_detail_krs) AS cekpilih FROM  akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND id_kelas=akd_kelas_kuliah.id_kelas) AS cek_pilih,
// 			(SELECT nilai_akhir_huruf FROM akd_detail_krs WHERE id_krs='" . $batassks->id_krs . "' AND akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas) AS na_huruf
//             FROM akd_penawaran_matakuliah
//             JOIN akd_kelas_kuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
//             JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
//             LEFT JOIN (SELECT akd_transkrip.id_matakuliah, nim, nilai, nilai_uts FROM akd_matakuliah 
//             LEFT JOIN akd_transkrip ON akd_matakuliah.id_matakuliah = akd_transkrip.id_matakuliah
//             WHERE nim = '" . $request->nim . "') cek_nilai ON akd_matakuliah.id_matakuliah = cek_nilai.id_matakuliah
//             JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
//             WHERE akd_penawaran_matakuliah.tahun='" . $request->tahun . "' 
//             AND akd_penawaran_matakuliah.semester='" . $request->semester . "' AND akd_matakuliah.tahun_kurikulum='" . $mhscek->tahun_kurikulum . "' 
//             AND akd_penawaran_matakuliah.kode_program_studi='" . $request->kode_prodi . "' AND akd_matakuliah.nama_matakuliah NOT LIKE 'skripsi%' AND akd_matakuliah.nama_matakuliah NOT LIKE 'kuliah kerja%' AND akd_matakuliah.nama_matakuliah NOT LIKE 'KKN%' 
//             ORDER BY akd_penawaran_matakuliah.smt_matakuliah,akd_matakuliah.id_matakuliah ASC");
        }

        // return $ambilkrs;
        return response()->json([
            'makul' => $makul,
			'kode_nilai' => $mhscek->kode_penilaian,
            'batasambilsks' => $batassks
        ]);
    }

// public function simpan_krs(Request $request)
// {
//     $nim       = $request->nim;
//     $tahun     = $request->tahun;
//     $semester  = $request->semester;
//     $id_class  = $request->id_kelas;
//     $id_tawar  = $request->id_tawar;
//     $year      = date('Y');

//     // 1. Cek herregistrasi & KRS
//     $her = DB::table('akd_heregistrasi as h')
//         ->join('akd_krs as k', 'h.id_heregistrasi', '=', 'k.id_heregistrasi')
//         ->where('h.nim', $nim)
//         ->where('h.tahun', $tahun)
//         ->where('h.semester', $semester)
//         ->select('h.id_heregistrasi', 'k.id_krs')
//         ->first();

//     if (!$her) {
//         return response()->json(['error' => 'Herregistrasi belum ditemukan']);
//     }

//     $id_her = $her->id_heregistrasi;
//     $id_krs = $her->id_krs;

//     // 2. Ambil id_matakuliah dari penawaran
//     $rIDmatkul = DB::table('akd_penawaran_matakuliah')
//         ->where('id_tawar', $id_tawar)
//         ->select('id_matakuliah')
//         ->first();

//     // 3. Ambil data mahasiswa
//     $mhs = DB::table('akd_mahasiswa as m')
//         ->join('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
//         ->where('m.nim', $nim)
//         ->select('m.tahun_angkatan', 'p.nama_program_studi', 'm.kode_program_studi')
//         ->first();

//     if (!$mhs) {
//         return response()->json(['error' => 'Data mahasiswa tidak ditemukan']);
//     }

//     $angkatan = $mhs->tahun_angkatan;

//     // 4. Hitung IPK / IPS (sederhana)
//     $ips_f = 0;
//     if ($angkatan != $year) {
//         $transkrip = DB::table('akd_transkrip as t')
//             ->join('akd_matakuliah as m', 't.id_matakuliah', '=', 'm.id_matakuliah')
//             ->join('akd_predikat_nilai_huruf as p', 't.nilai', '=', 'p.nilai_huruf_akhir')
//             ->where('t.nim', $nim)
//             ->select('m.sks_matakuliah', 'p.mutu')
//             ->get();

//         $totalSks = 0;
//         $totalNilai = 0;
//         foreach ($transkrip as $row) {
//             $totalSks   += (int) $row->sks_matakuliah;
//             $totalNilai += (int) $row->sks_matakuliah * (float) $row->mutu;
//         }
//         $ips_f = $totalSks ? number_format($totalNilai / $totalSks, 2) : 0;
//     }

//     // 5. Ambil info kelas
//     $kelas = DB::table('akd_kelas_kuliah as k')
//         ->join('akd_penawaran_matakuliah as t', 'k.id_tawar', '=', 't.id_tawar')
//         ->join('akd_matakuliah as m', 't.id_matakuliah', '=', 'm.id_matakuliah')
//         ->where('k.id_kelas', $id_class)
//         ->where('k.id_tawar', $id_tawar)
//         ->select('k.kapasitas_ruang', 'k.jumlah_peserta', 'k.hari', 'k.jam_mulai',
//             't.kode_bayar', 'm.sks_matakuliah', 'm.nama_matakuliah')
//         ->first();

//     if (!$kelas) {
//         return response()->json(['error' => 'Data kelas tidak ditemukan']);
//     }

//     // 6. Cek tabrakan jadwal
//     $tabrakan = DB::table('akd_detail_krs as d')
//         ->join('akd_kelas_kuliah as k', 'd.id_kelas', '=', 'k.id_kelas')
//         ->where('d.id_krs', $id_krs)
//         ->where('k.hari', $kelas->hari)
//         ->where('k.jam_mulai', $kelas->jam_mulai)
//         ->exists();

//     if ($tabrakan) {
//         return response()->json(['error' => 'Jadwal tabrakan dengan matakuliah lain']);
//     }

//     // 7. Cek apakah matakuliah sudah pernah diambil
//     $sudahAmbil = DB::table('akd_detail_krs as d')
//         ->join('akd_kelas_kuliah as k', 'd.id_kelas', '=', 'k.id_kelas')
//         ->join('akd_penawaran_matakuliah as t', 'k.id_tawar', '=', 't.id_tawar')
//         ->where('d.id_krs', $id_krs)
//         ->where('t.id_matakuliah', $rIDmatkul->id_matakuliah)
//         ->exists();

//     if ($sudahAmbil) {
//         return response()->json(['error' => 'Matakuliah ini sudah pernah diambil']);
//     }

//     // 8. Cek kapasitas kelas
//     if ($kelas->jumlah_peserta >= $kelas->kapasitas_ruang) {
//         return response()->json(['error' => 'Kelas sudah penuh']);
//     }

//     // 9. Ambil data KRS mahasiswa
//     $krs = DB::table('akd_krs')
//         ->where('id_krs', $id_krs)
//         ->where('id_heregistrasi', $id_her)
//         ->first();

//     if (!$krs) {
//         return response()->json(['error' => 'Data KRS tidak ditemukan']);
//     }

//     // Hitung total SKS
//     $ambilSKS = DB::table('akd_detail_krs as d')
//         ->join('akd_kelas_kuliah as k', 'd.id_kelas', '=', 'k.id_kelas')
//         ->join('akd_penawaran_matakuliah as t', 'k.id_tawar', '=', 't.id_tawar')
//         ->where('d.id_krs', $id_krs)
//         ->sum('t.sks_matakuliah');

//     $pengambilan_sks = $ambilSKS + $kelas->sks_matakuliah;

//     if ($krs->batas_sks && $pengambilan_sks > $krs->batas_sks) {
//         return response()->json(['error' => 'Anda melebihi batas pengambilan SKS']);
//     }

//     // 10. Update KRS
//     DB::table('akd_krs')
//         ->where('id_krs', $id_krs)
//         ->update([
//             'sks_ambil' => $pengambilan_sks,
//             'sks_bayar' => $krs->sks_bayar + $kelas->sks_matakuliah,
//             'waktu_krs' => now()
//         ]);

//     // 11. Update jumlah peserta kelas
//     DB::table('akd_kelas_kuliah')
//         ->where('id_kelas', $id_class)
//         ->increment('jumlah_peserta');

//     // 12. Cek prasyarat
//     $prasyarat = DB::table('akd_prasyarat_matakuliah as p')
//         ->join('akd_matakuliah as m1', 'p.id_matakuliah', '=', 'm1.id_matakuliah')
//         ->join('akd_matakuliah as m2', 'p.id_matakuliah_prasyarat', '=', 'm2.id_matakuliah')
//         ->where('p.id_matakuliah', $rIDmatkul->id_matakuliah)
//         ->select('p.id_matakuliah_prasyarat', 'm2.nama_matakuliah as makul_syarat')
//         ->first();

//     if ($prasyarat) {
//         $lulusPrasyarat = DB::table('akd_transkrip')
//             ->where('id_matakuliah', $prasyarat->id_matakuliah_prasyarat)
//             ->where('nim', $nim)
//             ->exists();

//         if (!$lulusPrasyarat) {
//             return response()->json(['error' => 'Anda belum mengambil matakuliah prasyarat: ' . $prasyarat->makul_syarat]);
//         }
//     }

//     // 13. Insert detail KRS
//     DB::table('akd_detail_krs')->insert([
//         'id_krs'    => $id_krs,
//         'id_kelas'  => $id_class,
//         'dtime_krs' => now()
//     ]);

//     return response()->json(['success' => 'Data berhasil ditambahkan!']);
// }



    public function simpan_krs(Request $request)
    {

        $check_herregistrasi = DB::select("SELECT * 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'");

        $id_her = $check_herregistrasi[0]->id_heregistrasi;
        $id_krs = $check_herregistrasi[0]->id_krs;
        $id_class = $request->id_kelas;
        $id_tawar = $request->id_tawar;
        $tahun = $request->tahun;
        $year = date('Y');
        // dd($id_class);

        $rIDmatkul =  collect(DB::select("select id_matakuliah from akd_penawaran_matakuliah where id_tawar='" . $request->id_tawar . "'"))->first();

        $q = "select * from akd_mahasiswa,akd_program_studi where akd_mahasiswa.nim='" . $request->nim . "' and akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi";
        $x = DB::select($q);
        $l = collect($x)->first();
        $prodi = $l->nama_program_studi;
        $kode_prodi = $l->kode_program_studi;
        $angkatan = $l->tahun_angkatan;
        if ($angkatan == $year) {
            $total_sks = 0;
            $jml_ipk_makul_f = 0;
        } else {
            // region select data transkrip
            $q_1 = "select min(akd_transkrip.nilai),akd_transkrip.*,akd_matakuliah.*,akd_predikat_nilai_huruf.* from akd_transkrip,akd_matakuliah,akd_predikat_nilai_huruf where akd_transkrip.nim='" . $request->nim . "' and akd_transkrip.nilai=akd_predikat_nilai_huruf.nilai_huruf_akhir
                and akd_transkrip.id_matakuliah=akd_matakuliah.id_matakuliah group by akd_transkrip.id_matakuliah";
            $x_1 = DB::select($q_1);
            // $num_1 = collect($x_1)->first();
            $sub_nilai_total = 0;
            $total_sks = 0;

            foreach ($x_1 as $d_1) {
                $nilai1 = $d_1->nilai;
                $nama_matakuliah = $d_1->nama_matakuliah;
                $sks = $d_1->sks_matakuliah;
                $smt = $d_1->smt_matakuliah;
                $mutu = $d_1->mutu;
                $nilai_total = $sks * $mutu;
                $sub_nilai_total += $nilai_total;
                $total_sks += $sks;
                $jml_ipk_makul = $sub_nilai_total / $total_sks;
                $jml_ipk_makul_f = number_format($jml_ipk_makul, 2);
            }
            //        endregion select data transkrip

            //    region cek khs
            if ($request->semester == "2") {
                $under_smtr = "1";
            } else {
                $under_smtr = "2";
            }
            //    region cek krs sblumnya
            $cek_khs = "select id_heregistrasi from akd_heregistrasi where semester='" . $under_smtr . "' and tahun='" . $tahun . "' and nim='" . $request->nim . "'";
            $ada_khs = DB::select($cek_khs);
            $num_khs = collect($ada_khs)->count();
            //    endregion cek krs sblumnya
            if ($num_khs < 1) {
                $sub_nilai_mat = "0";
                $ips_f = "0";
            } else {
                $q_ka = "select * from akd_heregistrasi,akd_krs where akd_heregistrasi.semester='" . $under_smtr . "' and akd_heregistrasi.tahun='" . $tahun . "' and akd_heregistrasi.nim='$request->nim' and akd_heregistrasi.id_heregistrasi=akd_krs.id_heregistrasi";
                $x_ka = DB::select($q_ka);
                $d_ka = collect($x_ka)->first();
                $idKrs = $d_ka->id_krs;

                $q_va = "select * from akd_detail_krs,akd_kelas_kuliah,akd_penawaran_matakuliah where akd_detail_krs.id_krs='$idKrs' and akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas and akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
                and akd_detail_krs.nilai_akhir_huruf IS NOT NULL ";
                $x_va =  DB::select($q_va);
                $sub_sks_mat = 0;
                $sub_nilai_mat = 0;

                foreach ($x_va as $d_va) {
                    $idk = $d_va->id_kelas;
                    $nilai_hruf = $d_va->nilai_akhir_huruf;
                    $sks_mat = $d_va->sks_matakuliah;
                    $sm_mat = $d_va->smt_matakuliah;
                    $sub_sks_mat += $sks_mat;

                    $q_se = "select * from akd_predikat_nilai_huruf where nilai_huruf_akhir='" . $nilai_hruf . "'";
                    $x_se =  DB::select($q_se);
                    $d_se = collect($x_se)->first();
                    $mtu = $d_se->mutu;
                    $nilai_mat = $sks_mat * $mtu;
                    $sub_nilai_mat += $nilai_mat;
                    $ips = $sub_nilai_mat / $sub_sks_mat;
                    $ips_f = number_format($ips, 2);
                }
            }
            //    endregion cek khs
        }

        //        region select menampilkan data matakuliah
        $q_2 = "select * from akd_kelas_kuliah,akd_penawaran_matakuliah,akd_matakuliah WHERE akd_kelas_kuliah.id_kelas='" . $id_class . "' and akd_kelas_kuliah.id_tawar='" . $id_tawar . "' AND akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
                AND akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah";
        $x_2 = DB::select($q_2);
        $d_2 = collect($x_2)->first();
        $kapasitas_psrta = $d_2->kapasitas_ruang;
        $jml_psrta = $d_2->jumlah_peserta;
        $kode_bayar = $d_2->kode_bayar;
        $sks_ambil = $d_2->sks_matakuliah;
        $sks_bayar = $d_2->sks_matakuliah;
        $hari = $d_2->hari;
        $mulai = $d_2->jam_mulai;
        $tambahan = 1;
        $serta = $jml_psrta + $tambahan;

        //        endregion select menampilkan data matakuliah

        //        region cek jadwal tabrakan tidak
        // $q_rush = "select * from akd_detail_krs,akd_kelas_kuliah WHERE akd_detail_krs.id_krs='" . $id_krs . "' AND akd_detail_krs.id_kelas=akd_kelas_kuliah.id_kelas AND akd_kelas_kuliah.hari='" . $hari . "' AND akd_kelas_kuliah.jam_mulai='" . $mulai . "'";
        // $x_rush = DB::select($q_rush);
        // $num_cek = collect($x_rush)->count();
        // $d_rush = collect($x_rush)->first();

        // if ($num_cek > 0) {
        //     $msg = "Jadwal matakuliah yang dipilih tabrakan dengan matakuliah yang lain";
        //     return response()->json(['error' => $msg]);
        // }
        $exists = DB::table('akd_detail_krs')
                ->join('akd_kelas_kuliah', 'akd_detail_krs.id_kelas', '=', 'akd_kelas_kuliah.id_kelas')
                ->where('akd_detail_krs.id_krs', $id_krs)
                ->where('akd_kelas_kuliah.hari', $hari)
                ->where('akd_kelas_kuliah.jam_mulai', $mulai)
                ->exists();

            if ($exists) {
                $msg = "Jadwal matakuliah yang dipilih tabrakan dengan matakuliah yang lain";
                return response()->json(['error' => $msg]);
            }else {
            //        region select data dan update data krs
            $cekdata = "select * from akd_krs where id_krs='$id_krs' AND id_heregistrasi='$id_her'";
            $ada =  DB::select($cekdata);
            $l = collect($ada)->first();
            $batas = $l->batas_sks;
            $waktu = $l->waktu_krs;
            $ambil_sks = $l->sks_ambil;
            $bayar_sks = $l->sks_bayar;

            //hitung sks ambil
            $getSKS =  DB::select("select id_kelas from akd_detail_krs where id_krs='" . $id_krs . "'");

            foreach ($getSKS as $rSKS) {
                // Ambil data kelas
                $openKelas = DB::select("SELECT id_tawar FROM akd_kelas_kuliah WHERE id_kelas = ?", [$rSKS->id_kelas]);
                $rKelas = collect($openKelas)->first();
            
                // Jika tidak ada id_tawar, lanjutkan ke iterasi berikutnya
                if (!$rKelas || !$rKelas->id_tawar) {
                    continue;
                }
            
                // Ambil data penawaran matakuliah berdasarkan id_tawar
                $openPenawaran = DB::select("SELECT id_matakuliah, sks_matakuliah FROM akd_penawaran_matakuliah WHERE id_tawar = ?", [$rKelas->id_tawar]);
                $rPenawaran = collect($openPenawaran)->first();
            
                // Jika id_matakuliah cocok dengan rIDmatkul, tampilkan pesan error
                if ($rPenawaran && $rPenawaran->id_matakuliah == $rIDmatkul->id_matakuliah) {
                    return response()->json(['error' => 'Mohon maaf!! matakuliah ini telah diambil. Terima kasih']);
                }
            }

            $getSKS2 = DB::select("SELECT
                                    akd_penawaran_matakuliah.sks_matakuliah,
                                    akd_detail_krs.id_krs,
                                    akd_detail_krs.id_kelas,
                                    akd_matakuliah.nama_matakuliah,
                                    akd_matakuliah.sks_matakuliah
                                    FROM
                                    akd_detail_krs
                                    INNER JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
                                    INNER JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
                                    INNER JOIN akd_matakuliah ON akd_penawaran_matakuliah.id_matakuliah = akd_matakuliah.id_matakuliah
                                    WHERE
                                    akd_detail_krs.id_krs = '" . $id_krs . "'");
            $summary_SKS = 0;
            foreach ($getSKS2 as $rSKS) {
                $summary_SKS = $summary_SKS + intval($rSKS->sks_matakuliah);
            }



            $pengambilan_sks = $summary_SKS + $sks_ambil; //hitung SKS yang akan diambil ini
            $pembayaran_sks = $bayar_sks + $sks_bayar;
            if ($jml_psrta >= $kapasitas_psrta) {

                return response()->json(['error' => 'Maaf!!Jumlah peserta melebihi batas kapasitas ruangan,lakukan pengambilan matakuliah yang sama yang belum melebihi batas kuota ruangan']);
            } else {
                if ($waktu == "") {
                    if ($kode_bayar == "1") {  //bayar untuk SKS
                        //echo "****************************************************************kene 1 $sub_nilai_mat $ips_f $sks_ambil";
                        $query = "update akd_krs set sks_ambil='" . $pengambilan_sks . "',sks_bayar='" . $sks_bayar . "',waktu_krs='" . date('Y-m-d H:i:s') . "' where id_krs='" . $id_krs . "' AND id_heregistrasi='$id_her'";
                        $x_k =  DB::select($query);

                        //      region update jumlah peserta kuliah
                        $q_6 = "update akd_kelas_kuliah set jumlah_peserta='" . $serta . "' where id_kelas='" . $id_class . "' AND id_tawar='" . $id_tawar . "'";
                        $x_6 =  DB::select($q_6);
                        //      endregion update jumlah peserta kuliah
                    } else { //tidak bayar SKS
                        //echo "****************************************************************kene 2";
                        $query = "update akd_krs set sks_ambil='" . $pengambilan_sks . "',sks_bayar='" . $sks_bayar . "',waktu_krs= '" . date('Y-m-d H:i:s') . "' where id_krs='$id_krs' AND id_heregistrasi='" . $id_her . "'";
                        $x_k = DB::statement($query);

                        //      region update jumlah peserta kuliah
                        $q_6 = "update akd_kelas_kuliah set jumlah_peserta='" . $serta . "' where id_kelas='" . $id_class . "' AND id_tawar='" . $id_tawar . "'";
                        $x_6 = DB::statement($q_6);
                        //      endregion update jumlah peserta kuliah
                    }
                } else {
                    if ($pengambilan_sks <= $batas) {
                        if ($kode_bayar == "1") {
                            //echo "****************************************************************kene 3";
                            $q_a = "update akd_krs set sks_ambil='$pengambilan_sks',sks_bayar='$pembayaran_sks' where id_krs='$id_krs' AND id_heregistrasi='$id_her'";
                            $x_a = DB::statement($q_a);

                            //      region update jumlah peserta kuliah
                            $q_6 = "update akd_kelas_kuliah set jumlah_peserta='$serta' where id_kelas='$id_class' AND id_tawar='$id_tawar'";
                            $x_6 = DB::statement($q_6);
                            //      endregion update jumlah peserta kuliah

                        } else {
                            //echo "****************************************************************kene 4";
                            $q_b = "update akd_krs set sks_ambil='$pengambilan_sks',sks_bayar='$pembayaran_sks' where id_krs='$id_krs' AND id_heregistrasi='$id_her'";
                            $x_b = DB::statement($q_b);

                            //      region update jumlah peserta kuliah
                            $q_6 = "update akd_kelas_kuliah set jumlah_peserta='$serta' where id_kelas='$id_class' AND id_tawar='$id_tawar'";
                            $x_6 = DB::statement($q_6);
                            //      endregion update jumlah peserta kuliah
                        }
                    } else {
                        return response()->json(['error' => 'Anda melebihi batas pengambilan sks yang telah ditentukan, silahkan untuk dicek sekali lagi dan lakukan Revisi jika memungkinkan']);
                    }
                }
            }

            //cecking prasyarat
            $prasyarat = collect(DB::select('SELECT id_prasyarat, akd_prasyarat_matakuliah.id_matakuliah AS id_makul_reguler, a.nama_matakuliah AS makul_reguler, 
            akd_prasyarat_matakuliah.id_matakuliah_prasyarat AS id_makul_syarat, b.nama_matakuliah AS makul_syarat FROM akd_prasyarat_matakuliah 
            JOIN akd_matakuliah a ON akd_prasyarat_matakuliah.id_matakuliah = a.id_matakuliah
            JOIN akd_matakuliah b ON akd_prasyarat_matakuliah.id_matakuliah_prasyarat = b.id_matakuliah WHERE akd_prasyarat_matakuliah.id_matakuliah="' . $request->id_matakuliah . '"'));
            // $prasyarat = collect(DB::select('SELECT * FROM akd_prasyarat_matakuliah WHERE id_matakuliah="'.$request->id_matakuliah.'"'));
            $check_prasyarat = $prasyarat->count();
            $dataprasyarat = $prasyarat->first();
            if ($check_prasyarat > 1) {
                $prasyarat = collect(DB::select('SELECT * FROM akd_transkrip WHERE id_matakuliah="' . $dataprasyarat->id_matakuliah_prasyarat . '" AND nim="' . $request->nim . '" '))->count();

                if ($prasyarat > 1) {
                    if ($dataprasyarat->id_matakuliah_prasyarat != "") {
                        return response()->json(['error' => 'Anda belum mengambil matakuliah prasyarat ' . $dataprasyarat->makul_syarat . ', silahkan cek kembali !']);
                    }
                }
            }

            //        region insert record detail krs
            $acc = "0";
            $simpan_krs = DB::table('akd_detail_krs')->insert([
                'id_krs'  =>  $id_krs,
                'id_kelas'  =>  $request->id_kelas,
                'dtime_krs'  =>  date('Y-m-d H:i:s')
            ]);
            return response()->json(['success' => 'Data berhasil ditambahkan !']);
            //        endregion insert record detail krs
            //        endregion select data dan update data krs
        }
    }


    // public function cek_prasyarat(Request $request)
    // {

    //     $prasyarat = collect(DB::select('SELECT * FROM akd_prasyarat_matakuliah WHERE id_matakuliah="'.$request->id_matakuliah.'"'));
    //     $check_prasyarat = $prasyarat->count();
    //     $dataprasyarat = $prasyarat->first();
    //     if ($check_prasyarat > 1) {
    //         $prasyarat = collect(DB::select('SELECT * FROM akd_transkrip WHERE id_matakuliah="'.$dataprasyarat->id_matakuliah_prasyarat.'" AND nim="'.$request->nim.'" '))->count();

    //         if ($prasyarat < 1) {
    //             if($dataprasyarat->id_matakuliah_prasyarat == ""){
    //                 return response()->json(['success' => 1]);
    //             }else{
    //                 return response()->json(['error' => 'Anda belum mengambil matakuliah prasyarat, silahkan cek kembali !']);
    //             }

    //         } else {
    //             return response()->json(['success' => 1]);
    //         }

    //     }

    // }

    public function revisikrs(Request $request)
    {

        $check_herregistrasi = DB::select("SELECT * 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'");

        if (empty($check_herregistrasi)) {
            return [];
        }
        $id_her = $check_herregistrasi[0]->id_heregistrasi;

        $revisikrs = DB::select("SELECT hari, CONCAT_WS(' s/d ', TIME_FORMAT(jam_mulai, '%H:%i'), TIME_FORMAT(jam_selesai, '%H:%i')) AS jam, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah AS sks, akd_krs.id_krs, akd_kelas_kuliah.id_kelas,
        akd_penawaran_matakuliah.smt_matakuliah AS semester, nama_kelas, 
        CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, kode_ruang,akd_heregistrasi.krs, jumlah_peserta FROM akd_heregistrasi
        JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi
        JOIN akd_detail_krs ON akd_krs.id_krs = akd_detail_krs.id_krs
        JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
        JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
        JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
        WHERE akd_krs.id_heregistrasi='" . $id_her . "'   
        ORDER BY akd_kelas_kuliah.hari DESC");

        return $revisikrs;
    }


    public function hapus_revisikrs(Request $request)
    {

        $query = collect(DB::select("SELECT * from akd_krs,akd_kelas_kuliah,akd_penawaran_matakuliah,akd_matakuliah where akd_krs.id_krs='$request->id_krs' and akd_kelas_kuliah.id_kelas='$request->id_kelas' and akd_kelas_kuliah.id_tawar=akd_penawaran_matakuliah.id_tawar
        and akd_penawaran_matakuliah.id_matakuliah=akd_matakuliah.id_matakuliah"))->first();

        $peserta = $query->jumlah_peserta;
        $id_tawar = $query->id_tawar;
        $sks = $query->sks_matakuliah;
        $kode_bayar = $query->kode_bayar;
        $sks_ambil = $query->sks_ambil;
        $sks_bayar = $query->sks_bayar;
        $jml_sks_ambil = intval($sks_ambil) - intval($sks);
        $jml_sks_bayar = intval($sks_bayar) - intval($sks);
        $jumlah_akhir = $peserta - 1;

        DB::table('akd_detail_krs')
            ->where('id_krs', $request->id_krs)
            ->where('id_kelas', $request->id_kelas)
            ->delete();

        DB::table('akd_kelas_kuliah')
            ->where('id_kelas', $request->id_kelas)
            ->where('id_tawar', $id_tawar)
            ->update([
                'jumlah_peserta' => $jumlah_akhir
            ]);

        if ($kode_bayar == "1") {
            DB::table('akd_krs')
                ->where('id_krs', $request->id_krs)
                ->update([
                    'sks_ambil' => $jml_sks_ambil,
                    'sks_bayar' => $jml_sks_bayar
                ]);
        } else {
            DB::table('akd_krs')
                ->where('id_krs', $request->id_krs)
                ->update([
                    'sks_ambil' => $jml_sks_ambil,
                    'sks_bayar' => $jml_sks_bayar
                ]);
        }
    }

    public function presensimakul(Request $request)
    {

        $check_herregistrasi = collect(DB::select("SELECT * 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'"))->first();

        $id_her = isset($check_herregistrasi->id_heregistrasi) ? $check_herregistrasi->id_heregistrasi : 0;
        $nim = isset($check_herregistrasi->nim) ? $check_herregistrasi->nim : 0;

        // untuk mengaktifkan cegatan pembayaran UTS
        $querybyr1 = DB::select("SELECT * FROM (SELECT nim,(SELECT SUM(bayar) AS jum FROM keu_bayar aaa WHERE aaa.id_tagihan=keu_tagihan.id_tagihan) AS bayar FROM keu_tagihan 
        WHERE nim='" . $request->nim . "' AND tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND nama_biaya LIKE '%SPP VARIABLE%') AS tbl1 WHERE bayar IS NOT NULL");
        $querybyr2 = DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $request->nim . "' AND tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND jenis='UTS'");
        $querybyr3 = DB::select("SELECT nim FROM keu_beasiswa_mahasiswa WHERE nim='" . $request->nim . "' AND status_aktif='1'");
        $cekbyr1 = collect($querybyr1)->count();
        $cekbyr2 = collect($querybyr2)->count();
        $cekbyr3 = collect($querybyr3)->count();
        $cekbbyran = 1;
        if ($cekbyr1 == 0 && $cekbyr2 == 0 && $cekbyr3 == 0) {
            $cekbbyran = 0;
        }





        $statusuas = DB::select("SELECT nim FROM keu_tagihan WHERE nim='" . $request->nim . "' AND tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND ( nama_biaya LIKE 'SPP VARIABLE%' OR nama_biaya LIKE '%SPP Tetap Kelas Pegawai%' OR nama_biaya LIKE '%PEMBIAYAAN SPP BPE%' ) AND status='1'");
        $cekstatusuas1 = collect($statusuas)->count();
        $querybyruas2 = DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $request->nim . "' AND tahun='" . $request->tahun . "' AND semester='" . $request->semester . "' AND jenis='UAS'");
        $cekbyrnuas = 1;
        if ($cekstatusuas1 == 0 && $querybyruas2 == 0 && $cekbyr3 == 0) {
            $cekbyrnuas = 0;
        }
        // else {
        //     $cekbbyran = 1;
        // }

        //end cegatan pembayaran UTS


        // $presensimakul = DB::select("SELECT kode_matakuliah, nama_matakuliah, akd_kelas_kuliah.id_kelas as id_kelas,
        // CONCAT_WS(' s/d ', TIME_FORMAT(IFNULL(akd_berita_acara.jam_mulai,akd_kelas_kuliah.jam_mulai), '%H:%i'), 
        // TIME_FORMAT(IFNULL(akd_berita_acara.jam_selesai,akd_kelas_kuliah.jam_selesai), '%H:%i')) AS jam,
        // akd_penawaran_matakuliah.sks_matakuliah AS sks, akd_berita_acara.jam_mulai, akd_berita_acara.jam_selesai, akd_berita_acara.jam_selesai, akd_berita_acara.tgl as tgl, 
        // CASE DAYOFWEEK(akd_berita_acara.tgl)
        //     WHEN 1 THEN 'Minggu'
        //     WHEN 2 THEN 'Senin'
        //     WHEN 3 THEN 'Selasa'
        //     WHEN 4 THEN 'Rabu'
        //     WHEN 5 THEN 'Kamis'
        //     WHEN 6 THEN 'Jumat'
        //     WHEN 7 THEN 'Sabtu'
        // END AS hari, akd_kelas_kuliah.hari AS hari_semula, IF((NOW()>= CONCAT_WS(' ',akd_berita_acara.tgl,akd_berita_acara.jam_mulai)) AND (NOW()<= CONCAT_WS(' ',akd_berita_acara.tgl,akd_berita_acara.jam_selesai)), 1,0) AS button_in, akd_berita_acara.pertemuan_ke,
        // IF(akd_penawaran_matakuliah.smt_matakuliah = '1', 'Ganjil', 'Genap' ) AS semester, nama_kelas, 
        // CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, kode_ruang FROM akd_krs 
        // JOIN akd_detail_krs ON akd_krs.id_krs = akd_detail_krs.id_krs
        // JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
        // JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
        // JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        // LEFT JOIN akd_berita_acara ON akd_berita_acara.id_kelas = akd_kelas_kuliah.id_kelas
        // LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
        // WHERE akd_krs.id_heregistrasi='" . $id_her . "'   
        // ORDER BY akd_kelas_kuliah.hari DESC");

        $presensimakul = DB::select("SELECT *,IF(((NOW())>= CONCAT_WS(' ',tglbrt,jam_mulaibrt)) 
        AND ((NOW())<= CONCAT_WS(' ',tglbrt,jam_selesaibrt)), 1,0) AS button_in,
        CONCAT_WS(' s/d ', TIME_FORMAT(jam_mulaibrt, '%H:%i'), TIME_FORMAT(jam_selesaibrt, '%H:%i')) AS jam, CONCAT_WS(' s/d ', TIME_FORMAT(tbl1.jam_mulai, '%H:%i'), TIME_FORMAT(tbl1.jam_selesai, '%H:%i')) AS jam_semula,
        CASE DAYOFWEEK(tglbrt)
            WHEN 1 THEN 'Minggu'
            WHEN 2 THEN 'Senin'
            WHEN 3 THEN 'Selasa'
            WHEN 4 THEN 'Rabu'
            WHEN 5 THEN 'Kamis'
            WHEN 6 THEN 'Jumat'
            WHEN 7 THEN 'Sabtu'
        END AS hari,(SELECT id FROM akd_presensi_mhs WHERE id_kelas=tbl1.id_kelas AND pertemuan=tbl1.pertemuan_ke AND hadir LIKE '%$nim%') AS kehadiran FROM (SELECT akd_kelas_kuliah.id_kelas,kode_matakuliah, nama_matakuliah,akd_kelas_kuliah.jam_mulai,akd_kelas_kuliah.jam_selesai, 
        akd_penawaran_matakuliah.sks_matakuliah AS sks, akd_penawaran_matakuliah.url_rps, akd_kelas_kuliah.hari AS hari_semula,  
        IF(akd_penawaran_matakuliah.semester = '1', 'Ganjil', 'Genap' ) AS semester, akd_penawaran_matakuliah.smt_matakuliah, nama_kelas,
        (SELECT pertemuan_ke FROM akd_berita_acara a WHERE a.id_kelas=akd_detail_krs.id_kelas AND CONCAT_WS(' ',tgl,jam_selesai)>=(NOW()) ORDER BY tgl,jam_mulai LIMIT 1) AS pertemuan_ke,
        (SELECT tgl FROM akd_berita_acara a WHERE a.id_kelas=akd_detail_krs.id_kelas AND CONCAT_WS(' ',tgl,jam_selesai)>=(NOW()) ORDER BY tgl,jam_mulai LIMIT 1) AS tglbrt,
        (SELECT jam_mulai FROM akd_berita_acara a WHERE a.id_kelas=akd_detail_krs.id_kelas AND CONCAT_WS(' ',tgl,jam_selesai)>=(NOW()) ORDER BY tgl,jam_mulai LIMIT 1) AS jam_mulaibrt,
        (SELECT jam_selesai FROM akd_berita_acara a WHERE a.id_kelas=akd_detail_krs.id_kelas AND CONCAT_WS(' ',tgl,jam_selesai)>=(NOW()) ORDER BY tgl,jam_mulai LIMIT 1) AS jam_selesaibrt,
        CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, kode_ruang,(NOW()) as cektglwaktu,'" . $cekbbyran . "' as cekuts,'" . $cekbyrnuas . "' as cekuas FROM akd_detail_krs 
        JOIN akd_krs ON akd_krs.id_krs = akd_detail_krs.id_krs
        JOIN akd_heregistrasi ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi
        JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
        JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
        JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        LEFT JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
        WHERE akd_krs.id_heregistrasi='" . $id_her . "' AND akd_heregistrasi.krs='1'
        ORDER BY akd_kelas_kuliah.hari DESC) AS tbl1");
        return $presensimakul;
    }

    public function dispensasikhs(Request $request)
    {
        $cek = 0;
        $cekmhs = DB::table("akd_mahasiswa")->where("nim", $request->nim)->first();
        // $getmhs = collect($cekmhs)->first();


        $querybyrsisatag = DB::select("SELECT nim FROM keu_tagihan WHERE nim='" . $request->nim . "' AND tahun='2022' AND semester='1' AND nama_biaya LIKE '%SISA TAGIHAN%' AND status='0'");
        $cekssatagihanlama = collect($querybyrsisatag)->count();
        $query = DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $request->nim . "' AND tahun='2022' AND semester='2' AND jenis='KRS'");
        $cekdispen = collect($query)->count();
        if ($cekssatagihanlama > 0 && $cekdispen == 0) {
            $cek = 0;
        } else {
            $query = DB::select("SELECT nim FROM akd_dispensasi WHERE nim='" . $request->nim . "' AND tahun='2022' AND semester='2' AND jenis='KRS'");
            $cekdispen = collect($query)->count();
            if ($cekdispen > 1) {
                $cek = 1;
            } else {
                if ($cekmhs->kode_program_pendidikan == "1") {
                    $querybyr = DB::select("SELECT nim FROM keu_tagihan WHERE nim='" . $request->nim . "' AND tahun='2022' AND semester='2' AND (nama_biaya LIKE '%SPP Tetap%' OR nama_biaya LIKE '%SPP BPE%') AND status='1'");
                    $cekbyr = collect($querybyr)->count();
                    if ($cekbyr > 0) {
                        $cek = 1;
                    } else {
                        //cek pembayaran SPP Tetap lolos atau tidak (lolos 1 atau tidak lolos 0)
                        $cek = 1;
                    }
                } else {
                    $querybyr1 = DB::select("SELECT nim FROM keu_tagihan WHERE nim='" . $request->nim . "' AND tahun='2022' AND semester='2' AND (nama_biaya LIKE '%SPP Tetap%' OR nama_biaya LIKE '%SPP BPE%') AND status='1'");
                    $cekbyr1 = collect($querybyr1)->count();
                    if ($cekbyr1 > 0) {
                        $cek = 1;
                    } else {
                        $querybyr1 = DB::select("SELECT biaya,(SELECT SUM(bayar) AS jum FROM keu_bayar aaa WHERE aaa.id_tagihan=keu_tagihan.id_tagihan) AS bayar FROM keu_tagihan WHERE nim='" . $request->nim . "' AND tahun='2022' AND semester='2' AND (nama_biaya LIKE '%SPP Tetap%' OR nama_biaya LIKE '%SPP BPE%')");
                        $cekbyr12 = collect($querybyr1)->first();
                        if ((intval($cekbyr12->biaya) / 6) <= intval($cekbyr12->bayar == null ? 0 : $cekbyr12->bayar)) {
                            $cek = 1;
                        } else {
                            //cek pembayaran SPP Tetap lolos atau tidak (lolos 1 atau tidak lolos 0)
                            $cek = 1;
                        }
                    }
                }
            }
        }




        return $cek;
    }

    public function simpan_presensi_mhs(Request $request)
    {

        $nimarray = $request->nim;
        $tglJamnow = date('Y-m-d');
        $pertemuan = $request->pertemuan;
        $id_kelas = $request->id_kelas;

        $query = DB::select("SELECT id, id_kelas, tgl, pertemuan, hadir, sakit, ijin, alpha, created_at FROM akd_presensi_mhs WHERE id_kelas='" . $id_kelas . "' AND pertemuan ='" . $pertemuan . "'");
        $query1 = DB::select("SELECT id, id_kelas, tgl, pertemuan, hadir, sakit, ijin, alpha, created_at FROM akd_presensi_mhs WHERE id_kelas='" . $id_kelas . "' AND pertemuan ='" . $pertemuan . "' AND hadir LIKE '%$nimarray%'");
        $cek = collect($query)->count();
        $cekdouble = collect($query1)->count();
        if ($cekdouble == 0) {
            if ($cek > 0) {
                $ceknim = collect($query)->first();
                $nimhadir = $ceknim->hadir . '#' . $nimarray;
                DB::table('akd_presensi_mhs')
                    ->where('id_kelas', $id_kelas)
                    ->where('pertemuan', $pertemuan)
                    ->update([
                        'hadir' => $nimhadir
                    ]);
            } else {
                DB::table('akd_presensi_mhs')->insert([
                    'id_kelas'  =>  $id_kelas,
                    'tgl'  =>  $tglJamnow,
                    'pertemuan' => $pertemuan,
                    'hadir' => $nimarray
                ]);
            }
            return response()->json(['success' => 'Anda berhasil presensi !']);
        } else {
            return response()->json(['error' => 'Anda sudah presensi hari ini !']);
        }
    }

    public function tampiljadwalmakul(Request $request)
    {

        $check_herregistrasi = DB::select("SELECT * 
        FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
        WHERE akd_heregistrasi.nim ='" . $request->nim . "' 
        AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
        AND akd_heregistrasi.semester='" . $request->semester . "'");

        $jml = count($check_herregistrasi);
        if ($jml > 0) {
            $id_her = $check_herregistrasi[0]->id_heregistrasi;
        } else {
            $id_her = 0;
        }

        $tampiljadwalmakul = DB::select("SELECT hari, CONCAT_WS(' s/d ', jam_mulai, jam_selesai) AS jam, kode_matakuliah, nama_matakuliah, akd_penawaran_matakuliah.sks_matakuliah AS sks, 
        IF(akd_penawaran_matakuliah.semester = '1', 'Ganjil', 'Genap' ) AS semester, nama_kelas, 
        CONCAT_WS(' ', gelar_depan, simpeg_pegawai.nama,gelar_belakang) AS dosen, kode_ruang FROM akd_krs 
        JOIN akd_detail_krs ON akd_krs.id_krs = akd_detail_krs.id_krs
        JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
        JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
        JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        JOIN simpeg_pegawai ON simpeg_pegawai.id = akd_penawaran_matakuliah.kode_dosen
        WHERE akd_krs.id_heregistrasi='" . $id_her . "'   
        ORDER BY akd_kelas_kuliah.hari DESC");

        return $tampiljadwalmakul;
    }
    public function tampilstatuspembayaran(Request $request)
    {
        $nim = $request->nim;
        $sttpem = DB::select("SELECT a.id_tagihan,a.kode_biling,a.nama_biaya,a.tahun,a.semester,a.biaya,(SELECT IF(SUM(bayar) IS NULL,0,SUM(bayar)) AS bayar FROM keu_bayar kb WHERE a.id_tagihan=kb.id_tagihan) AS jumbayar,a.status,(SELECT nim FROM keu_virtual_akun WHERE kode=a.kode_biling) AS kodeva FROM keu_tagihan a 
        JOIN akd_mahasiswa b ON a.nim=b.nim JOIN akd_program_studi c  ON c.kode_program_studi=b.kode_program_studi 
        WHERE a.nim='$nim' AND a.status='0' ORDER BY a.id_tagihan DESC");

        return $sttpem;
    }
    public function tampilstatuspembayaranriwayat(Request $request)
    {
        $nim = $request->nim;
        $sttpem = DB::select("SELECT b.tahun,b.semester,b.nama_biaya,a.bayar,a.id_bayar,a.created_at,(SELECT nim FROM keu_virtual_akun WHERE kode=b.kode_biling) AS kodeva FROM keu_bayar a JOIN keu_tagihan b ON a.id_tagihan=b.id_tagihan WHERE nim='$nim'");
        // $sttpem = DB::select("SELECT * FROM keu_bayar a JOIN keu_tagihan b ON a.id_tagihan=b.id_tagihan WHERE nim='$nim'");

        return $sttpem;
    }
    public function tampilstatusva(Request $request)
    {
        $nim = $request->nim;
        $sttpemva = DB::select("SELECT * FROM keu_va WHERE nim='$nim'");

        return $sttpemva;
    }


    public function transkripnilai(Request $request)
    {
        $transkripnilai = DB::select("SELECT akd_transkrip.*,akd_matakuliah.*,akd_predikat_nilai_huruf.*, (akd_matakuliah.sks_matakuliah*MAX(mutu)) AS kum_sksmutu,MIN(akd_transkrip.nilai) as nilai,MAX(akd_predikat_nilai_huruf.mutu) AS mutu 
        FROM akd_transkrip
        JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_transkrip.id_matakuliah 
        JOIN akd_predikat_nilai_huruf ON akd_transkrip.nilai = akd_predikat_nilai_huruf.nilai_huruf_akhir 
        WHERE akd_transkrip.nim ='" . $request->nim . "' 
          AND akd_transkrip.id_matakuliah IN (
              SELECT DISTINCT pm.id_matakuliah 
              FROM akd_detail_krs dk
              JOIN akd_krs k ON dk.id_krs=k.id_krs
              JOIN akd_heregistrasi h ON k.id_heregistrasi=h.id_heregistrasi
              JOIN akd_kelas_kuliah kk ON dk.id_kelas=kk.id_kelas
              JOIN akd_penawaran_matakuliah pm ON kk.id_tawar=pm.id_tawar
              WHERE h.nim='" . $request->nim . "'
          )
        GROUP BY akd_transkrip.id_matakuliah ORDER BY akd_matakuliah.smt_matakuliah");

        return $transkripnilai;
    }

    public function edit_password_mhs(Request $request)
    {
        $edit_password_mhs = DB::table('akd_mahasiswa')
            ->where('nim', $request->nim)
            ->update([
                'password_mhs'  =>  md5($request->epasswordbaru)
            ]);
        return $edit_password_mhs;
    }

    public function profil_personal(Request $request)
    {
        $profil_personal = collect(DB::select("SELECT *,akd_mahasiswa.kode_kabupaten AS kabupaten_mhs,akd_mahasiswa.alamat_asal AS alamat_asal_mhs,akd_mahasiswa.no_pendaftaran AS no_pendaftaran_mhs,akd_mahasiswa.kode_agama AS kode_agama_mhs, CONCAT_WS(', ',akd_mahasiswa.tempat_lahir,akd_mahasiswa.tanggal_lahir) AS ttl, IF(akd_mahasiswa.jenis_kelamin ='L', 'Laki-laki', 'Perempuan') AS jk, CONCAT(akd_mahasiswa.alamat_asal, ', ','RT/RW : ',rt,'/',rw,', ','Kode Pos : ', kode_pos) AS alamat_lengkap
        FROM akd_mahasiswa
                LEFT JOIN mst_kewarganegaraan ON akd_mahasiswa.kode_kewarganegaraan = mst_kewarganegaraan.kode_kewarganegaraan
                LEFT JOIN mst_agama ON akd_mahasiswa.kode_agama=mst_agama.kode_agama
                LEFT JOIN akd_fakultas ON akd_mahasiswa.kode_fakultas=akd_fakultas.kode_fakultas
                LEFT JOIN akd_program_pendidikan ON akd_mahasiswa.kode_program_pendidikan=akd_program_pendidikan.kode_program_pendidikan 
                LEFT JOIN akd_program_studi ON akd_mahasiswa.kode_program_studi=akd_program_studi.kode_program_studi
                LEFT JOIN adm_provinsi ON akd_mahasiswa.kode_provinsi=adm_provinsi.kode_provinsi
                LEFT JOIN adm_kabupaten ON akd_mahasiswa.kode_kabupaten=adm_kabupaten.kode_kabupaten
                WHERE akd_mahasiswa.nim='" . $request->nim . "'"))->first();

        return $profil_personal;
    }
    public function profil_ayah(Request $request)
    {
        $profil_ayah = collect(DB::select("SELECT akd_ortu_ayah.* FROM akd_ortu_ayah
        LEFT JOIN mst_pekerjaan ON akd_ortu_ayah.kode_pekerjaan=mst_pekerjaan.kode_pekerjaan
        LEFT JOIN mst_pendidikan ON akd_ortu_ayah.pendidikan_id=mst_pendidikan.pendidikan_id 
        LEFT JOIN mst_penghasilan ON akd_ortu_ayah.kode_penghasilan=mst_penghasilan.kode_penghasilan
        WHERE akd_ortu_ayah.nim='" . $request->nim . "'"))->first();

        return $profil_ayah;
    }
    public function profil_ibu(Request $request)
    {
        $profil_ibu = collect(DB::select("SELECT akd_ortu_ibu.* FROM akd_ortu_ibu
        LEFT JOIN mst_pekerjaan ON akd_ortu_ibu.kode_pekerjaan=mst_pekerjaan.kode_pekerjaan
        LEFT JOIN mst_pendidikan ON akd_ortu_ibu.pendidikan_id=mst_pendidikan.pendidikan_id 
        LEFT JOIN mst_penghasilan ON akd_ortu_ibu.kode_penghasilan=mst_penghasilan.kode_penghasilan
        WHERE akd_ortu_ibu.nim='" . $request->nim . "'"))->first();

        return $profil_ibu;
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
            ]);
        return $edit_camaba;
    }
    public function simpan_user_profil(Request $request)
    {
        $simpanmahasiswa = DB::table('akd_mahasiswa')
            ->where('nim', $request->nim)
            ->update([
                'nik_mhs'  =>  $request->nik_mhs,
                // 'nama_mahasiswa'  =>  $request->nama_lengkap,
                'tempat_lahir'  =>  $request->tempat_lahir,
                'tanggal_lahir'  =>  $request->tanggal_lahir,
                'jenis_kelamin'  =>  $request->jk,
                'kode_agama'  =>  $request->editagama,
                'alamat_asal'  =>  $request->alamat_lengkap,
                'kode_kewarganegaraan'  =>  $request->kwg,
                'kode_provinsi'  =>  $request->kode_provinsi,
                'kode_kabupaten'  =>  $request->kode_kabupaten,
                'kode_kecamatan'  =>  $request->kode_kecamatan,
                'kelurahan'  =>  $request->kelurahan,
                'rt'  =>  $request->rt,
                'rw'  =>  $request->rw,
                'email'  =>  $request->email,
                'telp'  =>  $request->telp,
                'nisn'  =>  $request->nisn,
                'kode_pos'  =>  $request->kode_pos,
                'kode_jenis_tinggal'  =>  $request->jenis_tinggal,
                'kode_transportasi'  =>  $request->transportasi,
                'kode_jenis_pendaftaran'  =>  $request->jenis_pendaftaran,
                'kode_jalur_pendaftaran'  =>  $request->jalur_pendaftaran
            ]);
        $simpancamaba = DB::table('adm_camaba')
        ->where('no_pendaftaran', $request->no_pendaftaran)
        ->update([
            'alamat_asal'  =>  $request->alamat_lengkap,
            'rt'  =>  $request->rt,
            'rw'  =>  $request->rw,
            'kode_pos'  =>  $request->kode_pos,
            'nik'  =>  $request->nik_mhs,
            // 'nama_camaba'  =>  $request->nama_lengkap,
            'tempat_lahir'  =>  $request->tempat_lahir,
            'tanggal_lahir'  =>  $request->tanggal_lahir,
            'jenis_kelamin'  =>  $request->jk,
            'kode_agama'  =>  $request->editagama,
            'kode_kewarganegaraan'  =>  $request->kwg,
            'kode_provinsi'  =>  $request->kode_provinsi,
            'kode_kabupaten'  =>  $request->kode_kabupaten,
            'kode_kecamatan'  =>  $request->kode_kecamatan,
            'kelurahan'  =>  $request->kelurahan,
            'rt'  =>  $request->rt,
            'rw'  =>  $request->rw,
            'email'  =>  $request->email,
            'telp'  =>  $request->telp,
            'nisn'  =>  $request->nisn,
        ]);

        return $simpanmahasiswa;
    }
    public function simpan_pendidikan_mahasiswa(Request $request)
    {
        $simpanmahasiswa = DB::table('akd_mahasiswa')
            ->where('nim', $request->pendidikan_nim)
            ->update([
                'pendidikan_terakhir'  =>  $request->pendidikan_terakhir,
                'alamat_slta'  =>  $request->alamat_slta,
                'jurusan_slta'  =>  $request->jurusan_slta,
                'no_ijazah_slta'  =>  $request->no_ijazah,
                'tahun_ijazah_slta'  =>  $request->tahun_ijazah,
            ]);
            
        return $simpanmahasiswa;
    }
    
    public function simpan_pendidikan_camaba(Request $request)
    {
        $no_pendaftaran = collect(DB::connection('mysql')->select('SELECT no_pendaftaran FROM akd_mahasiswa WHERE nim = "'.$request->pendidikan_nim.'"'))->first()->no_pendaftaran;
        $simpancamaba = DB::table('adm_camaba')
            ->where('no_pendaftaran', $no_pendaftaran)
            ->update([
                'pendidikan_terakhir'  =>  $request->pendidikan_terakhir,
                'alamat_slta'  =>  $request->alamat_slta,
                'jurusan_slta'  =>  $request->jurusan_slta,
                'no_ijazah_slta'  =>  $request->no_ijazah,
                'tahun_ijazah_slta'  =>  $request->tahun_ijazah
            ]);
        return $simpancamaba;
    }    
    
    public function simpan_ayah_mahasiswa(Request $request)
    {
        $simpanmahasiswa = DB::table('akd_ortu_ayah')
        ->updateOrInsert(
            // Kondisi untuk mencocokkan data yang akan diperbarui atau disisipkan
            [
                'nim' => $request->nim,
            ],
            // Data yang akan di-update atau disisipkan
            [
                'nik_ayah' => $request->nik_ayah,
                'nama' => $request->nama_ayah,
                'tgl_lahir' => $request->tanggal_lahir,
                'pendidikan_id' => $request->pendidikan_ayah,
                'kode_penghasilan' => $request->penghasilan_ayah,
                'telepon_ayah' => $request->phone_ayah,
                'kode_pekerjaan' => $request->pekerjaan_ayah
            ]
        );
    
    return $simpanmahasiswa;
    }
    public function simpan_ibu_mahasiswa(Request $request)
    {
        $simpanmahasiswa = DB::table('akd_ortu_ibu')
            ->updateOrInsert(
                // Kondisi untuk mencocokkan data yang akan diperbarui atau disisipkan
                [
                    'nim' => $request->nim,
                ],
                // Data yang akan di-update atau disisipkan
                [
                'nik_ibu'  =>  $request->nik_ibu,
                'nama'  =>  $request->nama_ibu,
                'tgl_lahir'  =>  $request->tanggal_lahir,
                'pendidikan_id'  =>  $request->pendidikan_ibu,
                'kode_penghasilan'  =>  $request->penghasilan_ibu,
                'telepon_ibu'  =>  $request->phone_ibu,
                'kode_pekerjaan'  =>  $request->pekerjaan_ibu
            ]);
        return $simpanmahasiswa;
    }

    public function upload_foto(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nim_mhs' => 'required',
            'file_upload' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {

// $cekfoto = collect(DB::connection('mysql')->select('SELECT foto FROM akd_mahasiswa WHERE nim = "'.$request->nim_mhs.'"'))->first();

            // if ($cekfoto && $cekfoto->foto) {
            //     $path = public_path('images/foto_mahasiswa/' . $cekfoto->foto);
            //     if (File::exists($path)) {
            //         File::delete($path);
            //     }
            // }

            if ($request->hasfile('file_upload')) {
                $file   =   $request->file('file_upload');
                // Rename file menggunakan nim_mhs dan ekstensi dipaksa ke .jpg sesuai kebutuhan
                $name = $request->nim_mhs . '.jpg';
                $file->move(public_path('images/foto_mahasiswa/'), $name);
                DB::connection('mysql')->table('akd_mahasiswa')
                    ->where('nim', $request->nim_mhs)
                    ->update([
                        'foto'  =>  $name
                    ]);

                return response()->json([
                    'success' => 'Foto berhasil diupload !'
                ]);
            }
        }
    }
    public function tampilprovinsi()
    {
        $tampilprovinsi = DB::select("select * from adm_provinsi order by nama_provinsi asc");

        return $tampilprovinsi;
    }
    public function tampilkabupaten(Request $request)
    {
        $kd_provinsi = $request->kd_provinsi;
        $tampilkabupaten = DB::select("select * from adm_kabupaten WHERE kd_provinsi=?", [$kd_provinsi]);

        return $tampilkabupaten;
    }

    public function tampilkecamatan(Request $request)
    {
        $kd_kabupaten = $request->kd_kabupaten;
        $kd_provinsi = $request->kd_provinsi;
        $tampilkabupaten = DB::select("select * from adm_kecamatan WHERE kd_kabupaten=? AND kd_provinsi=?", [$kd_kabupaten, $kd_provinsi]);

        return $tampilkabupaten;
    }


    public function checkedom(Request $request)
    {
        // Retrieve the registration ID based on provided filters
        $check_herregistrasi = collect(DB::select("SELECT akd_heregistrasi.id_heregistrasi 
            FROM akd_heregistrasi JOIN akd_krs ON akd_heregistrasi.id_heregistrasi = akd_krs.id_heregistrasi 
            WHERE akd_heregistrasi.nim ='" . $request->nim . "' 
            AND akd_heregistrasi.tahun = '" . $request->tahun . "' 
            AND akd_heregistrasi.semester='" . $request->semester . "'"))->first();
        
        // Check if herregistrasi data is found
        if ($check_herregistrasi == null) {
            return response()->json([
                'status' => 'notCompletedFilled',
                'checkingdata' => 'Belum Her Registrasi'
            ], 200);
        }
        
        $getid_mhs = collect(DB::select("SELECT id_mhs, kode_penilaian FROM akd_mahasiswa WHERE nim ='" . $request->nim . "'"))->first();
        $getid_mreg = collect(DB::select("SELECT id_mreg FROM akd_mreg WHERE tahun = '" . $request->tahun . "' AND semester='" . $request->semester . "'"))->first();
        
        // Determine id_her value
        $id_her = $check_herregistrasi->id_heregistrasi;
    
        // Retrieve the data from akd_krs and related tables
        // $get_data = DB::connection('mysql')->select('SELECT akd_krs.id_krs, kode_matakuliah, nama_matakuliah, akd_matakuliah.sks_matakuliah AS sks, akd_penawaran_matakuliah.smt_matakuliah AS semester, nama_kelas, akd_detail_krs.id_kelas, 
        //     kode_ruang, jumlah_peserta, nilai_uts, nilai_huruf_akhir,
        //     ROUND(akd_predikat_nilai_huruf.mutu * akd_matakuliah.sks_matakuliah,2) AS total_nilai, ROUND(akd_matakuliah.sks_matakuliah*mutu,2) AS kum_sksmutu
        //     FROM akd_krs
        //     LEFT JOIN akd_detail_krs ON akd_detail_krs.id_krs = akd_krs.id_krs
        //     LEFT JOIN akd_predikat_nilai_huruf ON akd_predikat_nilai_huruf.nilai_huruf_akhir = akd_detail_krs.nilai_akhir_huruf
        //     LEFT JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
        //     LEFT JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
        //     LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        //     WHERE akd_krs.id_heregistrasi = "' . $id_her . '"');
        $kode_nilai = $getid_mhs->kode_penilaian;
        $get_data = DB::connection('mysql')->select('SELECT akd_krs.id_krs, kode_matakuliah, nama_matakuliah, akd_matakuliah.sks_matakuliah AS sks, akd_penawaran_matakuliah.smt_matakuliah AS semester, nama_kelas, akd_detail_krs.id_kelas, kode_ruang, jumlah_peserta, nilai_uts, nilai_uas, nilai_tugas, nilai_kuis, nilai_praktek, kehadiran, nilai_akhir_angka, nilai_akhir_huruf, nilai_huruf_akhir, ROUND(akd_predikat_nilai_huruf.mutu * akd_matakuliah.sks_matakuliah, 2) AS total_nilai, ROUND(akd_matakuliah.sks_matakuliah * mutu, 2) AS kum_sksmutu
            FROM akd_krs
            JOIN akd_detail_krs ON akd_detail_krs.id_krs = akd_krs.id_krs
            LEFT JOIN akd_predikat_nilai_huruf 
                   ON akd_predikat_nilai_huruf.nilai_huruf_akhir = akd_detail_krs.nilai_akhir_huruf
                   AND akd_predikat_nilai_huruf.kode_nilai = "' . $kode_nilai . '"
            JOIN akd_kelas_kuliah ON akd_detail_krs.id_kelas = akd_kelas_kuliah.id_kelas
            LEFT JOIN akd_penawaran_matakuliah ON akd_kelas_kuliah.id_tawar = akd_penawaran_matakuliah.id_tawar
            LEFT JOIN akd_matakuliah ON akd_matakuliah.id_matakuliah = akd_penawaran_matakuliah.id_matakuliah
        WHERE akd_krs.id_heregistrasi = "' . $id_her . '" ORDER BY id_kelas ASC');
    
        // Extract id_kelas values from $get_data
        $idKelasArray = array_map(function ($item) {
            return $item->id_kelas;
        }, $get_data);
    
        // Count distinct id_kelas values
        $distinctIdKelasCount = count(array_unique($idKelasArray));
    
        // Retrieve the count of distinct id_kelas from another query (if needed)
        $id_mhs = $getid_mhs->id_mhs; // Get user_id from the request
        $id_mreg = $getid_mreg->id_mreg; // Get id_mreg from the request
    
        $total_soal = DB::table('edom_soal')->where('id_mreg', $id_mreg)->count();

        if ($total_soal == 0) {
            return response()->json([
                'status' => 'completedFilled',
                'checkingdata' => 'bypass'
            ], 200);
        }

        $completedClasses = DB::table('edom_jawaban')
            ->where('user_id', $id_mhs)
            ->where('id_mreg', $id_mreg)
            ->select('id_kelas')
            ->groupBy('id_kelas')
            ->havingRaw('COUNT(DISTINCT id_soal) >= ?', [$total_soal])
            ->pluck('id_kelas')
            ->toArray();
        
        $count = count($completedClasses);
    
        // Use the distinct count in your logic
        if ($distinctIdKelasCount <= $count) {
            return response()->json([
                'status' => 'completedFilled',
                'checkingdata' => 'ok'
            ], 200);
        }
    
        return response()->json([
            'status' => 'notCompletedFilled',
            'checkingdata' => 'ok'
        ], 200);
    }    

    public function cekhereg(Request $request)
    {
        $nim = $request->nim;
        $tahun = $request->tahun;
        $semester = $request->semester;
        $cekhereg = collect(DB::select("select * from akd_heregistrasi WHERE nim='$nim' AND tahun='$tahun' AND semester='$semester'"))->first();

        return $cekhereg;
    }
    public function getBukti(Request $request)
    {
        $id = $request->id;

        $data = DB::table('keu_bayar as a')
            ->join('keu_tagihan as b', 'a.id_tagihan', '=', 'b.id_tagihan')
            ->join('akd_mahasiswa as m', 'b.nim', '=', 'm.nim')
            ->leftJoin('keu_virtual_akun as v', 'b.kode_biling', '=', 'v.kode') // untuk ambil jurusan dan fakultas
            ->select(
                'a.id_bayar',
                'a.bayar',
                'a.created_at as waktubayar',
                DB::raw('DATE(a.created_at) as tgl_bayar'), // ubah di sini
                'a.no_kwitansi',
                'a.valid_id',
                'a.keterangan',
                'a.payment_by',
                'a.user_by',
                'b.nama_biaya',
                'b.tahun',
                'b.semester',
                'b.kode_biling as no_transaksi',
                'm.nim',
                'm.nama_mahasiswa',
                'v.jurusan as prodi',
                'v.fakultas'
            )
            ->where('a.id_bayar', $id)
            ->first();

        return response()->json($data);
    } 
}

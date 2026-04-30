<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use App\Models\Mmahasiswa;

class Mahasiswa extends Controller
{
    //
    public function __construct()
    {
        $this->mahasiswa = new Mmahasiswa();
        // $session_mahasiswa = DB::table('session_mahasiswa')->select('username')->first();
    }

    public function cek_bisa_krs(Request $request)
    {
        $cek_bisa_krs = $this->mahasiswa->cek_bisa_krs($request);
        return $cek_bisa_krs;
    }
    public function cek_bisa_cetak_kartuujian(Request $request)
    {
        $cek_bisa_cetak_kartuujian = $this->mahasiswa->cek_bisa_cetak_kartuujian($request);
        return $cek_bisa_cetak_kartuujian;
    }

    public function cek_bisa_revisikrs(Request $request)
    {
        $cek_bisa_revisikrs = $this->mahasiswa->cek_bisa_revisikrs($request);
        return $cek_bisa_revisikrs;
    }

    public function filter_khs(Request $request)
    {
        $filter_khs = $this->mahasiswa->filter_khs($request);
        return $filter_khs;
    }

    public function select_khs(Request $request)
    {
        $select_khs = $this->mahasiswa->select_khs($request);
        return $select_khs;
    }

    public function check_session(Request $request)
    {

        // Session::put('username');
        // if (Session::has('username')) {
        //     $check_session = Session::get('username');
        // } else {
        //     $check_session = 'Tidak ada data dalam session.';
        // }

        $data_khs = DB::connection('mysql')->table('akd_heregistrasi')
            ->join('akd_krs', 'akd_heregistrasi.id_heregistrasi', '=', 'akd_krs.id_heregistrasi')
            ->join('akd_detail_krs', ' akd_detail_krs.id_krs', '=', 'akd_krs.id_krs')
            ->join('akd_kelas_kuliah', 'akd_detail_krs.id_kelas', '=', 'akd_kelas_kuliah.id_kelas')
            ->where('akd_heregistrasi.nim', '20310410030')
            ->where('akd_heregistrasi.semester', '1')
            ->where('akd_heregistrasi.tahun', '2020');


        $data = [];
        $get_data = $data_khs->get();

        foreach ($get_data as $row) {
            $id_heregistrasi = $row->id_heregistrasi;
            $id_tawar = $row->id_tawar;
            $tahun = $row->tahun;
            $semester = $row->semester;
            $id_kelas = $row->id_kelas;
            $nilai_akhir = $row->nilai_akhir_huruf;
            if ($semester == "1") {
                $semester_huruf = "Gasal";
            } else {
                $semester_huruf = "Genap";
            }

            //$data_predikat_nilai = "select * from akd_predikat_nilai_huruf where nilai_huruf_akhir='$nilai_akhir'";

            $data_predikat_nilai = DB::connection('mysql')->table('akd_predikat_nilai_huruf')
                ->where('nilai_huruf_akhir', $nilai_akhir);
            $hasil_data_predikat_nilai = $data_predikat_nilai->get();
            $cek_data_predikat_nilai = $data_predikat_nilai->count();
            if ($cek_data_predikat_nilai > 0) {
                $row = $hasil_data_predikat_nilai->fetch_object();
                $nilai_angka = $row->mutu;
            } else {
                $nilai_angka = 0;
            }
        }

        dd($nilai_angka);
        // $data = Session::get('username');
        // return $data;
    }

    public function datakhs(Request $request)
    {
        $datakhs = $this->mahasiswa->datakhs($request);
        return $datakhs;
    }

    public function ambilkrs(Request $request)
    {
        $ambilkrs = $this->mahasiswa->ambilkrs($request);
        return $ambilkrs;
    }

    public function simpan_krs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'id_kelas'  =>  'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_krs = $this->mahasiswa->simpan_krs($request);

            return $simpan_krs;
        }
    }

    public function revisikrs(Request $request)
    {
        $revisikrs = $this->mahasiswa->revisikrs($request);
        return $revisikrs;
    }
    public function tampilstatuspembayaran(Request $request)
    {
        $tampilstatuspembayaran = $this->mahasiswa->tampilstatuspembayaran($request);
        return $tampilstatuspembayaran;
    }
    public function tampilstatuspembayaranriwayat(Request $request)
    {
        $tampilstatuspembayaranriwayat = $this->mahasiswa->tampilstatuspembayaranriwayat($request);
        return $tampilstatuspembayaranriwayat;
    }
    public function tampilstatusva(Request $request)
    {
        $tampilstatusva = $this->mahasiswa->tampilstatusva($request);
        return $tampilstatusva;
    }

    public function hapus_revisikrs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_krs' => 'required',
            'id_kelas' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->mahasiswa->hapus_revisikrs($request);

            return response()->json(['success' => 'Data berhasil dihapus !']);
        }
    }

    public function tampiljadwalmakul(Request $request)
    {
        $tampiljadwalmakul = $this->mahasiswa->tampiljadwalmakul($request);
        return $tampiljadwalmakul;
    }
    public function dispensasikhs(Request $request)
    {
        $dispensasikhs = $this->mahasiswa->dispensasikhs($request);
        return $dispensasikhs;
    }
    public function presensimakul(Request $request)
    {
        $presensimakul = $this->mahasiswa->presensimakul($request);
        return $presensimakul;
    }

    public function simpan_presensi_mhs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'pertemuan' => 'required',
            'id_kelas'  =>  'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_presensi_mhs = $this->mahasiswa->simpan_presensi_mhs($request);

            return $simpan_presensi_mhs;
        }
    }

    public function transkripnilai(Request $request)
    {
        $transkripnilai = $this->mahasiswa->transkripnilai($request);
        return $transkripnilai;
    }

    public function edit_password_mhs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'epasswordbaru' => 'required',
            're_epasswordbaru'  => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->mahasiswa->edit_password_mhs($request);

            return response()->json(['success' => 'Password berhasil diubah !']);
        }
    }

    public function profil_personal(Request $request)
    {
        $profil_personal = $this->mahasiswa->profil_personal($request);
        return $profil_personal;
    }

    public function profil_ayah(Request $request)
    {
        $profil_ayah = $this->mahasiswa->profil_ayah($request);
        return $profil_ayah;
    }

    public function profil_ibu(Request $request)
    {
        $profil_ibu = $this->mahasiswa->profil_ibu($request);
        return $profil_ibu;
    }
    public function upload_foto(Request $request)
    {
        $upload_foto = $this->mahasiswa->upload_foto($request);
        return $upload_foto;
    }
}

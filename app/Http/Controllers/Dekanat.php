<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use App\Models\Mdekanat;

class Dekanat extends Controller
{
    //
    public function __construct()
    {
        $this->dekanat = new Mdekanat();
        // $session_mahasiswa = DB::table('session_mahasiswa')->select('username')->first();
    }

    public function data_acckrs(Request $request)
    {
        $data_acckrs = $this->dekanat->data_acckrs($request);
        return $data_acckrs;
    }

    public function ubahstatus_acckrs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_her' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $ubahstatus_acckrs = $this->dekanat->ubahstatus_acckrs($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function edit_password_dekanadmin(Request $request)
    {
        $this->dekanat->edit_password_dekanadmin($request);
        return response()->json(['success' => 'Password berhasil diubah !']);
    }
    public function data_makulpenawaran(Request $request)
    {
        $data_makulpenawaran = $this->dekanat->data_makulpenawaran($request);
        return $data_makulpenawaran;
    }

    public function data_makul_ba_ujian(Request $request)
    {
        $data_makul_ba_ujian = $this->dekanat->data_makul_ba_ujian($request);
        return $data_makul_ba_ujian;
    }

    public function simpan_nilai_uts(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_detail_krs' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {

            DB::beginTransaction();

            try {

                $this->akademik->simpan_nilai_uts($request);

                DB::commit();
                return response()->json(['success' => 'Data berhasil disubmit !']);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            }
        }
    }

    public function simpan_nilai_uas(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_detail_krs' => 'required',
            'nilai_akhir_huruf' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {

            // DB::beginTransaction();
            try {
                $this->akademik->simpan_nilai_uas($request);
                // DB::commit();
            } catch (\Exception $e) {
                // DB::rollback();
                return response()->json(['error' => $e->getMessage()]);
            }
            return response()->json(['success' => 'Data nilai berhasil disimpan !']);
        }
    }

    public function data_transkripnilai(Request $request)
    {
        $data_transkripnilai = $this->dekanat->data_transkripnilai($request);
        return $data_transkripnilai;
    }
    public function dosenwali(Request $request)
    {
        $data_dosenwali = $this->dekanat->dosenwali($request);
        return $data_dosenwali;
    }
    public function daftarmhs_pa(Request $request)
    {
        $daftarmhs_pa = $this->dekanat->daftarmhs_pa($request);
        return $daftarmhs_pa;
    }
    public function daftar_mahasiswa(Request $request)
    {
        $daftar_mahasiswa = $this->dekanat->daftar_mahasiswa($request);
        return $daftar_mahasiswa;
    }
    public function list_mhs_already(Request $request)
    {
        $list_mhs_already = $this->dekanat->list_mhs_already($request);
        return $list_mhs_already;
    }

    public function save_mhs_dosenwali(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'id_pegawai' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->dekanat->save_mhs_dosenwali($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function nonaktif_mhs_dosenwali(Request $request)
    {

        $validation = Validator::make($request->all(), [
            // 'send_value' => 'required',
            'id_pegawai' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->dekanat->nonaktif_mhs_dosenwali($request);

            return response()->json(['success' => 'Data berhasil dinonaktifkan !']);
        }
    }

    public function hapus_mhs_dosen_wali(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->dekanat->hapus_mhs_dosen_wali($request);

            return response()->json(['success' => 'Data berhasil dihapus !']);
        }
    }
}

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
    public function simpan_user_profil(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->mahasiswa->simpan_user_profil($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }
    public function simpan_pendidikan_mahasiswa(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'pendidikan_nim' => 'required'
        ]);
    
        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpanmahasiswa = $this->mahasiswa->simpan_pendidikan_mahasiswa($request);
            $simpancamaba = $this->mahasiswa->simpan_pendidikan_camaba($request);
            
            // Periksa hasil update dan buat response yang sesuai
            if ($simpanmahasiswa && $simpancamaba) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil ditambahkan !'
                ]);
            } 
            
            // Jika salah satu gagal, buat pesan error sesuai variabel yang gagal
            $errorMessages = [];
    
            if (!$simpanmahasiswa) {
                $errorMessages[] = 'Gagal menyimpan data mahasiswa.';
            }
    
            if (!$simpancamaba) {
                $errorMessages[] = 'Gagal menyimpan data calon mahasiswa baru (camaba).';
            }
    
            return response()->json([
                'success' => false,
                'message' => $errorMessages
            ], 400);
        }
    }
    public function simpan_ayah_mahasiswa(Request $request)
    {
        $validation = Validator::make($request->all(), [
            // 'id_kelas' => 'required',
            // 'tgl' => 'required',
            // 'pertemuan'  => 'required',
            // 'materi_makul'  => 'required',
            // 'peserta_hadir' => 'required',
            // 'jam_mulai' => 'required',
            // 'jam_selesai' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->mahasiswa->simpan_ayah_mahasiswa($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }
    public function simpan_ibu_mahasiswa(Request $request)
    {
        $validation = Validator::make($request->all(), [
            // 'id_kelas' => 'required',
            // 'tgl' => 'required',
            // 'pertemuan'  => 'required',
            // 'materi_makul'  => 'required',
            // 'peserta_hadir' => 'required',
            // 'jam_mulai' => 'required',
            // 'jam_selesai' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->mahasiswa->simpan_ibu_mahasiswa($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function tampilprovinsi()
    {
        $tampilprovinsi = $this->mahasiswa->tampilprovinsi();
        return $tampilprovinsi;
    }

    public function tampilkabupaten(Request $request)
    {
        $tampilkabupaten = $this->mahasiswa->tampilkabupaten($request);
        return $tampilkabupaten;
    }

    public function tampilkecamatan(Request $request)
    {
        $tampilkecamatan = $this->mahasiswa->tampilkecamatan($request);
        return $tampilkecamatan;
    }

    public function checkedom(Request $request)
    {
        $checkedom = $this->mahasiswa->checkedom($request);
        return $checkedom;
    }
    public function cekhereg(Request $request)
    {
        $cekhereg = $this->mahasiswa->cekhereg($request);
        return $cekhereg;
    }
    public function getBukti(Request $request)
    {
        $getBukti = $this->mahasiswa->getBukti($request);
        return $getBukti;
    }

    public function check_verifikasi_semester(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'tahun' => 'required',
            'semester' => 'required'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $tahun = $request->tahun;
        $semester = $request->semester;

        $check = DB::table('akd_mahasiswa_verifikasi_semester')
            ->where('nim', $nim)
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->first();

        // Check if mandatory profile fields are completed
        $mhs = DB::table('akd_mahasiswa')->where('nim', $nim)->first();
        $is_profile_complete = true;
        if ($mhs) {
            if (empty($mhs->nik_mhs) || empty($mhs->tempat_lahir) || empty($mhs->alamat_asal) || empty($mhs->pendidikan_terakhir)) {
                $is_profile_complete = false;
            }
        } else {
            $is_profile_complete = false;
        }

        // Check parents
        $ayah = DB::table('akd_ortu_ayah')->where('nim', $nim)->first();
        if (!$ayah || empty($ayah->nama) || empty($ayah->nik_ayah)) {
            $is_profile_complete = false;
        }

        $ibu = DB::table('akd_ortu_ibu')->where('nim', $nim)->first();
        if (!$ibu || empty($ibu->nama) || empty($ibu->nik_ibu)) {
            $is_profile_complete = false;
        }

        return response()->json([
            'status' => 'success',
            'is_verified' => $check ? (int)$check->is_verified : 0,
            'is_profile_complete' => $is_profile_complete ? 1 : 0
        ]);
    }

    public function submit_verifikasi_semester(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'tahun' => 'required',
            'semester' => 'required'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $tahun = $request->tahun;
        $semester = $request->semester;

        DB::table('akd_mahasiswa_verifikasi_semester')->updateOrInsert(
            ['nim' => $nim, 'tahun' => $tahun, 'semester' => $semester],
            ['is_verified' => 1, 'verified_at' => now(), 'updated_at' => now()]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Konfirmasi verifikasi data semester berjalan berhasil disimpan!'
        ]);
    }

    public function get_skpi_prestasi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $prestasi = DB::table('akd_skpi_prestasi')
            ->where('nim', $nim)
            ->orderBy('tanggal_perolehan', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $prestasi
        ]);
    }

    public function add_skpi_prestasi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'nama_kegiatan_id' => 'required|string|max:255',
            'nama_kegiatan_en' => 'required|string|max:255',
            'kategori' => 'required|string|max:50',
            'peran_id' => 'required|string|max:100',
            'peran_en' => 'required|string|max:100',
            'penyelenggara_id' => 'required|string|max:255',
            'penyelenggara_en' => 'required|string|max:255',
            'tanggal_perolehan' => 'required|date',
            'file_bukti' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120' // Max 5MB
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $nim = $request->nim;
        $path = '';
        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $filename = "SKPI_" . time() . "_" . $file->getClientOriginalName();
            $path = $file->storeAs("public/skpi_prestasi/{$nim}", $filename);
        }

        DB::table('akd_skpi_prestasi')->insert([
            'nim' => $nim,
            'nama_kegiatan_id' => $request->nama_kegiatan_id,
            'nama_kegiatan_en' => $request->nama_kegiatan_en,
            'kategori' => $request->kategori,
            'peran_id' => $request->peran_id,
            'peran_en' => $request->peran_en,
            'penyelenggara_id' => $request->penyelenggara_id,
            'penyelenggara_en' => $request->penyelenggara_en,
            'tanggal_perolehan' => $request->tanggal_perolehan,
            'path_bukti' => $path,
            'status_verifikasi' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data prestasi/sertifikasi SKPI berhasil ditambahkan!'
        ]);
    }

    public function delete_skpi_prestasi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id' => 'required',
            'nim' => 'required'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $id = $request->id;
        $nim = $request->nim;

        $check = DB::table('akd_skpi_prestasi')->where('id', $id)->first();
        if (!$check) {
            return response()->json(['error' => 'Data tidak ditemukan.'], 404);
        }

        if ($check->nim !== $nim) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke data ini.'], 403);
        }

        if ($check->status_verifikasi === 'disetujui') {
            return response()->json(['error' => 'Data yang sudah disetujui tidak dapat dihapus.'], 403);
        }

        if ($check->path_bukti && \Illuminate\Support\Facades\Storage::exists($check->path_bukti)) {
            \Illuminate\Support\Facades\Storage::delete($check->path_bukti);
        }

        DB::table('akd_skpi_prestasi')->where('id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data prestasi/sertifikasi SKPI berhasil dihapus!'
        ]);
    }

    public function translate(Request $request)
    {
        $v = Validator::make($request->all(), [
            'text' => 'required|string|max:5000'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $text = trim($request->text);
        if (empty($text)) {
            return response()->json(['status' => 'success', 'translated_text' => '']);
        }

        $hash = md5(strtolower($text));

        // 1. Cek cache lokal
        $cached = DB::table('sys_translation_cache')->where('md5_hash', $hash)->first();
        if ($cached) {
            return response()->json([
                'status' => 'success',
                'translated_text' => $cached->text_translated,
                'source' => 'cache'
            ]);
        }

        // 2. Hubungi MyMemory Translation API
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(5)->get('https://api.mymemory.translated.net/get', [
                'q' => $text,
                'langpair' => 'id|en'
            ]);
            
            if ($res->successful()) {
                $data = $res->json();
                if (isset($data['responseData']['translatedText'])) {
                    $translated = trim($data['responseData']['translatedText']);
                    
                    // Simpan ke cache
                    DB::table('sys_translation_cache')->insertOrIgnore([
                        'text_source' => $text,
                        'text_translated' => $translated,
                        'md5_hash' => $hash,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'translated_text' => $translated,
                        'source' => 'api'
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Fallback: kembalikan teks asli jika API luar error/timeout
        return response()->json([
            'status' => 'success',
            'translated_text' => $text,
            'source' => 'fallback'
        ]);
    }

    public function get_transkrip_ajuan(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $history = DB::table('akd_transkrip_ajuan')
            ->where('nim', $nim)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    public function submit_transkrip_ajuan(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;

        // Cek jika ada ajuan yang masih pending
        $checkPending = DB::table('akd_transkrip_ajuan')
            ->where('nim', $nim)
            ->where('status', 'pending')
            ->first();

        if ($checkPending) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda masih memiliki pengajuan transkrip yang sedang diproses.'
            ], 400);
        }

        DB::table('akd_transkrip_ajuan')->insert([
            'nim' => $nim,
            'tanggal_ajuan' => now()->toDateString(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan transkrip nilai berhasil dikirimkan.'
        ]);
    }
}


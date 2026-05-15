<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //untuk raw DB
use Illuminate\Support\Facades\Session; //untuk raw DB
use Illuminate\Support\Facades\Validator;
use App\Models\Makademik;
use App\Exports\TemplateUTSExport;
use App\Exports\TemplateUASExport;
use App\Exports\TemplateNewUASExport;
use App\Exports\ExportBeritaAcara;
use App\Exports\DHMDExport;
use App\Exports\TemplatePresensiExport;
use App\Imports\NilaiUTSImport;
use App\Imports\NilaiUASImport;
use App\Imports\PresensiImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Akademik extends Controller
{


    public function __construct()
    {
        $this->akademik = new Makademik();
    }
    public function presensi_permakul(Request $request)
    {
        $presensi_permakul = $this->akademik->presensi_permakul($request);
        return $presensi_permakul;
    }
    public function presensi_permakul1(Request $request)
    {
        $presensi_permakul1 = $this->akademik->presensi_permakul1($request);
        return $presensi_permakul1;
    }
    public function daftarmhs_pa(Request $request)
    {
        $daftarmhs_pa = $this->akademik->daftarmhs_pa($request);
        return $daftarmhs_pa;
    }
    public function cek_transkrip_krs(Request $request)
    {
        $cek_transkrip_krs = $this->akademik->cek_transkrip_krs($request);
        return $cek_transkrip_krs;
    }
    public function auto_pertemuan(Request $request)
    {
        $auto_pertemuan = $this->akademik->auto_pertemuan($request);
        return $auto_pertemuan;
    }
    public function auto_pertemuan_presensi(Request $request)
    {
        $auto_pertemuan_presensi = $this->akademik->auto_pertemuan_presensi($request);
        return $auto_pertemuan_presensi;
    }
    public function edit_password_dosen(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_peg' => 'required',
            'epasswordbaru' => 'required',
            're_epasswordbaru'  => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->akademik->edit_password_dosen($request);

            return response()->json(['success' => 'Password berhasil diubah !']);
        }
    }
    public function edit_password_dekanadmin(Request $request)
    {
        $this->akademik->edit_password_dekanadmin($request);
        return response()->json(['success' => 'Password berhasil diubah !']);
    }

    public function data_makul_ba(Request $request)
    {
        $data_makul_ba = $this->akademik->data_makul_ba($request);
        return $data_makul_ba;
    }

    public function data_makul_ba_ujian(Request $request)
    {
        $data_makul_ba_ujian = $this->akademik->data_makul_ba_ujian($request);
        return $data_makul_ba_ujian;
    }
    public function data_makul_ba_ujian_kaprodi(Request $request)
    {
        $data_makul_ba_ujian_kaprodi = $this->akademik->data_makul_ba_ujian_kaprodi($request);
        return $data_makul_ba_ujian_kaprodi;
    }

    public function rekap_ba(Request $request)
    {
        $rekap_ba = $this->akademik->rekap_ba($request);
        return $rekap_ba;
    }

    public function simpan_ba(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_kelas' => 'required',
            'tgl' => 'required',
            'pertemuan'  => 'required',
            'materi_makul'  => 'required',
            // 'peserta_hadir' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            return $this->akademik->simpan_ba($request);
        }
    }

    public function hapus_ba(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_ba($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function validated_ba(Request $request)
    {
        $request->validate([
            'id' => 'required' // Pastikan 'id' adalah angka
        ]);
    
        $result = $this->akademik->validated_ba($request);
        // var_dump($result); // Debug hasil return dari fungsi
    
        if ($result) {
            return response()->json(['berhasil' => 'Data berhasil divalidasi!']);
        } 
    
        return response()->json(['gagal' => 'Data gagal divalidasi!']);
    }

    public function list_ba(Request $request)
    {
        $list_ba = $this->akademik->list_ba($request);
        return $list_ba;
    }
    public function ubah_ba(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'eid' => 'required',
            'etgl' => 'required',
            'epertemuan'  => 'required',
            'emateri_makul'  => 'required',
            // 'epeserta_hadir'  => 'required',
            'ejam_mulai'  => 'required',
            'ejam_selesai'  => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            
            return $this->akademik->ubah_ba($request);
        }
    }
    public function simpan_ba_ujian(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_kelas'  =>  'required',
            'tgl_ujian'  =>  'required',
            'jenis_ujian'  =>  'required',
            'jam_mulai'  =>  'required',
            'jam_selesai'  =>  'required',
            'jml_mhs'  =>  'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            return $this->akademik->simpan_ba_ujian($request);
        }
    }
    public function ubah_ba_ujian(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'eid' => 'required',
            'tgl_ujian'  =>  'required',
            'jenis_ujian'  =>  'required',
            'jam_mulai'  =>  'required',
            'jam_selesai'  =>  'required',
            'jml_mhs'  =>  'required',
            'nim_tidak_hadir' =>  'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->akademik->ubah_ba_ujian($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function hapus_ba_ujian(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_ba_ujian($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function list_ba_ujian(Request $request)
    {
        $list_ba_ujian = $this->akademik->list_ba_ujian($request);
        return $list_ba_ujian;
    }

    public function data_lihat_absen_ujian(Request $request)
    {
        $data_lihat_absen_ujian = $this->akademik->data_lihat_absen_ujian($request);
        return $data_lihat_absen_ujian;
    }
    public function list_mhs_help_ba_ujian(Request $request)
    {
        $list_mhs_help_ba_ujian = $this->akademik->list_mhs_help_ba_ujian($request);
        return $list_mhs_help_ba_ujian;
    }

    public function select_nim_tidak_hadir(Request $request)
    {
        $select_nim_tidak_hadir = $this->akademik->select_nim_tidak_hadir($request);
        return $select_nim_tidak_hadir;
    }
    public function modal_sks_ambil(Request $request)
    {
        $modal_sks_ambil = $this->akademik->modal_sks_ambil($request);
        return $modal_sks_ambil;
    }


    public function cetakkrs(Request $request)
    {
        $cetakkrs = $this->akademik->cetakkrs($request);
        return $cetakkrs;
    }

    public function cetakkhs(Request $request)
    {
        $cetakkhs = $this->akademik->cetakkhs($request);
        return $cetakkhs;
    }

    public function getmhs_cetak(Request $request)
    {
        $getmhs_cetak = $this->akademik->getmhs_cetak($request);
        return $getmhs_cetak;
    }

    public function getkelasmk_cetak(Request $request)
    {
        $getkelasmk_cetak = $this->akademik->getkelasmk_cetak($request);
        return $getkelasmk_cetak;
    }
    public function getkelasdanbaujian_cetak(Request $request)
    {
        $getkelasdanbaujian_cetak = $this->akademik->getkelasdanbaujian_cetak($request);
        return $getkelasdanbaujian_cetak;
    }

    public function riwayat_mengajar(Request $request)
    {

        $riwayat_mengajar = $this->akademik->riwayat_mengajar($request);
        return $riwayat_mengajar;
    }

    public function presensi_mhs(Request $request)
    {

        $list_mhs_presensi = $this->akademik->list_mhs_presensi($request);
        return $list_mhs_presensi;
    }

    public function list_mhs_inputnilai(Request $request)
    {

        $list_mhs_inputnilai = $this->akademik->list_mhs_inputnilai($request);
        return $list_mhs_inputnilai;
    }
    
    public function persen_nilai_mk (Request $request)
    {

        $persen_nilai_mk  = $this->akademik->persen_nilai_mk ($request);
        return $persen_nilai_mk ;
    }

    public function select_predikat_nilai_huruf(Request $request)
    {
        $select_predikat_nilai_huruf = $this->akademik->select_predikat_nilai_huruf($request);
        return $select_predikat_nilai_huruf;
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

	    public function sinkron_transkrip(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => 'error', 'message' => $validation->errors()->all()], 400);
        }

        $nim = $request->nim;

        try {
            DB::beginTransaction();

            // 1. Ambil data nilai dari KRS beserta bobotnya (mutu) berdasarkan sistem penilaian mahasiswa
            $allGrades = DB::table('akd_detail_krs as dk')
                ->join('akd_kelas_kuliah as c', 'dk.id_kelas', '=', 'c.id_kelas')
                ->join('akd_penawaran_matakuliah as p', 'c.id_tawar', '=', 'p.id_tawar')
                ->join('akd_matakuliah as m', 'p.id_matakuliah', '=', 'm.id_matakuliah')
                ->join('akd_krs as k', 'dk.id_krs', '=', 'k.id_krs')
                ->join('akd_heregistrasi as h', 'k.id_heregistrasi', '=', 'h.id_heregistrasi')
                ->join('akd_mahasiswa as mhs', 'h.nim', '=', 'mhs.nim')
                ->join('akd_predikat_nilai_huruf as pre', function ($join) {
                    $join->on('dk.nilai_akhir_huruf', '=', 'pre.nilai_huruf_akhir')
                        ->on('mhs.kode_penilaian', '=', 'pre.kode_nilai');
                })
                ->where('h.nim', $nim)
                ->whereNotNull('dk.nilai_akhir_huruf')
                ->where('dk.nilai_akhir_huruf', '!=', '')
                ->select(
                    'h.nim',
                    'p.id_matakuliah',
                    'dk.nilai_akhir_huruf as nilai',
                    'm.tahun_kurikulum',
                    'mhs.kode_penilaian',
                    DB::raw('CAST(pre.mutu AS DECIMAL(10,2)) as mutu')
                )
                ->orderBy('mutu', 'desc')
                ->get();

            // 2. Ambil nilai terbaik (mutu tertinggi) untuk setiap mata kuliah
            $bestGrades = $allGrades->unique('id_matakuliah');

            foreach ($bestGrades as $row) {
                // Check if already exists in transkrip
                $existing = DB::table('akd_transkrip')
                    ->where('nim', $row->nim)
                    ->where('id_matakuliah', $row->id_matakuliah)
                    ->first();

                if ($existing) {
                    // Ambil bobot (mutu) nilai yang sudah ada di transkrip untuk perbandingan
                    $existingMutu = DB::table('akd_predikat_nilai_huruf')
                        ->where('nilai_huruf_akhir', $existing->nilai)
                        ->where('kode_nilai', $row->kode_penilaian)
                        ->value(DB::raw('CAST(mutu AS DECIMAL(10,2))')) ?? 0;

                    // Update jika nilai dari KRS lebih baik (bobot mutu lebih tinggi)
                    if ($row->mutu > $existingMutu) {
                        DB::table('akd_transkrip')
                            ->where('id_transkrip', $existing->id_transkrip)
                            ->update([
                                'nilai' => $row->nilai,
                                'updated_at' => now()
                            ]);
                    }
                } else {
                    // Insert new
                    DB::table('akd_transkrip')->insert([
                        'nim' => $row->nim,
                        'id_matakuliah' => $row->id_matakuliah,
                        'tahun_kurikulum' => $row->tahun_kurikulum,
                        'nilai' => $row->nilai,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Sinkronisasi berhasil dilakukan dari KRS ke Transkrip'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function export_berita_acara(Request $request)
    {
        $id = $request->id_kelas;
        $mk = $request->mk;
        $dosen = $request->dosen;
        return (new DHMDExport($id))->download('Berita_Acara_' . $dosen . '_' . $mk . '.xlsx');
    }
    public function templatenilai_uts_export(Request $request)
    {
        $id = $request->id_kelas;
        return (new TemplateUTSExport($id))->download('Template_Input_Nilai_UTS.xlsx');
    }
    public function templatenilai_uas_export(Request $request)
    {
        $id = $request->id_kelas;
        // $filename = $request->filename;
        $filename = preg_replace('/[\/\\\\]/', '_', $request->filename); // ganti / dan \ jadi _
        return (new TemplateUASExport($id))->download($filename.'_Template_UAS.xlsx');
        // return Excel::download(new TemplateNewUASExport($id), 'Template_Input_Nilai_UAS.xlsx');
    }

    public function templatepresensiexport(Request $request)
    {
        $id = $request->id_kelas;
        return (new TemplatePresensiExport($id))->download('Template_Import_Presensi.xlsx');
    }
    public function download_bantuan(Request $request)
    {
        $file = "public/panduan/Release_Panduan_Module_Dosen_v1.pdf";
        $name = "Release_Panduan_Module_Dosen_v1.pdf";
        return Storage::download($file, $name);

        $file_path = storage_path('public/panduan/Release_Panduan_Module_Dosen_v1.pdf');
        $headers = array('Content-Type' => 'application/pdf');
        return Response::download($file_path, 'file.pdf', $headers);
    }

    public function download_bantuan_mhs(Request $request)
    {
        $file = "public/panduan/Release_Panduan_Module_Mahasiswa_v1.pdf";
        $name = "Release_Panduan_Module_Mahasiswa_v1.pdf";
        return Storage::download($file, $name);

        $file_path = storage_path('public/panduan/Release_Panduan_Module_Mahasiswa_v1.pdf');
        $headers = array('Content-Type' => 'application/pdf');
        return Response::download($file_path, 'file.pdf', $headers);
    }

    public function import_nilai_uts(Request $request)
    {

        $this->validate($request, [
            'fileimport' => 'required|mimes:xls,xlsx'
        ]);


        if ($request->hasFile('fileimport')) {
            $file = $request->file('fileimport');
            Excel::import(new NilaiUTSImport(), $file);
            return response()->json(['success' => 'Data berhasil di simpan !']);
        }
        return response()->json(['error' => 'Please choose file before']);
        // return redirect()->back()->with(['errors' => 'Please choose file before']);
    }

    public function import_nilai_uas(Request $request)
    {
        $this->validate($request, [
            'fileimport' => 'required|mimes:xls,xlsx'
        ]);
    
        if ($request->hasFile('fileimport')) {
            $file = $request->file('fileimport');
    
            // Menggunakan PhpSpreadsheet untuk load file yang di-upload
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
    
            // Mengambil data nilai dari worksheet
            $dataFromWorksheet = $this->extractDataFromWorksheet($worksheet);
    
            // **Mengambil nilai persentase dari sel D8-G8**
            $persentase = [
                'kehadiran' => $worksheet->getCell('D8')->getValue() * 100,
                'uts'       => $worksheet->getCell('E8')->getValue() * 100,
                'uas'       => $worksheet->getCell('F8')->getValue() * 100,
                'tugas'     => $worksheet->getCell('G8')->getValue() * 100,
                'praktek'   => $worksheet->getCell('H8')->getValue() * 100,
                'kuis'      => $worksheet->getCell('I8')->getValue() * 100,
            ];
    
            // Kirim data nilai dan persentase ke import class
            $import = new NilaiUASImport($dataFromWorksheet, $persentase);
            Excel::import($import, $file);
    
            $errors = $import->getErrors();
    
            if (!empty($errors)) {
                return response()->json([
                    'status' => 'error',
                    'messages' => $errors,
                ], 422);
            }
    
            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan!',
            ]);
        }
    
        return response()->json([
            'status' => 'error',
            'message' => 'Please choose a file before uploading.',
        ], 400);
    }
    
    private function extractDataFromWorksheet($worksheet)
    {
        // Extract data yang diperlukan dari worksheet
        $data = [];
        $highestRow = $worksheet->getHighestRow(); // Mendapatkan jumlah baris data
        for ($row = 9; $row <= $highestRow; $row++) { // Menyesuaikan startRow
            $nilaiAkhirAngka = (float)$worksheet->getCellByColumnAndRow(10, $row)->getCalculatedValue();
            $nilaiAkhirHuruf = (string)$worksheet->getCellByColumnAndRow(11, $row)->getCalculatedValue();
            // Tambahkan data yang diperlukan
            $data[] = [
                'nilaiAkhirAngka' => $nilaiAkhirAngka,
                'nilaiAkhirHuruf' => $nilaiAkhirHuruf,
            ];
        }
        return $data;
    }

    public function import_presensi(Request $request)
    {

        $this->validate($request, [
            'fileimport_presensi' => 'required|mimes:xls,xlsx',
            'kelas_id' => 'required',
            'tgl' => 'required'
        ]);
        $kelas_id = $request->kelas_id;
        $tgl = $request->tgl;
        $pertemuan = $request->pertemuan_presensi;
        // var_dump($pertemuan);
        if ($request->hasFile('fileimport_presensi')) {
            $file = $request->file('fileimport_presensi');
            Excel::import(new PresensiImport($kelas_id, $tgl, $pertemuan), $file);
            return response()->json(['success' => 'Data berhasil di import !']);
        }
        return response()->json(['error' => 'Please choose file before']);
        // return redirect()->back()->with(['errors' => 'Please choose file before']);
    }

    public function simpan_presensi_mhs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_kls_presensi' => 'required',
            'nim' => 'required',
            'status' => 'required',
            'berita_acara' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            return $this->akademik->simpan_presensi_mhs($request);
        }
    }
    public function edit_presensi_mhs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_presensi' => 'required',
            'nim' => 'required',
            'status' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            return $this->akademik->edit_presensi_mhs($request);
        }
    }

    public function hapus_presensi(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_presensi($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function data_hitung_presensi(Request $request)
    {
        $data_hitung_presensi = $this->akademik->data_hitung_presensi($request);
        return $data_hitung_presensi;
    }

    public function data_lihat_mhs_presensi(Request $request)
    {

        $data_lihat_mhs_presensi = $this->akademik->data_lihat_mhs_presensi($request);
        return $data_lihat_mhs_presensi;
    }

    public function home_kalenderakademik(Request $request)
    {
        $home_kalenderakademik = $this->akademik->home_kalenderakademik($request);
        return $home_kalenderakademik;
    }
    public function home_kalenderakademikbase(Request $request)
    {
        $home_kalenderakademik = $this->akademik->home_kalenderakademikbase($request);
        return $home_kalenderakademik;
    }

    public function change_session_tahunakademik(Request $request)
    {

        $home_kalenderakademik = DB::select("SELECT * FROM
        (
        SELECT akd_mreg.*, IF(semester='1', CONCAT_WS(' ', tahun_akademik, 'Ganjil'), CONCAT_WS(' ', tahun_akademik, 'Genap')) AS tahun_ajaran
        FROM akd_mreg ORDER BY tahun DESC
        ) ta where id_mreg = '" . $request->tahunakademik . "'");

        // $tahun = $home_kalenderakademik[0]->tahun;
        // $semester = $home_kalenderakademik[0]->semester;
        // $nama_tahunakademik = $home_kalenderakademik[0]->tahun_ajaran;

        // $change_session_tahunakademik = DB::select("SELECT nama_kegiatan,tahun, semester, DATE_FORMAT(tanggal_mulai,'%d-%m-%Y') AS tanggal_mulai, DATE_FORMAT(tanggal_akhir,'%d-%m-%Y') AS tanggal_akhir, background from akd_kalender_akademik where tahun='$tahun' and semester = '$semester'");

        // if (Session::has('session_tahun') or Session::has('session_semester') or Session::has('session_nama_tahunakademik')) {
        //     Session::forget('session_tahun');
        //     Session::forget('session_semester');
        //     Session::forget('session_nama_tahunakademik');
        // };
        // //buat session
        // Session::put('session_tahun', $change_session_tahunakademik[0]->tahun);
        // Session::put('session_semester', $change_session_tahunakademik[0]->semester);
        // Session::put('session_nama_tahunakademik', $nama_tahunakademik);
        // //ambil session
        // Session::get('session_tahun');
        // Session::get('session_semester');
        // Session::get('session_nama_tahunakademik');

        return response()->json(['success' => 'Session berhasil diubah !', 'smtta' => $home_kalenderakademik]);
    }

    public function tahunajaran()
    {
        $datatahunajaran = $this->akademik->tahunajaran();
        return $datatahunajaran;
    }

    public function simpan_tahunajaran(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'tahun' => 'required',
            'semester' => 'required',
            'tahun_akademik' => 'required',
            'trash' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpantahunajaran = $this->akademik->simpan_tahunajaran($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_tahunajaran(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mreg' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edittahunajaran = $this->akademik->edit_tahunajaran($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function hapus_tahunajaran(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mreg' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_tahunajaran($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function ubahstatus_tahunajaran(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mreg' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edittahunajaran = $this->akademik->ubahstatus_tahunajaran($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    // Input Nilai Mahasiswa

    public function nilaimahasiswa(Request $request)
    {
        $datanilaimahasiswa = $this->akademik->nilaimahasiswa($request);
        return $datanilaimahasiswa;
    }

    public function tampil_tahunajaran()
    {
        $tahunajaran = $this->akademik->tampiltahunangkatan();
        return $tahunajaran;
    }
    public function tampil_tahunajaranmaba()
    {
        $tahunajaran = $this->akademik->tampiltahunangkatanmaba();
        return $tahunajaran;
    }

    public function tampiltahunakademik()
    {
        $tahunakademik = $this->akademik->tampiltahunakademik();
        return $tahunakademik;
    }
    public function simpan_nilaimahasiswa(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'makul' => 'required',
            'makul_prasyarat' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpannilaimahasiswa = $this->akademik->simpan_nilaimahasiswa($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    // Fakultas Di Mata Kuliah
    public function dropdown_prodifakultas()
    {
        $dropdown_prodifakultas = $this->akademik->dropdown_prodifakultas();
        return $dropdown_prodifakultas;
    }
    // makul prasyarat
    public function dropdown_prodi()
    {
        $dropdown_prodi = $this->akademik->dropdown_prodi();
        return $dropdown_prodi;
    }

    public function data_makulprasyarat()
    {
        $makulprasyarat = $this->akademik->data_makulprasyarat();
        return $makulprasyarat;
    }

    public function simpan_makulprasyarat(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'makul' => 'required',
            'makul_prasyarat' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_makulprasyarat = $this->akademik->simpan_makulprasyarat($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_makulprasyarat(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_prasyarat' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edittahunajaran = $this->akademik->edit_makulprasyarat($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function hapus_makulprasyarat(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_makulprasyarat($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }
    public function select_makul(Request $request)
    {
        $select2_makul = $this->akademik->select_makul($request);
        return $select2_makul;
    }

    public function select_tahunakademik(Request $request)
    {
        $select2_tahunakademik = $this->akademik->select_tahunakademik($request);
        return $select2_tahunakademik;
    }

    public function data_makulpenawaran(Request $request)
    {
        $makulpenawaran = $this->akademik->data_makulpenawaran($request);
        return $makulpenawaran;
    }

    public function simpan_makulpenawaran(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'makul' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_makulpenawaran = $this->akademik->simpan_makulpenawaran($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function update_url_rps(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_tawar' => 'required',
            'url_rps' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $editmakulpenawaran = $this->akademik->update_url_rps($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function edit_jadwalujian(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_tawar' => 'required',
            'id_kelas' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $editmakulpenawaran = $this->akademik->edit_jadwalujian($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function edit_makulpenawaran(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_tawar' => 'required',
            'id_kelas' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $editmakulpenawaran = $this->akademik->edit_makulpenawaran($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function edit_makulpenawaran_dkn(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_tawar' => 'required',
            'id_kelas' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->akademik->edit_makulpenawaran_dkn($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function hapus_makulpenawaran(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_makulpenawaran($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }


    public function data_inputnilaikhs(Request $request)
    {
        $inputnilaikhs = $this->akademik->data_inputnilaikhs($request);
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

        $validation = Validator::make($request->all(), [
            'nama_kegiatan' => 'required',
            'trash' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpankegiatanakademik = $this->akademik->simpan_kegiatanakademik($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_kegiatanakademik(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'kode_kegiatan' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $editkegiatanakademik = $this->akademik->edit_kegiatanakademik($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function hapus_kegiatanakademik(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'kode_kegiatan' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_kegiatanakademik($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function ubahstatus_kegiatanakademik(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'kode_kegiatan' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $editkegiatanakademik = $this->akademik->ubahstatus_kegiatanakademik($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
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
        $edittampilpimpinan = $this->akademik->edittampilpimpinan();
        return $edittampilpimpinan;
        // $edittampilpimpinan = DB::select("SELECT * FROM simpeg_pegawai WHERE kode_jenis='1' order by nama asc");
        // return $edittampilpimpinan;
    }
    public function edittampiljeniskelamin()
    {
        $edittampiljeniskelamin = $this->akademik->edittampiljeniskelamin();
        return $edittampiljeniskelamin;
    }
    public function tampiljenjang()
    {
        $tampiljenjang = DB::select("SELECT * FROM akd_jenjang_pendidikan order by kode_jenjang_pendidikan asc");
        return $tampiljenjang;
    }
    public function simpan_fakultas(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'kode_fakultas' => 'required',
            'nama_fakultas' => 'required',
            'pimpinan' => 'required',
            'kode_jenjang_pendidikan' => 'required',
            'plt' => 'required',
            'trash' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_fakultas = $this->akademik->simpan_fakultas($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_fakultas(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_fak' => 'required',
            'ekode_fakultas' => 'required',
            'enama_fakultas' => 'required',
            'editpimpinan' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $editfakultas = $this->akademik->edit_fakultas($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function hapus_fakultas(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_fak' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_fakultas($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function ubahstatus_fakultas(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_fak' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $ubahstatusfakultas = $this->akademik->ubahstatus_fakultas($request);

            if ($ubahstatusfakultas) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
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

        $validation = Validator::make($request->all(), [
            'kode_program_studi' => 'required',
            'kode_prodi_forlab' => 'required',
            'kode_fakultas' => 'required',
            'pimpinan_prodi' => 'required',
            'nama_program_studi' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_programstudi = $this->akademik->simpan_programstudi($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_programstudi(Request $request)
    {
        // Pre-process request to convert empty strings to null
        // Keep ta_ada_sempro as 0/1 (numeric) for database compatibility
        $etaSempro = $request->eta_ada_sempro;
        if ($etaSempro === 'Ya' || $etaSempro === 'ya') {
            $etaSempro = 1;
        } elseif ($etaSempro === 'Tidak' || $etaSempro === 'tidak') {
            $etaSempro = 0;
        }
        
        // Handle backward compatibility for field name (old: eta_min_bimbingan, new: eta_minimal_bimbingan)
        $minBimbingan = $request->eta_minimal_bimbingan;
        if ($minBimbingan === null || $minBimbingan === '') {
            $minBimbingan = $request->eta_min_bimbingan;
        }
        
        $request->merge([
            'eta_sks_minimal' => $request->eta_sks_minimal === '' ? null : $request->eta_sks_minimal,
            'eta_ada_sempro' => $etaSempro,
            'eta_komponen_bayar' => $request->eta_komponen_bayar === '' ? null : $request->eta_komponen_bayar,
            'eta_minimal_bimbingan' => $minBimbingan === '' ? null : $minBimbingan,
        ]);
        
        $validation = Validator::make($request->all(), [
            'id_program_studi' => 'required',
            'ekode_program_studi' => 'required',
            'ekode_prodi_forlab' => 'required',
            'ekode_fakultas' => 'required',
            'epimpinan_prodi' => 'required',
            'enama_program_studi' => 'required',
            'eta_sks_minimal' => 'nullable|integer|min:0',
            'eta_ada_sempro' => 'nullable|integer|in:0,1',
            'eta_komponen_bayar' => 'nullable|string',
            'eta_minimal_bimbingan' => 'nullable|integer|min:0'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'error' => $validation->errors()->all(),
                'message' => 'Validasi gagal',
                'data' => $request->only(['eta_sks_minimal', 'eta_ada_sempro', 'eta_minimal_bimbingan'])
            ], 422);
        }

        try {
            $isExists = DB::table('akd_program_studi')
                ->where('id_program_studi', $request->id_program_studi)
                ->exists();

            if (!$isExists) {
                return response()->json(['error' => 'Data program studi tidak ditemukan'], 404);
            }

            $edit_programstudi = $this->akademik->edit_programstudi($request);

            if ($edit_programstudi) {
                return response()->json(['success' => 'Data berhasil diubah !']);
            } else {
                return response()->json([
                    'success' => 'Tidak ada perubahan data',
                    'info' => 'Data yang dikirim sama dengan data saat ini'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function hapus_programstudi(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_program_studi' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_programstudi($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
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
    // public function tampilprogramstudi()
    // {
    //     $tampilprogramstudi = DB::select("SELECT * FROM akd_program_studi order by kode_program_studi asc");
    //     return $tampilprogramstudi;
    // }
    public function tampilprogramstudi()
    {
        $tampilprogramstudi = $this->akademik->tampilprogramstudi();
        return $tampilprogramstudi;
    }
    public function tampilprodi_perfak(Request $request)
    {
        $tampilprodi_perfak = $this->akademik->tampilprodi_perfak($request);
        return $tampilprodi_perfak;
    }
    public function select_kurikulum(Request $request)
    {
        $select2_kurikulum = $this->akademik->select_kurikulum($request);
        return $select2_kurikulum;
    }
    public function select_sifatmatakuliah()
    {
        $select2_sifatmatakuliah = $this->akademik->select_sifatmatakuliah();
        return $select2_sifatmatakuliah;
    }

    public function simpan_kurikulum(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'tahun_kurikulum' => 'required',
            'kode_prodi' => 'required',
            'date' => 'required',
            'trash' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpankurikulum = $this->akademik->simpan_kurikulum($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_kurikulum(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_kurikulum' => 'required',
            'etahun_kurikulum' => 'required',
            'ekode_prodi' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_kurikulum = $this->akademik->edit_kurikulum($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    public function hapus_kurikulum(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_kurikulum' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_kurikulum($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
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
        $kalenderakademik = $this->akademik->kalenderakademik();
        return $kalenderakademik;
        // $kurikulum = DB::select("SELECT * FROM akd_kalender_akademik a JOIN akd_kegiatan b ON a.kode_kegiatan_akademik=b.kode_kegiatan ORDER BY tahun desc,semester desc");
        // return $kurikulum;
    }
    public function tampilkegiatan()
    {
        $tampilkegiatan = DB::select("SELECT * FROM akd_kegiatan order by kode_kegiatan asc");
        return $tampilkegiatan;
    }

    public function simpan_kalenderakademik(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'kode_kegiatan_akademik' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_kalenderakademik = $this->akademik->simpan_kalenderakademik($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_kalenderakademik(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_kalender' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_kalenderakademik = $this->akademik->edit_kalenderakademik($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function hapus_kalenderakademik(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_kalenderakademik($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
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
    public function matakuliah()
    {
        $datamatakuliah = $this->akademik->matakuliah();
        return $datamatakuliah;
    }

    public function simpan_matakuliah(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'tahun_kurikulum' => 'required',
            'nama_matakuliah' => 'required',
            'nama_matakuliah_inggris' => 'required',
            'sks_matakuliah' => 'required',
            'smt_matakuliah' => 'required',
            'kode_sifat_matakuliah' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpanmatakuliah = $this->akademik->simpan_matakuliah($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function edit_matakuliah(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'ekurikulum' => 'required',
            'ekode_matakuliah' => 'required',
            'enama_matakuliah' => 'required',
            'enama_matakuliah_inggris' => 'required',
            'esks_matakuliah' => 'required',
            'ekode_bayar' => 'required',
            'esifatmatakuliah' => 'required',
            'esemester' => 'required',
            'ekode_fakultas' => 'required',
            'ekurikulum' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_matakuliah = $this->akademik->edit_matakuliah($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    public function hapus_matakuliah(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_matakuliah' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_matakuliah($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }
    // Dosen
    public function dosen(Request $request)
    {
        $data_dosen = $this->akademik->dosen($request);
        return $data_dosen;
    }
    public function select_dosen(Request $request)
    {
        $select2_dosen = $this->akademik->select_dosen($request);
        return $select2_dosen;
    }

    // Mahasiswa
    public function mahasiswa(Request $request)
    {
        $datamahasiswa = $this->akademik->mahasiswa($request);
        return $datamahasiswa;
    }
    public function edit_mahasiswa(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mhs11' => 'required',
            'no_pendaftaran11' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_mahasiswa = $this->akademik->edit_mahasiswa($request);

            return response()->json(['success' => 'Data Mahasiswa berhasil diubah !']);
        }
    }
    // Password Mahasiswa
    public function passwordmahasiswa(Request $request)
    {
        $datapasswordmahasiswa = $this->akademik->passwordmahasiswa($request);
        return $datapasswordmahasiswa;
    }

    public function edit_passwordmahasiswamhs(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mhs' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_passwordmahasiswamhs = $this->akademik->edit_passwordmahasiswamhs($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }

    public function edit_passwordmahasiswaortu(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mhs1' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_passwordmahasiswaortu = $this->akademik->edit_passwordmahasiswaortu($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    // Registrasi
    public function registrasi(Request $request)
    {
        $dataregistrasi = $this->akademik->registrasi($request);
        return $dataregistrasi;
    }

    public function edit_registrasi(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'eno_pendaftaran' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_registrasi = $this->akademik->edit_registrasi($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    // Her Registrasi
    public function herregistrasi(Request $request)
    {
        $dataherregistrasi = $this->akademik->herregistrasi($request);
        return $dataherregistrasi;
    }

    public function edit_herregistrasi(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'eid_heregistrasi' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_herregistrasi = $this->akademik->edit_herregistrasi($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    public function simpan_herregistrasi(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'batas_sks' => 'required',
            'nama_jenis_her' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_herregistrasi = $this->akademik->simpan_herregistrasi($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }
    // User
    public function user()
    {
        $user = DB::select("SELECT a.nm_module AS nama_m,a.username,a.nama,b.nm_module AS jabatan,c.* FROM USER a LEFT JOIN group_user b ON a.kode_group=b.id_group LEFT JOIN akd_fakultas c ON a.kode_fakultas=c.kode_fakultas ORDER BY a.id_user ASC");
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

        $daftarhadirkuliah = $this->akademik->daftarhadirkuliah($request);
        return $daftarhadirkuliah;
    }
    // Daftar Hadir Ujian
    public function daftarhadirujian(Request $request)
    {
        $datadaftarhadirujian = $this->akademik->daftarhadirujian($request);
        return $datadaftarhadirujian;
    }
    // Kartu Ujian
    public function kartuujian(Request $request)
    {
        $datakartuujian = $this->akademik->kartuujian($request);
        return $datakartuujian;
    }
    public function dropdown_angkatan()
    {
        $dropdown_angkatan = $this->akademik->dropdown_angkatan();
        return $dropdown_angkatan;
    }
    // Daftar Hadir Ujian
    public function hasilstudi(Request $request)
    {
        $datahasilstudi = $this->akademik->hasilstudi($request);
        return $datahasilstudi;
    }
    // Dosen Wali
    public function dosenwali()
    {
        $data_dosenwali = $this->akademik->dosenwali();
        return $data_dosenwali;
    }
    // Laporan Her Registrasi

    public function lapherregistrasi(Request $request)
    {
        $datalapherregistrasi = $this->akademik->lapherregistrasi($request);
        return $datalapherregistrasi;
    }
    public function batassksher(Request $request)
    {
        $databatassksher = $this->akademik->batassksher($request);
        return $databatassksher;
    }
    public function cetaktranskipnilaikurikulum(Request $request)
    {
        $datacetaktranskipnilaikurikulum = $this->akademik->cetaktranskipnilaikurikulum($request);
        return $datacetaktranskipnilaikurikulum;
    }
    public function cekkalenderbatasinputnilai(Request $request)
    {
        $datacekkalenderbatasinputnilai = $this->akademik->cekkalenderbatasinputnilai($request);
        return $datacekkalenderbatasinputnilai;
    }
    public function dropdown_akademik()
    {
        $dropdown_akademik = $this->akademik->dropdown_akademik();
        return $dropdown_akademik;
    }
    public function kewarganegaraan()
    {
        $kewarganegaraan = $this->akademik->kewarganegaraan();
        return $kewarganegaraan;
    }
    public function dispensasi(Request $request)
    {
        $datadispensasi = $this->akademik->dispensasi($request);
        return $datadispensasi;
    }
    public function lap_ipk_Mahasiswa_detail(Request $request)
    {
        $datalap_ipk_Mahasiswa_detail = $this->akademik->lap_ipk_Mahasiswa_detail($request);
        return $datalap_ipk_Mahasiswa_detail;
    }
    public function forminputcamaba(Request $request)
    {
        $dataforminputcamaba = $this->akademik->forminputcamaba($request);
        return $dataforminputcamaba;
    }
    // Transkip Nilai
    public function transkipnilai(Request $request)
    {
        $datatranskipnilai = $this->akademik->transkipnilai($request);
        return $datatranskipnilai;
    }
    // Transkip Akademik
    public function transkipakademik(Request $request)
    {
        $datatranskipakademik = $this->akademik->transkipakademik($request);
        return $datatranskipakademik;
    }
    // Maba
    public function daftarmaba(Request $request)
    {
        $datadaftarmaba = $this->akademik->daftarmaba($request);
        return $datadaftarmaba;
    }
    // Maba
    public function mahasiswalulusan1()
    {
        $datamahasiswalulusan1 = $this->akademik->mahasiswalulusan1();
        return $datamahasiswalulusan1;
    }
    public function mahasiswalulusan2()
    {
        $datamahasiswalulusan2 = $this->akademik->mahasiswalulusan2();
        return $datamahasiswalulusan2;
    }

    public function status_lulus_mahasiswa(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mhs' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $status_lulus_mahasiswa = $this->akademik->status_lulus_mahasiswa($request);

            if ($status_lulus_mahasiswa) {
                return response()->json(['berhasil' => 'Data berhasil diubah !']);
            } else {
                return response()->json(['gagal' => 'data gagal diubah !']);
            }
        }
    }

    public function status_mengundurkan_diri_mahasiswa(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mhs' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $status_mengundurkan_diri_mahasiswa = $this->akademik->status_mengundurkan_diri_mahasiswa($request);

            if ($status_mengundurkan_diri_mahasiswa) {
                return response()->json(['berhasil' => 'Data berhasil diubah !']);
            } else {
                return response()->json(['gagal' => 'data gagal diubah !']);
            }
        }
    }

    public function status_dikeluarkan_mahasiswa(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mhs' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $status_dikeluarkan_mahasiswa = $this->akademik->status_dikeluarkan_mahasiswa($request);

            if ($status_dikeluarkan_mahasiswa) {
                return response()->json(['berhasil' => 'Data berhasil diubah !']);
            } else {
                return response()->json(['gagal' => 'data gagal diubah !']);
            }
        }
    }

    public function status_batal_mahasiswa(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_mhs' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $status_batal_mahasiswa = $this->akademik->status_batal_mahasiswa($request);

            if ($status_batal_mahasiswa) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function tampil_tahunakademik2()
    {
        $tahunakademik = $this->akademik->tampiltahunakademik();
        return $tahunakademik;
    }

    public function tampilkegiatanakademik()
    {
        $kegiatanakademik = $this->akademik->tampilkegiatanakademik();
        return $kegiatanakademik;
    }
    public function cetakkartuhasilstudi(Request $request)
    {
        $cetakkartuhasilstudi = $this->akademik->cetakkartuhasilstudi($request);
        return $cetakkartuhasilstudi;
    }
    public function cetaktranskipnilai(Request $request)
    {
        $cetaktranskipnilai = $this->akademik->cetaktranskipnilai($request);
        return $cetaktranskipnilai;
    }
    public function cetaktranskipakademik(Request $request)
    {
        $cetaktranskipakademik = $this->akademik->cetaktranskipakademik($request);
        return $cetaktranskipakademik;
    }
    public function cetaktranskipakademikinggris(Request $request)
    {
        $cetaktranskipakademikinggris = $this->akademik->cetaktranskipakademikinggris($request);
        return $cetaktranskipakademikinggris;
    }
    public function cetakdaftarhadirkuliah(Request $request)
    {
        $cetakdaftarhadirkuliah = $this->akademik->cetakdaftarhadirkuliah($request);
        return $cetakdaftarhadirkuliah;
    }
    public function cetakdaftarhadirujian(Request $request)
    {
        $cetakdaftarhadirujian = $this->akademik->cetakdaftarhadirujian($request);
        return $cetakdaftarhadirujian;
    }
    public function cetakkartuujian(Request $request)
    {
        $cetakkartuujian = $this->akademik->cetakkartuujian($request);
        return $cetakkartuujian;
    }

    public function tampilsemester()
    {
        $semester = $this->akademik->tampilsemester();
        return $semester;
    }

    public function edit_transkipnilai(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'enim' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edittranskipnilai = $this->akademik->edit_transkipnilai($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    public function edittampilfakultas()
    {
        $edittampilfakultas = $this->akademik->edittampilfakultas();
        return $edittampilfakultas;
        // $edittampilpimpinan = DB::select("SELECT * FROM simpeg_pegawai WHERE kode_jenis='1' order by nama asc");
        // return $edittampilpimpinan;
    }
    public function edittampilprogramstudi()
    {
        $edittampilprogramstudi = $this->akademik->edittampilprogramstudi();
        return $edittampilprogramstudi;
    }

    public function tampilmhs()
    {
        $tampilmhs = $this->akademik->tampilmhs();
        return $tampilmhs;
    }

    public function tampilperprodi(Request $request)
    {
        $tampilperprodi = $this->akademik->tampilperprodi($request);
        return $tampilperprodi;
    }

    public function tampiljalurpmb()
    {
        $tampiljalurpmb = $this->akademik->tampiljalurpmb();
        return $tampiljalurpmb;
    }

    public function tampilprovinsi()
    {
        $tampilprovinsi = $this->akademik->tampilprovinsi();
        return $tampilprovinsi;
    }

    public function editmaba()
    {
        $dataeditmaba = $this->akademik->editmaba();
        return $dataeditmaba;
    }
    public function edit_camaba(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_camaba' => 'required',
            'no_pendaftaran11' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edit_camaba = $this->akademik->edit_camaba($request);

            return response()->json(['success' => 'Data Mahasiswa berhasil diubah !']);
        }
    }

    public function ubahstatus_camaba(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_camaba' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $ubahstatuscamaba = $this->akademik->ubahstatus_camaba($request);

            if ($ubahstatuscamaba) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }

    public function detail_camaba(Request $request)
    {
        $detail_camaba = $this->akademik->detail_camaba($request);
        return $detail_camaba;
    }

    // public function select_nilai()
    // {
    //     $select_nilai = $this->akademik->select_nilai();
    //     return $select_nilai;
    // }
    public function select_nilai(Request $request)
    {
        $select2_nilai = $this->akademik->select_nilai($request);
        return $select2_nilai;
    }

    public function simpan_nilai_akhir(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'makul' => 'required',
            'nilai_huruf_akhir' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_nilai_akhir = $this->akademik->simpan_nilai_akhir($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function simpan_nilai_akhir1(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'makul' => 'required',
            'nilai_huruf_akhir' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $simpan_nilai_akhir1 = $this->akademik->simpan_nilai_akhir1($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function tampilkabupaten(Request $request)
    {
        $tampilkabupaten = $this->akademik->tampilkabupaten($request);
        return $tampilkabupaten;
    }

    public function simpan_camaba(Request $request)
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
            $this->akademik->simpan_camaba($request);

            return response()->json(['success' => 'Data berhasil ditambahkan !']);
        }
    }

    public function getdaftarhadirkuliah_cetak(Request $request)
    {
        $getdaftarhadirkuliah_cetak = $this->akademik->getdaftarhadirkuliah_cetak($request);
        return $getdaftarhadirkuliah_cetak;
    }
    public function getdaftarhadirkuliah_cetak1(Request $request)
    {
        $getdaftarhadirkuliah_cetak1 = $this->akademik->getdaftarhadirkuliah_cetak1($request);
        return $getdaftarhadirkuliah_cetak1;
    }    
    public function cetakdaftarhadirkuliah1(Request $request)
    {
        $cetakdaftarhadirkuliah1 = $this->akademik->cetakdaftarhadirkuliah1($request);
        return $cetakdaftarhadirkuliah1;
    }
    public function getdaftarhadirujian_cetak(Request $request)
    {
        $getdaftarhadirujian_cetak = $this->akademik->getdaftarhadirujian_cetak($request);
        return $getdaftarhadirujian_cetak;
    }
    public function cetakdaftarhadirujian1(Request $request)
    {
        $cetakdaftarhadirujian1 = $this->akademik->cetakdaftarhadirujian1($request);
        return $cetakdaftarhadirujian1;
    }
    public function getkartuujian_cetak(Request $request)
    {
        $getkartuujian_cetak = $this->akademik->getkartuujian_cetak($request);
        return $getkartuujian_cetak;
    }
    public function cetakkartuujian1(Request $request)
    {
        $cetakkartuujian1 = $this->akademik->cetakkartuujian1($request);
        return $cetakkartuujian1;
    }
    public function select_makulprasyarat(Request $request)
    {
        $select_makulprasyarat = $this->akademik->select_makulprasyarat($request);
        return $select_makulprasyarat;
    }
    public function ceknimterakhir(Request $request)
    {
        $ceknimterakhir = $this->akademik->ceknimterakhir($request);
        return $ceknimterakhir;
    }

    public function ubahstatus_registrasi(Request $request)
    {

        // $validation = Validator::make($request->all(), [
        //     'no_pendaftaran' => 'required'
        // ]);

        // if ($validation->fails()) {
        //     return response()->json(['error' => $validation->errors()->all()]);
        // } else {
        $ubahstatusregistrasi = $this->akademik->ubahstatus_registrasi($request);

        if ($ubahstatusregistrasi) {
            return response()->json(['berhasil' => 'Data berhasil dihapus !']);
        } else {
            return response()->json(['gagal' => 'data gagal dihapus !']);
        }
        // }
    }
    public function edittampilkurikulum()
    {
        $edittampilkurikulum = $this->akademik->edittampilkurikulum();
        return $edittampilkurikulum;
    }
    public function edittampiljenisher()
    {
        $edittampiljenisher = $this->akademik->edittampiljenisher();
        return $edittampiljenisher;
    }

    public function hapus_herregistrasi(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'id_heregistrasi' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['gagal' => $validation->errors()->all()]);
        } else {
            $Qhapus = $this->akademik->hapus_herregistrasi($request);

            if ($Qhapus) {
                return response()->json(['berhasil' => 'Data berhasil dihapus !']);
            } else {
                return response()->json(['gagal' => 'data gagal dihapus !']);
            }
        }
    }
    public function cetakdaftarhadirujianjamak(Request $request)
    {
        $cetakdaftarhadirujianjamak = $this->akademik->cetakdaftarhadirujianjamak($request);
        return $cetakdaftarhadirujianjamak;
    }
    // KRS Mahasiswa
    public function krsmahasiswa(Request $request)
    {
        $datakrsmahasiswa = $this->akademik->krsmahasiswa($request);
        return $datakrsmahasiswa;
    }
    public function cetakkrsmahasiswa(Request $request)
    {
        $cetakkrsmahasiswa = $this->akademik->cetakkrsmahasiswa($request);
        return $cetakkrsmahasiswa;
    }
    public function cetakkrsmahasiswa1(Request $request)
    {
        $cetakkrsmahasiswa1 = $this->akademik->cetakkrsmahasiswa1($request);
        return $cetakkrsmahasiswa1;
    }
    public function getkrsmahasiswa_cetak(Request $request)
    {
        $getkrsmahasiswa_cetak = $this->akademik->getkrsmahasiswa_cetak($request);
        return $getkrsmahasiswa_cetak;
    }
    public function cetakkartuhasilstudi1(Request $request)
    {
        $cetakkartuhasilstudi1 = $this->akademik->cetakkartuhasilstudi1($request);
        return $cetakkartuhasilstudi1;
    }
    public function getSeluruhKHS1(Request $request)
    {
        $data = $this->akademik->getSeluruhKHS1($request);
        return $data;
    }
    public function getkartuhasilstudi_cetak(Request $request)
    {
        $getkartuhasilstudi_cetak = $this->akademik->getkartuhasilstudi_cetak($request);
        return $getkartuhasilstudi_cetak;
    }
    public function list_sksambil_already(Request $request)
    {
        $list_sksambil_already = $this->akademik->list_sksambil_already($request);
        return $list_sksambil_already;
    }
    public function list_sksbayar_already(Request $request)
    {
        $list_sksbayar_already = $this->akademik->list_sksbayar_already($request);
        return $list_sksbayar_already;
    }
    public function cetaktranskipnilai1(Request $request)
    {
        $cetaktranskipnilai1 = $this->akademik->cetaktranskipnilai1($request);
        return $cetaktranskipnilai1;
    }
    public function gettranskipnilai_cetak(Request $request)
    {
        $gettranskipnilai_cetak = $this->akademik->gettranskipnilai_cetak($request);
        return $gettranskipnilai_cetak;
    }
    public function tampilno_transkip()
    {
        $tampilno_transkip = $this->akademik->tampilno_transkip();
        return $tampilno_transkip;
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
            $this->akademik->save_mhs_dosenwali($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    public function daftar_mahasiswa(Request $request)
    {
        $daftar_mahasiswa = $this->akademik->daftar_mahasiswa($request);
        return $daftar_mahasiswa;
    }
    public function list_mhs_already(Request $request)
    {
        $list_mhs_already = $this->akademik->list_mhs_already($request);
        return $list_mhs_already;
    }
    public function saveAllQrCode(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        }

        $namafile = $request->nidn . ".png";
        // Simpan ke Database (opsional)
        DB::table('akd_qrcode')->updateOrInsert(
            // Kondisi pencarian
            ['id_dosen' => $request->id],
            // Data yang akan diperbarui atau disisipkan
            [
                'qrcode' => $namafile,
                'valid_id' => $request->valid_id,
            ]
        );
    }

    public function saveAllQrCodeManajemen(Request $request)
    {
        $namafile = $request->nidn;
        // Simpan ke Database (opsional)
        DB::table('akd_qrcode_manajemen')->updateOrInsert(
            // Kondisi pencarian
            ['id_dosen' => $request->id],
            // Data yang akan diperbarui atau disisipkan
            [
                'qrcode' => $namafile,
                'jenis' => $request->jenis,
                'valid_id' => $request->valid_id,
            ]
        );


        if ($request->jenis == 'Fakultas') {
            // Update tabel akd_fakultas jika jenis adalah fakultas
            DB::table('akd_fakultas')
                ->where('pimpinan', $request->id)
                ->update([
                    'valid_id' => $request->valid_id,
                ]);
        } elseif ($request->jenis == 'Prodi') {
            // Update tabel akd_program_studi jika jenis adalah prodi
            DB::table('akd_program_studi')
                ->where('pimpinan_prodi', $request->id)
                ->update([
                    'valid_id' => $request->valid_id,
                ]);
        }
    }
    public function saveAllQrCodeACC(Request $request)
    {
        $qrdosen = DB::table('akd_heregistrasi')
            ->where('id_heregistrasi', $request->id)
            ->update([
                'valid_id' => $request->valid_id
            ]);
        return $qrdosen;
    }

    public function qrdosen(Request $request)
    {
        $qrdosen = $this->akademik->qrdosen($request);
        return $qrdosen;
    }
    public function qrdosenmanajemen(Request $request)
    {
        $qrdosen = $this->akademik->qrdosenmanajemen($request);
        return $qrdosen;
    }

    public function hapus_mhs_dosen_wali(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $this->akademik->hapus_mhs_dosen_wali($request);

            return response()->json(['success' => 'Data berhasil dihapus !']);
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
            $this->akademik->nonaktif_mhs_dosenwali($request);

            return response()->json(['success' => 'Data berhasil dinonaktifkan !']);
        }
    }
    public function ceknimakademik(Request $request)
    {
        $ceknimakademik = $this->akademik->ceknimakademik($request);
        return $ceknimakademik;
    }

    public function edittranskipakademik(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'enim' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()]);
        } else {
            $edittranskipakademik = $this->akademik->edittranskipakademik($request);

            return response()->json(['success' => 'Data berhasil diubah !']);
        }
    }
    public function edittampilagama()
    {
        $edittampilagama = $this->akademik->edittampilagama();
        return $edittampilagama;
    }
    public function edittampilkelas()
    {
        $edittampilkelas = $this->akademik->edittampilkelas();
        return $edittampilkelas;
    }
    public function edittampilstatusnikah()
    {
        $edittampilstatusnikah = $this->akademik->edittampilstatusnikah();
        return $edittampilstatusnikah;
    }
    public function edittampiljalurpmb()
    {
        $edittampiljalurpmb = $this->akademik->edittampiljalurpmb();
        return $edittampiljalurpmb;
    }
    public function edittampilkewarganegaraan()
    {
        $edittampilkewarganegaraan = $this->akademik->edittampilkewarganegaraan();
        return $edittampilkewarganegaraan;
    }
    public function edittampiljenjangpendidikan()
    {
        $edittampiljenjangpendidikan = $this->akademik->edittampiljenjangpendidikan();
        return $edittampiljenjangpendidikan;
    }
    public function edittampiljenispekerjaan()
    {
        $edittampiljenispekerjaan = $this->akademik->edittampiljenispekerjaan();
        return $edittampiljenispekerjaan;
    }
    public function edittampilpenghasilan()
    {
        $edittampilpenghasilan = $this->akademik->edittampilpenghasilan();
        return $edittampilpenghasilan;
    }
    public function tampiljenistinggal()
    {
        $tampiljenistinggal = $this->akademik->tampiljenistinggal();
        return $tampiljenistinggal;
    }
    public function tampiltransportasi()
    {
        $tampiljenistinggal = $this->akademik->tampiltransportasi();
        return $tampiljenistinggal;
    }
    public function tampiljalurpendaftaran()
    {
        $tampiljenistinggal = $this->akademik->tampiljalurpendaftaran();
        return $tampiljenistinggal;
    }
    public function tampiljenispendaftaran()
    {
        $tampiljenispendaftaran = $this->akademik->tampiljenispendaftaran();
        return $tampiljenispendaftaran;
    }
    public function modal_sks_ambil2(Request $request)
    {
        $modal_sks_ambil2 = $this->akademik->modal_sks_ambil2($request);
        return $modal_sks_ambil2;
    }
    public function modal_ips_ambil(Request $request)
    {
        $modal_ips_ambil = $this->akademik->modal_ips_ambil($request);
        return $modal_ips_ambil;
    }
    public function edittampilkegiatanakademik()
    {
        $edittampilkegiatanakademik = $this->akademik->edittampilkegiatanakademik();
        return $edittampilkegiatanakademik;
    }
}

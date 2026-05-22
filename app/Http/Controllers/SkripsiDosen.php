<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Mskripsi;

class SkripsiDosen extends Controller
{
    /**
     * Dashboard Dosen Pembimbing (Daftar Mahasiswa)
     */
    public function dashboard(Request $request)
    {
        $id_dosen = $request->id_dosen;
        $tahun = $request->tahun;
        $semester = $request->semester;

        if (!$id_dosen) return response()->json(['error' => 'ID Dosen diperlukan'], 400);

        $m = new Mskripsi();
        $mahasiswa = $m->getMahasiswaBimbingan($id_dosen, $tahun, $semester);

        return response()->json([
            'status' => 'success',
            'data' => $mahasiswa
        ]);
    }

    /**
     * Ambil Log Bimbingan Mahasiswa Tertentu
     */
    public function log_bimbingan(Request $request)
    {
        $id_dosen = $request->id_dosen;
        $id_skripsi = $request->id_skripsi;

        if (!$id_dosen || !$id_skripsi) {
            return response()->json(['error' => 'Parameter id_dosen dan id_skripsi diperlukan'], 400);
        }

        $m = new Mskripsi();
        $logs = $m->getLogBimbinganDosen($id_skripsi, $id_dosen);

        return response()->json([
            'status' => 'success',
            'data' => $logs
        ]);
    }

    /**
     * Validasi (ACC/Tolak) Catatan Bimbingan Mahasiswa
     */
    public function validasi_bimbingan(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_log' => 'required',
            'id_dosen' => 'required',
            'status' => 'required|in:disetujui,revisi',
            'catatan_dosen' => 'nullable|string'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        // Update Log
        DB::table('akd_skripsi_bimbingan')
            ->where('id', $request->id_log)
            ->update([
                'status' => $request->status,
                'catatan_dosen' => $request->catatan_dosen,
                'updated_at' => now()
            ]);

        return response()->json(['success' => 'Status Bimbingan Berhasil Diperbarui']);
    }

    /**
     * ACC Ujian (Semester/Sidang Akhir)
     */
    public function acc_ujian(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi' => 'required',
            'id_dosen' => 'required',
            'fase' => 'required|in:sempro,ujian', // Mana yang di-ACC
            'status_acc' => 'required|in:1,0' // 1: ACC, 0: Cabut ACC
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        // Get skripsi data to get NIM
        $skripsi = DB::table('akd_skripsi')->where('id', $request->id_skripsi)->first();
        if (!$skripsi) {
            return response()->json(['error' => 'Data skripsi tidak ditemukan'], 404);
        }

        $nim = $skripsi->nim;
        $status_acc = $request->status_acc == '1';

        if ($request->fase == 'sempro') {
            // Update akd_skripsi_proposal
            // Jika ACC=1, status menjadi 'diajukan' (siap untuk dijadwalkan)
            // Jika ACC=0, status kembali ke 'draft' atau status sebelumnya
            $newStatus = $status_acc ? 'diajukan' : 'draft';
            
            DB::table('akd_skripsi_proposal')
                ->where('id_skripsi', $request->id_skripsi)
                ->where('nim', $nim)
                ->orderBy('iterasi', 'desc')
                ->update([
                    'status' => $newStatus,
                    'updated_at' => now()
                ]);

            DB::table('akd_skripsi')
                ->where('id', $request->id_skripsi)
                ->update([
                    'fase_aktif' => $status_acc ? 'sempro' : 'bimbingan',
                    'updated_at' => now()
                ]);

        } else if ($request->fase == 'ujian') {
            // Update akd_skripsi_ujian
            // Jika ACC=1, status menjadi 'pending' (siap untuk dijadwalkan)
            // Jika ACC=0, status tetap atau direset
            $newStatus = $status_acc ? 'pending' : 'pending';
            
            // Check if ujian record exists
            $ujian = DB::table('akd_skripsi_ujian')
                ->where('id_skripsi', $request->id_skripsi)
                ->where('nim', $nim)
                ->first();
            
            if ($ujian) {
                DB::table('akd_skripsi_ujian')
                    ->where('id', $ujian->id)
                    ->update([
                        'status' => $newStatus,
                        'updated_at' => now()
                    ]);
            } else {
                // Create new ujian record if not exists
                DB::table('akd_skripsi_ujian')->insert([
                    'nim' => $nim,
                    'id_skripsi' => $request->id_skripsi,
                    'status' => $newStatus,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json([
            'success' => "Persetujuan {$request->fase} berhasil disimpan."
        ]);
    }

    /**
     * List Mahasiswa yang diuji oleh Dosen (sebagai Penguji/Verifikator)
     */
    public function list_mahasiswa_diuji(Request $request)
    {
        $id_dosen = $request->id_dosen;
        if (!$id_dosen) return response()->json(['error' => 'ID Dosen diperlukan'], 400);

        $rows = DB::table('akd_skripsi_ujian as u')
            ->join('akd_skripsi as s', 'u.id_skripsi', '=', 's.id')
            ->join('akd_mahasiswa as m', 'u.nim', '=', 'm.nim')
            ->join('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->leftJoin('simpeg_pegawai as peg1', 'u.id_penguji1', '=', 'peg1.id')
            ->leftJoin('simpeg_pegawai as peg2', 'u.id_penguji2', '=', 'peg2.id')
            ->leftJoin('simpeg_pegawai as peg3', 'u.id_penguji3', '=', 'peg3.id')
            ->select(
                'u.id as id_skripsi_ujian',
                'u.id_skripsi',
                'u.nim',
                'm.nama_mahasiswa',
                's.judul',
                's.target_luaran',
                DB::raw("CASE WHEN s.target_luaran IS NOT NULL AND s.target_luaran != 'buku_skripsi' THEN 1 ELSE 0 END as is_obe"),
                'm.kode_program_studi as kode_prodi',
                'p.nama_program_studi',
                'u.tanggal_ujian as tgl_ujian',
                'u.jam_mulai as jam_ujian',
                'u.ruang as ruang_ujian',
                'u.status as status_ujian',
                'u.nilai_ujian',
                'u.nilai_angka',
                'u.id_penguji1',
                'u.id_penguji2',
                'u.id_penguji3',
                DB::raw("CONCAT_WS(' ', peg1.gelar_depan, peg1.nama, peg1.gelar_belakang) as nama_penguji1"),
                DB::raw("CONCAT_WS(' ', peg2.gelar_depan, peg2.nama, peg2.gelar_belakang) as nama_penguji2"),
                DB::raw("CONCAT_WS(' ', peg3.gelar_depan, peg3.nama, peg3.gelar_belakang) as nama_penguji3")
            )
            ->where(function ($query) use ($id_dosen) {
                $query->where('u.id_penguji1', $id_dosen)
                      ->orWhere('u.id_penguji2', $id_dosen)
                      ->orWhere('u.id_penguji3', $id_dosen);
            })
            ->whereNotNull('u.tanggal_ujian')
            ->orderBy('u.tanggal_ujian', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows
        ]);
    }

    /**
     * Ambil Rubrik CPMK untuk Penilaian OBE
     */
    public function get_rubrik_cpmk(Request $request)
    {
        $kode_prodi = $request->kode_prodi;
        
        $query = DB::table('akd_skripsi_rubrik_cpmk');
        if ($kode_prodi) {
            $query->where(function($q) use ($kode_prodi) {
                $q->where('kode_prodi', $kode_prodi)
                  ->orWhereNull('kode_prodi');
            });
        } else {
            $query->whereNull('kode_prodi');
        }

        $rows = $query->orderBy('kode_cpmk', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows
        ]);
    }

    /**
     * Ambil Nilai Ujian CPMK yang sudah di-input oleh Dosen
     */
    public function get_nilai_ujian_cpmk(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'id_dosen' => 'required'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $rows = DB::table('akd_skripsi_nilai_cpmk')
            ->where('id_skripsi_ujian', $request->id_skripsi_ujian)
            ->where('id_dosen', $request->id_dosen)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows
        ]);
    }

    /**
     * Simpan Nilai Ujian Per CPMK
     */
    public function simpan_nilai_ujian_cpmk(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'id_dosen' => 'required',
            'nilai' => 'required|array', // key is id_cpmk, value is score 0-100
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $id_skripsi_ujian = $request->id_skripsi_ujian;
        $id_dosen = $request->id_dosen;

        // Simpan nilai per CPMK
        foreach ($request->nilai as $id_cpmk => $score) {
            DB::table('akd_skripsi_nilai_cpmk')->updateOrInsert(
                ['id_skripsi_ujian' => $id_skripsi_ujian, 'id_dosen' => $id_dosen, 'id_cpmk' => $id_cpmk],
                ['nilai' => $score, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Kalkulasi ulang nilai akhir ujian
        $ujian = DB::table('akd_skripsi_ujian')->where('id', $id_skripsi_ujian)->first();
        if (!$ujian) return response()->json(['error' => 'Data ujian tidak ditemukan.'], 404);

        $examiners = array_filter([$ujian->id_penguji1, $ujian->id_penguji2, $ujian->id_penguji3]);
        
        $rubrics = DB::table('akd_skripsi_rubrik_cpmk')->get()->keyBy('id');

        $examiner_scores = [];
        foreach ($examiners as $ex_id) {
            $scores = DB::table('akd_skripsi_nilai_cpmk')
                ->where('id_skripsi_ujian', $id_skripsi_ujian)
                ->where('id_dosen', $ex_id)
                ->get();

            if ($scores->count() > 0) {
                $weighted_sum = 0;
                $ex_weight = 0;
                foreach ($scores as $s) {
                    if (isset($rubrics[$s->id_cpmk])) {
                        $w = $rubrics[$s->id_cpmk]->bobot;
                        $weighted_sum += $s->nilai * $w;
                        $ex_weight += $w;
                    }
                }
                if ($ex_weight > 0) {
                    $examiner_scores[] = $weighted_sum / $ex_weight;
                }
            }
        }

        if (count($examiner_scores) > 0) {
            $final_numeric_score = array_sum($examiner_scores) / count($examiner_scores);

            // Pemetaan predikat huruf
            $letter = 'E';
            if ($final_numeric_score >= 85) $letter = 'A';
            elseif ($final_numeric_score >= 80) $letter = 'A-';
            elseif ($final_numeric_score >= 75) $letter = 'B+';
            elseif ($final_numeric_score >= 70) $letter = 'B';
            elseif ($final_numeric_score >= 65) $letter = 'B-';
            elseif ($final_numeric_score >= 60) $letter = 'C+';
            elseif ($final_numeric_score >= 55) $letter = 'C';
            elseif ($final_numeric_score >= 50) $letter = 'D';

            // Jika seluruh tim penilai/verifikator sudah memberi nilai, ubah status kelulusan
            $status = $ujian->status;
            if (count($examiner_scores) == count($examiners)) {
                $status = ($final_numeric_score >= 60) ? 'lulus' : 'tidak_lulus';
                
                // Sinkronisasi otomatis ke Transkrip Nilai
                $mhs = DB::table('akd_mahasiswa')->where('nim', $ujian->nim)->first();
                $prodi = $mhs ? $mhs->kode_program_studi : '';
                
                $skripsi_course = DB::table('akd_matakuliah')
                    ->where('kode_program_studi', $prodi)
                    ->where(function($q) {
                        $q->where('nama_matakuliah', 'like', '%skripsi%')
                          ->orWhere('nama_matakuliah', 'like', '%tugas akhir%');
                    })
                    ->first();

                if ($skripsi_course && $mhs) {
                    $cek_transkrip = DB::table('akd_transkrip')
                        ->where('nim', $ujian->nim)
                        ->where('id_matakuliah', $skripsi_course->id_matakuliah)
                        ->first();

                    if ($cek_transkrip) {
                        DB::table('akd_transkrip')
                            ->where('id_transkrip', $cek_transkrip->id_transkrip)
                            ->update(['nilai' => $letter]);
                    } else {
                        DB::table('akd_transkrip')->insert([
                            'nim' => $ujian->nim,
                            'id_matakuliah' => $skripsi_course->id_matakuliah,
                            'tahun_kurikulum' => $mhs->tahun_kurikulum ?? date('Y'),
                            'nilai' => $letter
                        ]);
                    }
                }
            }

            DB::table('akd_skripsi_ujian')
                ->where('id', $id_skripsi_ujian)
                ->update([
                    'nilai_ujian' => $letter,
                    'nilai_angka' => $final_numeric_score,
                    'status' => $status,
                    'updated_at' => now()
                ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai Ujian/Luaran berhasil disimpan.'
        ]);
    }
}
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
        $updateData = [
            'status' => $request->status,
            'catatan_dosen' => $request->catatan_dosen,
            'updated_at' => now()
        ];

        if ($request->status === 'disetujui') {
            $existing = DB::table('akd_skripsi_bimbingan')->where('id', $request->id_log)->first();
            if ($existing && empty($existing->valid_id)) {
                $updateData['valid_id'] = uniqid('bimb_', true);
            }
        } else {
            $updateData['valid_id'] = null;
        }

        DB::table('akd_skripsi_bimbingan')
            ->where('id', $request->id_log)
            ->update($updateData);

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
            // Check if ujian record exists
            $ujian = DB::table('akd_skripsi_ujian')
                ->where('id_skripsi', $request->id_skripsi)
                ->where('nim', $nim)
                ->first();
            
            if ($status_acc) {
                // Berikan ACC Ujian
                $newStatus = 'pending';
                if ($ujian) {
                    if (in_array($ujian->status, ['pending', 'revisi'])) {
                        DB::table('akd_skripsi_ujian')
                            ->where('id', $ujian->id)
                            ->update([
                                'status' => $newStatus,
                                'updated_at' => now()
                            ]);
                    }
                } else {
                    DB::table('akd_skripsi_ujian')->insert([
                        'nim' => $nim,
                        'id_skripsi' => $request->id_skripsi,
                        'status' => $newStatus,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            } else {
                // Cabut ACC Ujian
                if ($ujian) {
                    if (!in_array($ujian->status, ['pending', 'revisi'])) {
                        return response()->json(['error' => 'Pendaftaran ujian sudah diajukan atau dijadwalkan, tidak dapat membatalkan persetujuan.'], 400);
                    }
                    DB::table('akd_skripsi_ujian')->where('id', $ujian->id)->delete();
                }
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
            ->leftJoin('akd_skripsi_luaran as l', 's.id', '=', 'l.id_skripsi')
            ->select(
                'u.id as id_skripsi_ujian',
                'u.id_skripsi',
                'u.nim',
                'm.nama_mahasiswa',
                'm.kode_penilaian',
                's.judul',
                's.target_luaran',
                DB::raw("CASE WHEN s.target_luaran IS NOT NULL AND s.target_luaran != 'buku_skripsi' THEN 1 ELSE 0 END as is_obe"),
                DB::raw("CASE WHEN s.target_luaran IS NOT NULL AND s.target_luaran != 'buku_skripsi' THEN 1 ELSE 0 END as cpmk_based"),
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
                DB::raw("CONCAT_WS(' ', peg3.gelar_depan, peg3.nama, peg3.gelar_belakang) as nama_penguji3"),
                'l.url_link',
                'l.jenis_luaran'
            )
            ->where(function ($query) use ($id_dosen) {
                $query->where('u.id_penguji1', $id_dosen)
                      ->orWhere('u.id_penguji2', $id_dosen)
                      ->orWhere('u.id_penguji3', $id_dosen);
            })
            ->whereNotNull('u.tanggal_ujian')
            ->orderBy('u.tanggal_ujian', 'desc')
            ->get();

        foreach ($rows as $r) {
            if ($r->id_penguji1 == $id_dosen) {
                $r->role_dosen = 'penguji1';
            } elseif ($r->id_penguji2 == $id_dosen) {
                $r->role_dosen = 'penguji2';
            } elseif ($r->id_penguji3 == $id_dosen) {
                $r->role_dosen = 'penguji3';
            } else {
                $r->role_dosen = null;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'grade_rules' => config('grades.rules')
        ]);
    }

    /**
     * Ambil Rubrik Indikator untuk Penilaian (OBE / Reguler)
     */
    public function get_rubrik_indikator(Request $request)
    {
        $kode_prodi = $request->kode_prodi;
        $jalur = $request->query('jalur', 'reguler');
        
        $rows = collect();
        if ($kode_prodi) {
            $rows = DB::table('akd_skripsi_rubrik_indikator')
                ->where('kode_prodi', $kode_prodi)
                ->where('jalur', $jalur)
                ->orderBy('kode_indikator', 'asc')
                ->get();
        }
        
        if ($rows->isEmpty()) {
            $rows = DB::table('akd_skripsi_rubrik_indikator')
                ->whereNull('kode_prodi')
                ->where('jalur', $jalur)
                ->orderBy('kode_indikator', 'asc')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $rows
        ]);
    }

    /**
     * Ambil Nilai Ujian Indikator yang sudah di-input oleh Dosen
     */
    public function get_nilai_ujian_indikator(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'id_dosen' => 'required'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $rows = DB::table('akd_skripsi_nilai_indikator')
            ->where('id_skripsi_ujian', $request->id_skripsi_ujian)
            ->where('id_dosen', $request->id_dosen)
            ->get();

        $ba = DB::table('akd_skripsi_berita_acara')
            ->where('id_skripsi_ujian', $request->id_skripsi_ujian)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'berita_acara' => $ba
        ]);
    }

    /**
     * Simpan Nilai Ujian Per Indikator
     */
    public function simpan_nilai_ujian_indikator(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'id_dosen' => 'required',
            'nilai' => 'required|array', // key is id_rubrik_indikator, value is score 0-100
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $id_skripsi_ujian = $request->id_skripsi_ujian;
        $id_dosen = $request->id_dosen;

        $ujian = DB::table('akd_skripsi_ujian')->where('id', $id_skripsi_ujian)->first();
        if (!$ujian) return response()->json(['error' => 'Data ujian tidak ditemukan.'], 404);

        $mhs = DB::table('akd_mahasiswa')->where('nim', $ujian->nim)->first();
        $kode_prodi = $mhs ? $mhs->kode_program_studi : null;

        // Tentukan jalur (reguler vs obe)
        $jalur = 'reguler';
        $skripsi = DB::table('akd_skripsi')->where('id', $ujian->id_skripsi)->first();
        if ($skripsi && !empty($skripsi->target_luaran) && $skripsi->target_luaran !== 'buku_skripsi') {
            $jalur = 'obe';
        }

        // Ambil rubrik master aktif untuk mendapatkan meta/snapshot
        $rubrics_list = collect();
        if ($kode_prodi) {
            $rubrics_list = DB::table('akd_skripsi_rubrik_indikator')
                ->where('kode_prodi', $kode_prodi)
                ->where('jalur', $jalur)
                ->get();
        }
        if ($rubrics_list->isEmpty()) {
            $rubrics_list = DB::table('akd_skripsi_rubrik_indikator')
                ->whereNull('kode_prodi')
                ->where('jalur', $jalur)
                ->get();
        }
        $rubrics = $rubrics_list->keyBy('id');

        // Simpan nilai per indikator dengan snapshot
        foreach ($request->nilai as $id_rubrik => $score) {
            $rubric_meta = $rubrics->get($id_rubrik);
            
            $snapshot_data = [
                'nama_indikator_snapshot' => $rubric_meta ? $rubric_meta->nama_indikator : null,
                'aspek_snapshot' => $rubric_meta ? $rubric_meta->aspek : null,
                'bobot_snapshot' => $rubric_meta ? $rubric_meta->bobot : null,
                'tipe_bobot_snapshot' => $rubric_meta ? $rubric_meta->tipe_bobot : null,
                'nilai' => $score,
                'updated_at' => now(),
                'created_at' => now()
            ];

            DB::table('akd_skripsi_nilai_indikator')->updateOrInsert(
                ['id_skripsi_ujian' => $id_skripsi_ujian, 'id_dosen' => $id_dosen, 'id_rubrik_indikator' => $id_rubrik],
                $snapshot_data
            );
        }

        // Fetch dynamic aspects
        $aspects = DB::table('akd_skripsi_aspek')
            ->where('kode_prodi', $mhs->kode_program_studi ?? '')
            ->where('jalur', $jalur)
            ->get()
            ->keyBy('nama_aspek');

        if ($aspects->count() == 0) {
            $aspects = collect([
                'Substansi dan Luaran' => (object)['nama_aspek' => 'Substansi dan Luaran', 'bobot' => 60.00],
                'Ujian / Presentasi' => (object)['nama_aspek' => 'Ujian / Presentasi', 'bobot' => 40.00]
            ]);
        }

        // Kalkulasi ulang nilai akhir ujian dari semua penguji
        $examiners = array_filter([$ujian->id_penguji1, $ujian->id_penguji2, $ujian->id_penguji3]);
        $examiner_scores = [];

        foreach ($examiners as $ex_id) {
            // Ambil data nilai yang sudah diinput oleh penguji ini
            $scores = DB::table('akd_skripsi_nilai_indikator')
                ->where('id_skripsi_ujian', $id_skripsi_ujian)
                ->where('id_dosen', $ex_id)
                ->get();

            if ($scores->count() > 0) {
                // Gunakan data dari snapshot jika tersedia
                $first_score = $scores->first();
                $tipe_bobot = $first_score->tipe_bobot_snapshot ?? ($rubrics_list->first()->tipe_bobot ?? 'indikator');

                if ($tipe_bobot === 'tunggal') {
                    // Opsi Tunggal (Taut Aspek) -> Rata-rata per aspek terbobot
                    $grouped_scores = [];
                    foreach ($scores as $s) {
                        $aspek = $s->aspek_snapshot ?? (isset($rubrics[$s->id_rubrik_indikator]) ? $rubrics[$s->id_rubrik_indikator]->aspek : 'Substansi dan Luaran');
                        $matched_aspek = $aspek;
                        foreach ($aspects as $name => $aspectObj) {
                            if (strcasecmp($name, $aspek) === 0) {
                                $matched_aspek = $name;
                                break;
                            }
                        }
                        $grouped_scores[$matched_aspek][] = floatval($s->nilai);
                    }

                    $ex_score = 0;
                    foreach ($grouped_scores as $aspName => $valArray) {
                        $avg = count($valArray) > 0 ? (array_sum($valArray) / count($valArray)) : 0;
                        $w = isset($aspects[$aspName]) ? floatval($aspects[$aspName]->bobot) : (str_contains(strtolower($aspName), 'ujian') ? 40.00 : 60.00);
                        $ex_score += $avg * ($w / 100);
                    }
                    $examiner_scores[] = $ex_score;
                } else {
                    // Opsi Per Indikator -> Weighted sum
                    $weighted_sum = 0;
                    $ex_weight = 0;
                    foreach ($scores as $s) {
                        $w = $s->bobot_snapshot ?? (isset($rubrics[$s->id_rubrik_indikator]) ? floatval($rubrics[$s->id_rubrik_indikator]->bobot) : 0);
                        $weighted_sum += $s->nilai * $w;
                        $ex_weight += $w;
                    }
                    if ($ex_weight > 0) {
                        $examiner_scores[] = $weighted_sum / $ex_weight;
                    }
                }
            }
        }

        if (count($examiner_scores) > 0) {
            $final_numeric_score = array_sum($examiner_scores) / count($examiner_scores);

            // Pemetaan predikat huruf dari konfigurasi berdasarkan kode_penilaian mahasiswa
            $kode_penilaian = $mhs ? (int)$mhs->kode_penilaian : 1;
            $rules = config('grades.rules.' . $kode_penilaian, config('grades.rules.1'));

            $letter = 'E';
            foreach ($rules as $rule) {
                if ($final_numeric_score >= $rule['min']) {
                    $letter = $rule['grade'];
                    break;
                }
            }

            // Buat atau update record Berita Acara
            $status = $ujian->status;
            if (count($examiner_scores) == count($examiners)) {
                if (!in_array($ujian->status, ['menunggu_penetapan', 'ditetapkan', 'lulus', 'tidak_lulus'])) {
                    $status = 'menunggu_penetapan';
                }
            } else {
                if (!in_array($ujian->status, ['menunggu_penetapan', 'ditetapkan', 'lulus', 'tidak_lulus'])) {
                    $status = 'dinilai';
                }
            }

            $existing_ba = DB::table('akd_skripsi_berita_acara')
                ->where('id_skripsi_ujian', $id_skripsi_ujian)
                ->first();

            $baData = [
                'nilai_angka' => $final_numeric_score,
                'nilai_huruf' => $letter,
                'updated_at'  => now(),
            ];
            if ($request->has('catatan') && $request->catatan !== null) {
                $baData['catatan'] = $request->catatan;
            }
            if ($id_dosen == $ujian->id_penguji1 || $id_dosen == $ujian->id_penguji2) {
                if ($request->has('keputusan')) {
                    $baData['keputusan'] = $request->keputusan;
                }
                if ($request->has('batas_revisi') && $request->batas_revisi !== null) {
                    $baData['batas_revisi'] = $request->batas_revisi;
                }
            }

            if (!$existing_ba) {
                $baData['id_skripsi_ujian'] = $id_skripsi_ujian;
                $baData['nim']              = $ujian->nim;
                $baData['id_penguji1']      = $ujian->id_penguji1;
                $baData['id_penguji2']      = $ujian->id_penguji2;
                $baData['id_penguji3']      = $ujian->id_penguji3;
                $baData['status']           = 'menunggu_ttd';
                $baData['created_at']        = now();
                DB::table('akd_skripsi_berita_acara')->insert($baData);
            } else {
                DB::table('akd_skripsi_berita_acara')
                    ->where('id_skripsi_ujian', $id_skripsi_ujian)
                    ->update($baData);
            }

            DB::table('akd_skripsi_ujian')
                ->where('id', $id_skripsi_ujian)
                ->update([
                    'nilai_ujian' => $letter,
                    'nilai_angka' => $final_numeric_score,
                    'status'      => $status,
                    'updated_at'  => now()
                ]);

            // Notify Kaprodi about the new exam grade entry
            $kaprodi = DB::table('akd_mahasiswa as m')
                ->join('akd_program_studi as prodi', 'm.kode_program_studi', '=', 'prodi.kode_program_studi')
                ->leftJoin('simpeg_pegawai as kps', 'prodi.pimpinan_prodi', '=', 'kps.id')
                ->where('m.nim', $ujian->nim)
                ->select('kps.username', 'kps.nidn')
                ->first();

            if ($kaprodi) {
                $dosenName = DB::table('simpeg_pegawai')->where('id', $id_dosen)->value('nama') ?? 'Dosen Penguji';
                $kpsUser = $kaprodi->username ?: $kaprodi->nidn;
                if ($kpsUser) {
                    \App\Helpers\NotificationHelper::send(
                        $kpsUser,
                        'Input Nilai Ujian Skripsi',
                        "Dosen {$dosenName} telah menginputkan nilai ujian untuk mahasiswa {$ujian->nim}.",
                        '/kaprodi/skripsi/penetapan',
                        'skripsi'
                    );
                }
            }

        }

        return response()->json([
            'status' => 'success',
            'message' => 'Nilai Ujian/Luaran berhasil disimpan.'
        ]);
    }

    /**
     * Ambil detail Berita Acara Penetapan untuk satu ujian
     */
    public function get_berita_acara(Request $request, $id_skripsi_ujian = null)
    {
        $id_skripsi_ujian = $id_skripsi_ujian ?? $request->id_skripsi_ujian;
        if ($id_skripsi_ujian) {
            $request->merge(['id_skripsi_ujian' => $id_skripsi_ujian]);
        }

        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'id_dosen'         => 'nullable',
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $id_dosen = $request->id_dosen;

        $ujian = DB::table('akd_skripsi_ujian as u')
            ->join('akd_skripsi as s', 'u.id_skripsi', '=', 's.id')
            ->join('akd_mahasiswa as m', 'u.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as prodi', 'm.kode_program_studi', '=', 'prodi.kode_program_studi')
            ->leftJoin('akd_fakultas as fak', 'prodi.kode_fakultas', '=', 'fak.kode_fakultas')
            ->leftJoin('simpeg_pegawai as kps', 'prodi.pimpinan_prodi', '=', 'kps.id')
            ->leftJoin('simpeg_pegawai as dekan', 'fak.pimpinan', '=', 'dekan.id')
            ->leftJoin('simpeg_pegawai as p1', 'u.id_penguji1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 'u.id_penguji2', '=', 'p2.id')
            ->leftJoin('simpeg_pegawai as p3', 'u.id_penguji3', '=', 'p3.id')
            ->select(
                'u.*', 's.judul', 's.target_luaran', 'm.nama_mahasiswa', 'm.kode_program_studi as kode_prodi',
                'prodi.nama_program_studi', 'fak.nama_fakultas',
                DB::raw("CONCAT_WS(' ', kps.gelar_depan, kps.nama, kps.gelar_belakang) as nama_kaprodi"),
                'kps.nidn as nidn_kaprodi',
                DB::raw("CONCAT_WS(' ', dekan.gelar_depan, dekan.nama, dekan.gelar_belakang) as nama_dekan"),
                'dekan.nidn as nidn_dekan',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_penguji1"),
                'p1.nidn as nidn_penguji1',
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_penguji2"),
                'p2.nidn as nidn_penguji2',
                DB::raw("CONCAT_WS(' ', p3.gelar_depan, p3.nama, p3.gelar_belakang) as nama_penguji3"),
                'p3.nidn as nidn_penguji3'
            )
            ->where('u.id', $id_skripsi_ujian)
            ->first();

        if (!$ujian) return response()->json(['error' => 'Data ujian tidak ditemukan.'], 404);

        $jalur = (!empty($ujian->target_luaran) && $ujian->target_luaran !== 'buku_skripsi') ? 'obe' : 'reguler';
        $ujian->is_obe = ($jalur === 'obe') ? 1 : 0;

        $is_penguji = false;
        if ($id_dosen) {
            // Pastikan dosen adalah salah satu penguji
            $is_penguji = in_array($id_dosen, array_filter([
                $ujian->id_penguji1, $ujian->id_penguji2, $ujian->id_penguji3
            ]));
            if (!$is_penguji) return response()->json(['error' => 'Anda tidak terdaftar sebagai penguji mahasiswa ini.'], 403);
        }

        // Ambil Berita Acara
        $ba = DB::table('akd_skripsi_berita_acara')
            ->where('id_skripsi_ujian', $id_skripsi_ujian)
            ->first();

        // Ambil nilai per indikator dari semua penguji (mendukung fallback data historis)
        $nilai_indikator = DB::table('akd_skripsi_nilai_indikator as nc')
            ->leftJoin('akd_skripsi_rubrik_indikator as r', 'nc.id_rubrik_indikator', '=', 'r.id')
            ->select(
                'nc.*',
                DB::raw("COALESCE(nc.nama_indikator_snapshot, r.nama_indikator) as nama_indikator"),
                DB::raw("COALESCE(nc.aspek_snapshot, r.aspek) as aspek"),
                DB::raw("COALESCE(nc.bobot_snapshot, r.bobot) as bobot"),
                DB::raw("COALESCE(nc.tipe_bobot_snapshot, r.tipe_bobot) as tipe_bobot")
            )
            ->where('nc.id_skripsi_ujian', $id_skripsi_ujian)
            ->get();

        // Ambil aspek penilaian untuk program studi ini
        $aspek = DB::table('akd_skripsi_aspek')
            ->where('kode_prodi', $ujian->kode_prodi ?? '')
            ->where('jalur', $jalur)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'ujian'           => $ujian,
                'berita_acara'    => $ba,
                'nilai_indikator' => $nilai_indikator,
                'nilai_cpmk'      => $nilai_indikator, // compatibility fallback
                'aspek'           => $aspek,
                'is_penguji'      => $is_penguji,
                'peran_saya'      => $id_dosen ? (($id_dosen == $ujian->id_penguji1) ? 'penguji1' :
                                       (($id_dosen == $ujian->id_penguji2) ? 'penguji2' : 'penguji3')) : 'kaprodi',
            ]
        ]);
    }

    /**
     * Dosen menyetujui (TTD digital) Berita Acara Penetapan
     */
    public function setuju_berita_acara(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'id_dosen'         => 'required',
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $id_skripsi_ujian = $request->id_skripsi_ujian;
        $id_dosen = $request->id_dosen;

        $ujian = DB::table('akd_skripsi_ujian')
            ->where('id', $id_skripsi_ujian)->first();
        if (!$ujian) return response()->json(['error' => 'Data ujian tidak ditemukan.'], 404);

        $ba = DB::table('akd_skripsi_berita_acara')
            ->where('id_skripsi_ujian', $id_skripsi_ujian)->first();
        if (!$ba) return response()->json(['error' => 'Berita Acara belum dibuat. Pastikan semua penguji sudah input nilai.'], 404);

        // Tentukan kolom setuju & valid_id berdasarkan peran dosen
        $updateData = ['updated_at' => now()];
        if ($id_dosen == $ujian->id_penguji1 && !$ba->setuju_penguji1) {
            $updateData['setuju_penguji1'] = now();
            $updateData['valid_id_penguji1'] = $ba->valid_id_penguji1 ?: uniqid('ba_p1_', true);
        } elseif ($id_dosen == $ujian->id_penguji2 && !$ba->setuju_penguji2) {
            $updateData['setuju_penguji2'] = now();
            $updateData['valid_id_penguji2'] = $ba->valid_id_penguji2 ?: uniqid('ba_p2_', true);
        } elseif ($id_dosen == $ujian->id_penguji3 && !$ba->setuju_penguji3) {
            $updateData['setuju_penguji3'] = now();
            $updateData['valid_id_penguji3'] = $ba->valid_id_penguji3 ?: uniqid('ba_p3_', true);
        } else {
            return response()->json(['error' => 'Anda sudah menyetujui atau tidak terdaftar sebagai penguji.'], 400);
        }

        DB::table('akd_skripsi_berita_acara')
            ->where('id_skripsi_ujian', $id_skripsi_ujian)
            ->update($updateData);

        // Re-check: apakah semua penguji yang terdaftar sudah TTD?
        $ba_updated = DB::table('akd_skripsi_berita_acara')
            ->where('id_skripsi_ujian', $id_skripsi_ujian)->first();

        $penguji_ids = array_filter([
            $ujian->id_penguji1, $ujian->id_penguji2, $ujian->id_penguji3
        ]);
        $sudah_ttd = 0;
        if ($ujian->id_penguji1 && $ba_updated->setuju_penguji1) $sudah_ttd++;
        if ($ujian->id_penguji2 && $ba_updated->setuju_penguji2) $sudah_ttd++;
        if ($ujian->id_penguji3 && $ba_updated->setuju_penguji3) $sudah_ttd++;

        if ($sudah_ttd >= count($penguji_ids)) {
            // Semua penguji sudah TTD → BA selesai
            DB::table('akd_skripsi_berita_acara')
                ->where('id_skripsi_ujian', $id_skripsi_ujian)
                ->update(['status' => 'selesai', 'updated_at' => now()]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Persetujuan Berita Acara berhasil dicatat.',
            'sudah_ttd' => $sudah_ttd,
            'total_penguji' => count($penguji_ids),
        ]);
    }

    /**
     * Update Nomor Berita Acara & Batas Revisi oleh Admin/Akademik
     */
    public function update_berita_acara_by_admin(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'nomor_ba'         => 'nullable|string|max:100',
            'batas_revisi'     => 'nullable|date',
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $id_skripsi_ujian = $request->id_skripsi_ujian;
        $nomor_ba = $request->nomor_ba;
        $batas_revisi = $request->batas_revisi;

        $ba = DB::table('akd_skripsi_berita_acara')
            ->where('id_skripsi_ujian', $id_skripsi_ujian)->first();

        $updateData = [
            'nomor_ba'     => $nomor_ba,
            'batas_revisi' => $batas_revisi,
            'updated_at'   => now(),
        ];

        if (!$ba) {
            $ujian = DB::table('akd_skripsi_ujian')
                ->where('id', $id_skripsi_ujian)->first();
            if (!$ujian) return response()->json(['error' => 'Data ujian tidak ditemukan.'], 404);

            $updateData['id_skripsi_ujian'] = $id_skripsi_ujian;
            $updateData['nim']              = $ujian->nim;
            $updateData['id_penguji1']      = $ujian->id_penguji1;
            $updateData['id_penguji2']      = $ujian->id_penguji2;
            $updateData['id_penguji3']      = $ujian->id_penguji3;
            $updateData['status']           = 'menunggu_ttd';
            $updateData['created_at']       = now();

            DB::table('akd_skripsi_berita_acara')->insert($updateData);
        } else {
            DB::table('akd_skripsi_berita_acara')
                ->where('id_skripsi_ujian', $id_skripsi_ujian)
                ->update($updateData);
        }

        // Notify Mahasiswa and Dosen Pembimbing about the Berita Acara publication
        $mhs = DB::table('akd_skripsi_ujian as u')
            ->join('akd_skripsi as s', 'u.id_skripsi', '=', 's.id')
            ->leftJoin('simpeg_pegawai as pmb1', 's.id_dosen_pembimbing1', '=', 'pmb1.id')
            ->leftJoin('simpeg_pegawai as pmb2', 's.id_dosen_pembimbing2', '=', 'pmb2.id')
            ->where('u.id', $id_skripsi_ujian)
            ->select('u.nim', 'pmb1.username as u1', 'pmb1.nidn as n1', 'pmb2.username as u2', 'pmb2.nidn as n2')
            ->first();

        if ($mhs) {
            $msg = "Nomor Berita Acara Ujian Anda telah diterbitkan.";
            if ($nomor_ba) {
                $msg .= " Nomor: " . $nomor_ba;
            }
            if ($batas_revisi) {
                $msg .= " Batas revisi s.d " . \Carbon\Carbon::parse($batas_revisi)->locale('id')->format('d F Y') . ".";
            }

            // 1. Notify Mahasiswa
            \App\Helpers\NotificationHelper::send(
                $mhs->nim,
                'Nomor Berita Acara Diterbitkan',
                $msg,
                '/mahasiswa/skripsi/ujian',
                'skripsi'
            );

            // 2. Notify Pembimbing 1
            $pmb1User = $mhs->u1 ?: $mhs->n1;
            if ($pmb1User) {
                \App\Helpers\NotificationHelper::send(
                    $pmb1User,
                    'Berita Acara Ujian Mahasiswa Bimbingan',
                    "Nomor Berita Acara Ujian untuk mahasiswa {$mhs->nim} telah diterbitkan: " . ($nomor_ba ?: '-'),
                    '/dosen/skripsi/bimbingan',
                    'skripsi'
                );
            }

            // 3. Notify Pembimbing 2
            $pmb2User = $mhs->u2 ?: $mhs->n2;
            if ($pmb2User) {
                \App\Helpers\NotificationHelper::send(
                    $pmb2User,
                    'Berita Acara Ujian Mahasiswa Bimbingan',
                    "Nomor Berita Acara Ujian untuk mahasiswa {$mhs->nim} telah diterbitkan: " . ($nomor_ba ?: '-'),
                    '/dosen/skripsi/bimbingan',
                    'skripsi'
                );
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Nomor Berita Acara & Batas Revisi berhasil diperbarui.'
        ]);
    }
}
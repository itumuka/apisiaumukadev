<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SkripsiKaprodi extends Controller
{
    /**
     * List mahasiswa yang sedang mengambil TA di prodi tertentu
     */
    public function list_mahasiswa_ta(Request $request)
    {
        $kode_prodi = $request->kode_prodi;

        if (!$kode_prodi) {
            return response()->json(['error' => 'Parameter prodi tidak ditemukan'], 400);
        }

        $data = DB::table('akd_mahasiswa as m')
            ->leftJoin('akd_skripsi as s', 'm.nim', '=', 's.nim')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->select(
                'm.nim',
                'm.nama_mahasiswa as nama_mhs',
                's.id as id_skripsi',
                's.judul',
                's.topik',
                's.status',
                DB::raw("CASE WHEN s.target_luaran IS NOT NULL AND s.target_luaran != 'buku_skripsi' THEN 1 ELSE 0 END as is_obe"),
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_pembimbing1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_pembimbing2"),
                's.id_dosen_pembimbing1',
                's.id_dosen_pembimbing2'
            )
            ->where('m.kode_program_studi', $kode_prodi)
            ->whereNotNull('s.nim')
            ->get();

        $prodiConfig = DB::table('akd_program_studi')
            ->where('kode_program_studi', $kode_prodi)
            ->select('ta_ada_sempro', 'ta_sempro_skema')
            ->first();
        
        $ta_ada_sempro = $prodiConfig ? $prodiConfig->ta_ada_sempro : 1;
        $ta_sempro_skema = $prodiConfig ? $prodiConfig->ta_sempro_skema : 'skripsi';

        foreach ($data as $row) {
            if ($ta_ada_sempro == 0 || $ta_ada_sempro === '0' || $ta_ada_sempro === 'Tidak') {
                $row->sempro_status = 'tidak_wajib';
            } else if ($ta_sempro_skema === 'matakuliah') {
                $hasPassedMk = $this->checkSemproByMataKuliahLulus($row->nim, $kode_prodi);
                $row->sempro_status = $hasPassedMk ? 'lulus_matakuliah' : 'belum_lulus_matakuliah';
            } else {
                $proposal = DB::table('akd_skripsi_proposal')
                    ->where('nim', $row->nim)
                    ->orderBy('iterasi', 'desc')
                    ->first();
                if ($proposal) {
                    $row->sempro_status = $proposal->status;
                } else {
                    $row->sempro_status = 'belum_mengajukan';
                }
            }
        }

        return response()->json($data);
    }

    public function plot_pembimbing(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_skripsi' => 'required',
            'id_dosen_pembimbing1' => 'required|different:id_dosen_pembimbing2'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()], 422);
        }

        $id_skripsi = $request->id_skripsi;
        $pembimbing1 = $request->id_dosen_pembimbing1;
        $pembimbing2 = $request->id_dosen_pembimbing2;

        DB::beginTransaction();
        try {
            // 1. Ambil data skripsi
            $skripsi = DB::table('akd_skripsi')->where('id', $id_skripsi)->first();
            if (!$skripsi) {
                DB::rollBack();
                return response()->json(['error' => 'Data skripsi tidak ditemukan.'], 404);
            }

            $nim = $skripsi->nim;

            // Update data skripsi utama
            DB::table('akd_skripsi')
                ->where('id', $id_skripsi)
                ->update([
                    'id_dosen_pembimbing1' => $pembimbing1,
                    'id_dosen_pembimbing2' => $pembimbing2,
                    'status' => 'aktif',
                    'fase_aktif' => 'bimbingan',
                    'updated_at' => now()
                ]);

            // 2. Sinkronisasi KRS jika ada
            // Dapatkan tahun/semester aktif dari akd_mreg
            $cekta = DB::table('akd_mreg')->where('trash', '1')->first();
            if ($cekta) {
                $ta = $cekta->tahun;
                $smt = $cekta->semester;

                // Cari heregistrasi & KRS
                $hereg = DB::table('akd_heregistrasi')
                    ->where('nim', $nim)
                    ->where('tahun', $ta)
                    ->where('semester', $smt)
                    ->first();

                if ($hereg) {
                    $krs = DB::table('akd_krs')
                        ->where('id_heregistrasi', $hereg->id_heregistrasi)
                        ->first();

                    if ($krs) {
                        $id_krs = $krs->id_krs;

                        // Cari matakuliah Skripsi / Tugas Akhir / Laporan Tugas Akhir yang ditawarkan untuk prodi mahasiswa tersebut di semester aktif
                        $mhs = DB::table('akd_mahasiswa')->where('nim', $nim)->first();
                        $kode_prodi = $mhs ? $mhs->kode_program_studi : null;

                        if ($kode_prodi) {
                            // Cari penawaran matakuliah Skripsi/TA dasar untuk program studi tersebut
                            $penawaran_dasar = DB::table('akd_penawaran_matakuliah as pm')
                                ->join('akd_matakuliah as mk', 'pm.id_matakuliah', '=', 'mk.id_matakuliah')
                                ->where('pm.tahun', $ta)
                                ->where('pm.semester', $smt)
                                ->where('pm.kode_program_studi', $kode_prodi)
                                ->where(function($q) {
                                    $q->where('mk.nama_matakuliah', 'like', '%Skripsi%')
                                      ->orWhere('mk.nama_matakuliah', 'like', '%Tugas Akhir%')
                                      ->orWhere('mk.nama_matakuliah', 'like', '%Laporan Tugas Akhir%');
                                })
                                ->select('pm.*', 'mk.sks_matakuliah')
                                ->first();

                            $id_matakuliah = null;
                            $sks_matakuliah = 6;
                            $tahun_kurikulum = null;
                            $smt_matakuliah = 8;

                            if ($penawaran_dasar) {
                                $id_matakuliah = $penawaran_dasar->id_matakuliah;
                                $sks_matakuliah = $penawaran_dasar->sks_matakuliah;
                                $tahun_kurikulum = $penawaran_dasar->tahun_kurikulum;
                                $smt_matakuliah = $penawaran_dasar->smt_matakuliah;
                            } else {
                                // Fallback: cari langsung ke akd_matakuliah
                                $mk_db = DB::table('akd_matakuliah')
                                    ->where('kode_program_studi', $kode_prodi)
                                    ->where(function($q) {
                                        $q->where('nama_matakuliah', 'like', '%Skripsi%')
                                          ->orWhere('nama_matakuliah', 'like', '%Tugas Akhir%')
                                          ->orWhere('nama_matakuliah', 'like', '%Laporan Tugas Akhir%');
                                    })
                                    ->first();
                                if ($mk_db) {
                                    $id_matakuliah = $mk_db->id_matakuliah;
                                    $sks_matakuliah = $mk_db->sks_matakuliah;
                                    $tahun_kurikulum = $mk_db->tahun_kurikulum;
                                    $smt_matakuliah = $mk_db->smt_matakuliah;
                                }
                            }

                            if ($id_matakuliah) {
                                // Cari apakah kelas skripsi untuk dosen pembimbing 1 & 2 ini sudah ada
                                $kelas_exist = DB::table('akd_kelas_kuliah as kk')
                                    ->join('akd_penawaran_matakuliah as pm', 'kk.id_tawar', '=', 'pm.id_tawar')
                                    ->where('pm.tahun', $ta)
                                    ->where('pm.semester', $smt)
                                    ->where('pm.kode_program_studi', $kode_prodi)
                                    ->where('pm.id_matakuliah', $id_matakuliah)
                                    ->where('kk.kode_dosen', $pembimbing1)
                                    ->where(function($q) use ($pembimbing2) {
                                        if ($pembimbing2) {
                                            $q->where('kk.kode_dosen2', $pembimbing2);
                                        } else {
                                            $q->whereNull('kk.kode_dosen2')->orWhere('kk.kode_dosen2', '');
                                        }
                                    })
                                    ->select('kk.id_kelas', 'kk.id_tawar')
                                    ->first();

                                if ($kelas_exist) {
                                    $id_kelas = $kelas_exist->id_kelas;
                                } else {
                                    // Cari nama dosen pembimbing untuk penamaan kelas
                                    $dosen1_row = DB::table('simpeg_pegawai')->where('id', $pembimbing1)->first();
                                    $dosen1_name = $dosen1_row ? $dosen1_row->nama : 'Pembimbing';
                                    $nama_kelas = 'Skripsi - ' . substr($dosen1_name, 0, 20);

                                    // Insert penawaran matakuliah baru
                                    $id_tawar_baru = DB::table('akd_penawaran_matakuliah')->insertGetId([
                                        'tahun' => $ta,
                                        'semester' => $smt,
                                        'id_matakuliah' => $id_matakuliah,
                                        'tahun_kurikulum' => $tahun_kurikulum ?? ($mhs ? $mhs->tahun_kurikulum : date('Y')),
                                        'sks_matakuliah' => $sks_matakuliah,
                                        'smt_matakuliah' => $smt_matakuliah,
                                        'kode_program_studi' => $kode_prodi,
                                        'kode_dosen' => $pembimbing1,
                                        'kode_dosen2' => $pembimbing2
                                    ]);

                                    // Insert kelas kuliah baru
                                    $id_kelas = DB::table('akd_kelas_kuliah')->insertGetId([
                                        'id_tawar' => $id_tawar_baru,
                                        'nama_kelas' => $nama_kelas,
                                        'hari' => '-',
                                        'jam_mulai' => '00:00:00',
                                        'jam_selesai' => '00:00:00',
                                        'kode_ruang' => '-',
                                        'kapasitas_ruang' => 100,
                                        'kode_dosen' => $pembimbing1,
                                        'kode_dosen2' => $pembimbing2
                                    ]);
                                }

                                // Cek apakah mahasiswa sudah terdaftar skripsi di akd_detail_krs
                                $existing_detail = DB::table('akd_detail_krs as dk')
                                    ->join('akd_kelas_kuliah as kk', 'dk.id_kelas', '=', 'kk.id_kelas')
                                    ->join('akd_penawaran_matakuliah as pm', 'kk.id_tawar', '=', 'pm.id_tawar')
                                    ->where('dk.id_krs', $id_krs)
                                    ->where('pm.id_matakuliah', $id_matakuliah)
                                    ->select('dk.id_detail_krs', 'dk.id_kelas')
                                    ->first();

                                $old_kelas_id = null;
                                if ($existing_detail) {
                                    $old_kelas_id = $existing_detail->id_kelas;
                                    // Update ke kelas pembimbing baru
                                    DB::table('akd_detail_krs')
                                        ->where('id_detail_krs', $existing_detail->id_detail_krs)
                                        ->update([
                                            'id_kelas' => $id_kelas
                                        ]);
                                } else {
                                    // Insert baru ke akd_detail_krs
                                    DB::table('akd_detail_krs')->insert([
                                        'id_krs' => $id_krs,
                                        'id_kelas' => $id_kelas,
                                        'dtime_krs' => date('Y-m-d H:i:s')
                                    ]);
                                }

                                // Rekalkulasi SKS KRS
                                $total_sks = DB::table('akd_detail_krs as dk')
                                    ->join('akd_kelas_kuliah as kk', 'dk.id_kelas', '=', 'kk.id_kelas')
                                    ->join('akd_penawaran_matakuliah as pm', 'kk.id_tawar', '=', 'pm.id_tawar')
                                    ->join('akd_matakuliah as mk', 'pm.id_matakuliah', '=', 'mk.id_matakuliah')
                                    ->where('dk.id_krs', $id_krs)
                                    ->sum('mk.sks_matakuliah');

                                DB::table('akd_krs')
                                    ->where('id_krs', $id_krs)
                                    ->update([
                                        'sks_ambil' => $total_sks,
                                        'sks_bayar' => $total_sks,
                                        'waktu_krs' => date('Y-m-d H:i:s')
                                    ]);

                                // Update jumlah peserta kelas baru
                                $jumlah_peserta_baru = DB::table('akd_detail_krs')
                                    ->where('id_kelas', $id_kelas)
                                    ->count();
                                DB::table('akd_kelas_kuliah')
                                    ->where('id_kelas', $id_kelas)
                                    ->update(['jumlah_peserta' => $jumlah_peserta_baru]);

                                // Update jumlah peserta kelas lama (jika berbeda)
                                if ($old_kelas_id && $old_kelas_id != $id_kelas) {
                                    $jumlah_peserta_lama = DB::table('akd_detail_krs')
                                        ->where('id_kelas', $old_kelas_id)
                                        ->count();
                                    DB::table('akd_kelas_kuliah')
                                        ->where('id_kelas', $old_kelas_id)
                                        ->update(['jumlah_peserta' => $jumlah_peserta_lama]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => 'Ploting pembimbing berhasil disimpan, status skripsi diaktifkan, dan KRS mahasiswa disinkronkan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan ploting pembimbing: ' . $e->getMessage()], 500);
        }
    }

    public function plot_jadwal_sempro(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nim' => 'required',
            'tgl_ujian' => 'required',
            'id_penguji1' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()], 422);
        }

        $proposal = DB::table('akd_skripsi_proposal')
            ->where('nim', $request->nim)
            ->orderBy('iterasi', 'desc')
            ->first();

        if (!$proposal) {
            return response()->json(['error' => 'Mahasiswa belum mengunggah naskah proposal di sistem. Plotting jadwal hanya dapat dilakukan jika mahasiswa sudah mengunggah draf proposal.'], 422);
        }

        DB::table('akd_skripsi_proposal')
            ->where('id', $proposal->id)
            ->update([
                'tanggal_sempro' => $request->tgl_ujian,
                'jam_ujian' => $request->jam_ujian,
                'ruang' => $request->ruang_ujian,
                'id_penguji1' => $request->id_penguji1,
                'id_penguji2' => $request->id_penguji2,
                'status' => 'disetujui',
                'updated_at' => now()
            ]);

        return response()->json(['success' => 'Jadwal Seminar Proposal berhasil diplot']);
    }

    public function plot_jadwal_ujian(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id_skripsi' => 'required',
            'tgl_ujian' => 'required',
            'id_penguji1' => 'required',
            'id_penguji2' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()], 422);
        }

        DB::table('akd_skripsi_ujian')
            ->where('id_skripsi', $request->id_skripsi)
            ->update([
                'tanggal_ujian' => $request->tgl_ujian,
                'jam_mulai' => $request->jam_ujian,
                'ruang' => $request->ruang_ujian,
                'id_penguji1' => $request->id_penguji1,
                'id_penguji2' => $request->id_penguji2,
                'id_penguji3' => $request->id_penguji3,
                'status' => 'disetujui',
                'updated_at' => now()
            ]);

        // After Kaprodi plots the exam schedule, set the main skripsi fase_aktif to 'ujian'
        DB::table('akd_skripsi')
            ->where('id', $request->id_skripsi)
            ->update([
                'fase_aktif' => 'ujian',
                'updated_at' => now()
            ]);

        return response()->json(['success' => 'Jadwal Ujian Akhir berhasil diplot']);
    }

    public function get_jadwal_ujian($id_skripsi)
    {
        $ujian = DB::table('akd_skripsi_ujian as u')
            ->leftJoin('simpeg_pegawai as peg1', 'u.id_penguji1', '=', 'peg1.id')
            ->leftJoin('simpeg_pegawai as peg2', 'u.id_penguji2', '=', 'peg2.id')
            ->leftJoin('simpeg_pegawai as peg3', 'u.id_penguji3', '=', 'peg3.id')
            ->select(
                'u.*',
                DB::raw("CONCAT_WS(' ', peg1.gelar_depan, peg1.nama, peg1.gelar_belakang) as nama_penguji1"),
                DB::raw("CONCAT_WS(' ', peg2.gelar_depan, peg2.nama, peg2.gelar_belakang) as nama_penguji2"),
                DB::raw("CONCAT_WS(' ', peg3.gelar_depan, peg3.nama, peg3.gelar_belakang) as nama_penguji3")
            )
            ->where('u.id_skripsi', $id_skripsi)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $ujian
        ]);
    }

    public function list_siap_sk(Request $request)
    {
        $kode_prodi = $request->kode_prodi;
        $angkatan = $request->angkatan;
        $tahun_aktif = $request->tahun;
        $semester_aktif = $request->semester == 1 ? 'Gasal' : 'Genap';

        $query = DB::table('akd_mahasiswa as m')
            ->join('akd_skripsi as s', 'm.nim', '=', 's.nim')
            ->join('akd_program_studi as ps', 'm.kode_program_studi', '=', 'ps.kode_program_studi')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->leftJoin('akd_skripsi_sk as sk', 's.id_sk_pembimbing', '=', 'sk.id')
            ->select(
                'm.nim',
                'm.nama_mahasiswa as nama_mhs',
                'm.tahun_angkatan',
                's.id as id_skripsi',
                's.judul',
                's.id_dosen_pembimbing1',
                's.id_dosen_pembimbing2',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_pembimbing1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_pembimbing2"),
                DB::raw("CASE WHEN s.id_sk_pembimbing IS NOT NULL THEN 'perpanjangan' ELSE 'baru' END as status_sk")
            )
            ->where(function ($q) use ($kode_prodi) {
                $q->where('m.kode_program_studi', $kode_prodi)
                    ->orWhere('ps.kode_fakultas', $kode_prodi);
            })
            ->whereNotNull('s.id_dosen_pembimbing1')
            ->where('s.status', '!=', 'lulus')
            ->where(function($q) use ($tahun_aktif, $semester_aktif) {
                $q->whereNull('s.id_sk_pembimbing')
                  ->orWhere(function($subq) use ($tahun_aktif, $semester_aktif) {
                      $subq->whereNotNull('s.id_sk_pembimbing')
                           ->where(function($qq) use ($tahun_aktif, $semester_aktif) {
                               $qq->where('sk.tahun_akademik', '!=', $tahun_aktif)
                                  ->orWhere('sk.semester', '!=', $semester_aktif);
                           });
                  });
            });

        if ($angkatan) {
            $query->where('m.tahun_angkatan', $angkatan);
        }

        $data = $query->get();

        return response()->json($data);
    }

    public function simpan_sk_kolektif(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'no_sk' => 'required',
            'no_surat_tugas' => 'required',
            'tgl_sk' => 'required',
            'id_skripsi' => 'required|array',
            'kode_prodi' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()], 422);
        }

        DB::beginTransaction();
        try {
            // Logika Increment Otomatis untuk Nomor Surat Tugas
            $baseNoST = $request->no_surat_tugas;
            $prefix = '';
            $currentNum = 0;
            $suffix = '';
            $padding = 0;
            $isIncrementable = false;

            // Regex: Mencari grup angka terakhir sebelum karakter non-angka/slash (seperti 4 di 11.4/ST)
            if (preg_match('/^(.*?)(\d+)(\/ST\/.*|$)/', $baseNoST, $matches)) {
                $prefix = $matches[1];
                $currentNum = (int)$matches[2];
                $suffix = $matches[3];
                $padding = strlen($matches[2]); // Menjaga format leading zero (misal 004 -> 005)
                $isIncrementable = true;
            }

            $id_sk = DB::table('akd_skripsi_sk')->insertGetId([
                'no_sk' => $request->no_sk,
                'no_surat_tugas' => $baseNoST, // Simpan nomor awal sebagai referensi batch
                'tgl_sk' => $request->tgl_sk,
                'kode_prodi' => $request->kode_prodi,
                'tahun_akademik' => $request->tahun_akademik,
                'semester' => $request->semester,
                'perihal' => $request->perihal ?? 'Pengangkatan Dosen Pembimbing Skripsi',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('akd_skripsi')
                ->whereIn('id', $request->id_skripsi)
                ->update([
                    'id_sk_pembimbing' => $id_sk,
                    'updated_at' => now()
                ]);

            // Save to detail history table
            $detail_records = [];
            foreach ($request->id_skripsi as $index => $id_skripsi) {
                $generatedNoST = $baseNoST;
                if ($isIncrementable) {
                    // Hitung nomor untuk mahasiswa ke-n
                    $nextNum = $currentNum + $index;
                    $formattedNum = str_pad($nextNum, $padding, '0', STR_PAD_LEFT);
                    $generatedNoST = $prefix . $formattedNum . $suffix;
                }

                $detail_records[] = [
                    'id_sk' => $id_sk,
                    'id_skripsi' => $id_skripsi,
                    'no_surat_tugas' => $generatedNoST, // Menyimpan nomor unik per mahasiswa
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            DB::table('akd_skripsi_sk_detail')->insert($detail_records);

            DB::commit();
            return response()->json(['success' => 'SK Kolektif berhasil diterbitkan untuk ' . count($request->id_skripsi) . ' mahasiswa.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan SK: ' . $e->getMessage()], 500);
        }
    }

    public function update_sk(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'id' => 'required',
            'no_sk' => 'required',
            'no_surat_tugas' => 'required',
            'tgl_sk' => 'required|date' // Menambahkan validasi untuk tgl_sk
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()], 422);
        }

        DB::beginTransaction();
        try {
            // Logika Increment untuk update (Sama seperti saat simpan baru)
            $baseNoST = $request->no_surat_tugas;
            $prefix = '';
            $currentNum = 0;
            $suffix = '';
            $padding = 0;
            $isIncrementable = false;

            if (preg_match('/^(.*?)(\d+)(\/ST\/.*|$)/', $baseNoST, $matches)) {
                $prefix = $matches[1];
                $currentNum = (int)$matches[2];
                $suffix = $matches[3];
                $padding = strlen($matches[2]);
                $isIncrementable = true;
            }

            DB::table('akd_skripsi_sk')
                ->where('id', $request->id)
                ->update([
                    'no_sk' => $request->no_sk,
                    'no_surat_tugas' => $baseNoST,
                    'tgl_sk' => $request->tgl_sk, // Menambahkan tgl_sk ke dalam data yang diperbarui
                    'updated_at' => now()
                ]);

            // Sinkronisasi nomor surat tugas individu di tabel detail
            $details = DB::table('akd_skripsi_sk_detail')->where('id_sk', $request->id)->orderBy('id', 'asc')->get();
            foreach ($details as $index => $det) {
                $generatedNoST = $baseNoST;
                if ($isIncrementable) {
                    $nextNum = $currentNum + $index;
                    $formattedNum = str_pad($nextNum, $padding, '0', STR_PAD_LEFT);
                    $generatedNoST = $prefix . $formattedNum . $suffix;
                }
                DB::table('akd_skripsi_sk_detail')->where('id', $det->id)->update(['no_surat_tugas' => $generatedNoST]);
            }

            DB::commit();
            return response()->json(['success' => 'Data SK dan Nomor Surat Tugas individu berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal memperbarui SK: ' . $e->getMessage()], 500);
        }
    }

    public function list_sk_terbit(Request $request)
    {
        $kode_prodi = $request->kode_prodi;

        $data = DB::table('akd_skripsi_sk as sk')
            ->leftJoin('akd_program_studi as ps', 'sk.kode_prodi', '=', 'ps.kode_program_studi')
            ->select('sk.*')
            ->where(function ($q) use ($kode_prodi) {
                $q->where('sk.kode_prodi', $kode_prodi)
                    ->orWhere('ps.kode_fakultas', $kode_prodi);
            })
            ->orderBy('sk.tgl_sk', 'desc')
            ->get();
        return response()->json($data);
    }

    public function get_sk_detail($id)
    {
        $sk = DB::table('akd_skripsi_sk as sk')
            ->leftJoin('akd_program_studi as ps', 'sk.kode_prodi', '=', 'ps.kode_program_studi')
            ->leftJoin('akd_fakultas as f', function ($join) {
                $join->on('ps.kode_fakultas', '=', 'f.kode_fakultas')
                    ->orOn('sk.kode_prodi', '=', 'f.kode_fakultas');
            })
            ->leftJoin('simpeg_pegawai as dekan', 'f.pimpinan', '=', 'dekan.id')
            ->select(
                'sk.*',
                'ps.nama_program_studi',
                'ps.kode_jenjang_pendidikan',
                'f.nama_fakultas',
                'dekan.nama as nama_dekan',
                'dekan.gelar_depan as gd_dekan',
                'dekan.gelar_belakang as gb_dekan',
                'dekan.nip as nip_dekan'
            )
            ->where('sk.id', $id)
            ->first();

        if (!$sk) {
            $all_data = DB::table('akd_skripsi_sk')->get();
            return response()->json([
                'error' => 'SK tidak ditemukan',
                'requested_id' => $id,
                'all_records' => $all_data
            ], 404);
        }

        $mahasiswa = DB::table('akd_skripsi_sk_detail as skd')
            ->join('akd_skripsi as s', 'skd.id_skripsi', '=', 's.id')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as ps_mhs', 'm.kode_program_studi', '=', 'ps_mhs.kode_program_studi')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->select(
                'm.nim',
                'm.nama_mahasiswa as nama_mhs',
                'ps_mhs.nama_program_studi',
                's.judul',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_p1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_p2"),
                'skd.no_surat_tugas as no_st_ind', // Ambil nomor individu
                'p1.id as nip_p1',
                'p2.id as nip_p2'
            )
            ->where('skd.id_sk', $id)
            ->get();

        if ($sk && !$sk->nama_program_studi && $mahasiswa->isNotEmpty()) {
            $sk->nama_program_studi = $mahasiswa->first()->nama_program_studi;
        }

        return response()->json([
            'sk' => $sk,
            'mahasiswa' => $mahasiswa
        ]);
    }

    /**
     * Kaprodi: Ambil daftar matakuliah skripsi beserta konfigurasi cpmk_based
     */
    public function get_grading_config($kode_prodi)
    {
        $matakuliah = DB::table('akd_matakuliah')
            ->where('kode_program_studi', $kode_prodi)
            ->where(function($q) {
                $q->where('nama_matakuliah', 'like', '%Skripsi%')
                  ->orWhere('nama_matakuliah', 'like', '%Tugas Akhir%')
                  ->orWhere('nama_matakuliah', 'like', '%Laporan Tugas Akhir%')
                  ->orWhere('nama_matakuliah', 'like', '%Seminar Proposal%')
                  ->orWhere('nama_matakuliah', 'like', '%PKL%')
                  ->orWhere('nama_matakuliah', 'like', '%Praktek Kerja%');
            })
            ->select('id_matakuliah', 'kode_matakuliah', 'nama_matakuliah', 'cpmk_based')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $matakuliah
        ]);
    }

    /**
     * Kaprodi: Simpan konfigurasi grading cpmk_based untuk matakuliah
     */
    public function update_grading_config(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_matakuliah' => 'required',
            'cpmk_based'    => 'required|in:0,1'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        DB::table('akd_matakuliah')
            ->where('id_matakuliah', $request->id_matakuliah)
            ->update([
                'cpmk_based' => $request->cpmk_based
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Metode penilaian mata kuliah berhasil diperbarui'
        ]);
    }

    /**
     * Konfigurasi Sempro Per Prodi
     */
    public function get_config_sempro($kode_prodi)
    {
        $prodi = DB::table('akd_program_studi')
            ->where('kode_program_studi', $kode_prodi)
            ->select('kode_program_studi', 'nama_program_studi', 'ta_sempro_skema')
            ->first();

        if (!$prodi) return response()->json(['error' => 'Prodi tidak ditemukan'], 404);

        $mapped_mk = DB::table('akd_skripsi_sempro_mk as m')
            ->join('akd_matakuliah as mk', 'm.id_matakuliah', '=', 'mk.id_matakuliah')
            ->where('m.kode_prodi', $kode_prodi)
            ->select('mk.id_matakuliah', 'mk.kode_matakuliah', 'mk.nama_matakuliah')
            ->get();

        return response()->json([
            'prodi' => $prodi,
            'mapped_matakuliah' => $mapped_mk
        ]);
    }

    public function update_config_sempro(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'ta_sempro_skema' => 'required|in:skripsi,matakuliah',
            'id_matakuliah' => 'nullable|array'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        DB::beginTransaction();
        try {
            // 1. Update Skema & Validation Status
            $updateData = ['ta_sempro_skema' => $request->ta_sempro_skema];
            if ($request->ta_sempro_skema == 'matakuliah') {
                $updateData['ta_sempro_is_validated'] = 0; // Requires admin validation
            } else {
                $updateData['ta_sempro_is_validated'] = 1; // Default skripsi doesn't need validation
            }

            DB::table('akd_program_studi')
                ->where('kode_program_studi', $request->kode_prodi)
                ->update($updateData);

            // 2. Sync Matakuliah if skema is matakuliah
            DB::table('akd_skripsi_sempro_mk')->where('kode_prodi', $request->kode_prodi)->delete();
            
            if ($request->ta_sempro_skema == 'matakuliah' && $request->id_matakuliah) {
                foreach ($request->id_matakuliah as $id_mk) {
                    DB::table('akd_skripsi_sempro_mk')->insert([
                        'kode_prodi' => $request->kode_prodi,
                        'id_matakuliah' => $id_mk,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => 'Konfigurasi Sempro berhasil diperbarui']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function search_matakuliah(Request $request)
    {
        $search = $request->search;
        $kode_prodi = $request->kode_prodi;

        $query = DB::table('akd_matakuliah')
            ->where('kode_program_studi', $kode_prodi)
            ->where(function($q) use ($search) {
                $q->where('nama_matakuliah', 'like', "%$search%")
                  ->orWhere('kode_matakuliah', 'like', "%$search%");
            })
            ->limit(20)
            ->get();

        return response()->json($query);
    }

    public function list_config_sempro()
    {
        $list = DB::table('akd_program_studi as ps')
            ->select('ps.kode_program_studi', 'ps.nama_program_studi', 'ps.ta_sempro_skema', 'ps.ta_sempro_is_validated')
            ->get();

        $data = [];
        foreach ($list as $item) {
            $mapped_mks = DB::table('akd_skripsi_sempro_mk as m')
                ->join('akd_matakuliah as mk', 'm.id_matakuliah', '=', 'mk.id_matakuliah')
                ->where('m.kode_prodi', $item->kode_program_studi)
                ->select('mk.kode_matakuliah', 'mk.nama_matakuliah')
                ->get();

            $data[] = [
                'kode_program_studi' => $item->kode_program_studi,
                'nama_program_studi' => $item->nama_program_studi,
                'ta_sempro_skema' => $item->ta_sempro_skema,
                'ta_sempro_is_validated' => $item->ta_sempro_is_validated ?? 1,
                'mapped_matakuliah' => $mapped_mks
            ];
        }

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function validate_config_sempro(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'status' => 'required|in:0,1'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        DB::table('akd_program_studi')
            ->where('kode_program_studi', $request->kode_prodi)
            ->update([
                'ta_sempro_is_validated' => $request->status,
                'updated_at' => now()
            ]);

        $message = $request->status == 1 ? 'Konfigurasi Sempro berhasil disetujui (Aktif)' : 'Konfigurasi Sempro ditolak (Pending)';

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    /**
     * Get Indikator Rubrics for Kaprodi Config
     */
    public function get_rubrik_indikator($kode_prodi, Request $request)
    {
        $jalur = $request->query('jalur', 'reguler');
        $rows = DB::table('akd_skripsi_rubrik_indikator')
            ->where('kode_prodi', $kode_prodi)
            ->where('jalur', $jalur)
            ->orderBy('kode_indikator', 'asc')
            ->get();

        // If empty, fetch default ones (where kode_prodi is null)
        if ($rows->count() == 0) {
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
     * Save Indikator Rubrics Config for Kaprodi
     */
    public function save_rubrik_indikator(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'jalur' => 'required|in:reguler,obe',
            'tipe_bobot' => 'required|in:tunggal,indikator',
            'rubrik' => 'required|array', // array of { id, kode_indikator, nama_indikator, bobot, kkm, aspek }
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $kode_prodi = $request->kode_prodi;
        $jalur = $request->jalur;
        $tipe_bobot = $request->tipe_bobot;

        // Validasi bobot jika tipe_bobot adalah 'indikator'
        if ($tipe_bobot === 'indikator') {
            // Fetch dynamic aspects
            $aspects = DB::table('akd_skripsi_aspek')
                ->where('kode_prodi', $kode_prodi)
                ->where('jalur', $jalur)
                ->get();

            if ($aspects->count() == 0) {
                // Default fallback if not found
                $aspects = collect([
                    (object)['nama_aspek' => 'Substansi dan Luaran', 'bobot' => 60.00],
                    (object)['nama_aspek' => 'Ujian / Presentasi', 'bobot' => 40.00]
                ]);
            }

            $aspectTotals = [];
            foreach ($aspects as $a) {
                $aspectTotals[$a->nama_aspek] = 0;
            }

            $rubrik = $request->rubrik;

            foreach ($rubrik as &$r) {
                $aspek = trim($r['aspek'] ?? '');
                $bobot = floatval($r['bobot'] ?? 0);
                
                $matchedAspect = null;
                foreach ($aspects as $a) {
                    $dbAspekLower = strtolower(trim($aspek));
                    $aNameLower = strtolower(trim($a->nama_aspek));
                    if ($dbAspekLower === $aNameLower) {
                        $matchedAspect = $a;
                        break;
                    } elseif ($dbAspekLower === 'substansi' && strpos($aNameLower, 'substansi') !== false) {
                        $matchedAspect = $a;
                        break;
                    } elseif ($dbAspekLower === 'ujian' && strpos($aNameLower, 'ujian') !== false) {
                        $matchedAspect = $a;
                        break;
                    }
                }

                if ($matchedAspect) {
                    $r['aspek'] = $matchedAspect->nama_aspek;
                    $aspectTotals[$matchedAspect->nama_aspek] += $bobot;
                } else {
                    return response()->json(['error' => 'Aspek "' . $aspek . '" tidak terdaftar di Master Aspek Penilaian.'], 422);
                }
            }
            unset($r);

            foreach ($aspects as $a) {
                $expected = floatval($a->bobot);
                $actual = $aspectTotals[$a->nama_aspek];
                if (abs($actual - $expected) > 0.01) {
                    return response()->json(['error' => 'Total bobot indikator aspek "' . $a->nama_aspek . '" harus tepat ' . $expected . '% (saat ini: ' . $actual . '%)'], 422);
                }
            }
        } else {
            $rubrik = $request->rubrik;
        }

        DB::beginTransaction();
        try {
            // Hapus rubrik kustom yang lama untuk prodi & jalur ini
            DB::table('akd_skripsi_rubrik_indikator')
                ->where('kode_prodi', $kode_prodi)
                ->where('jalur', $jalur)
                ->delete();

            // Insert new custom rubrics
            $now = now();
            foreach ($rubrik as $r) {
                DB::table('akd_skripsi_rubrik_indikator')->insert([
                    'kode_indikator' => $r['kode_indikator'],
                    'nama_indikator' => $r['nama_indikator'],
                    'bobot' => floatval($r['bobot']),
                    'kkm' => 70.00,
                    'aspek' => $r['aspek'],
                    'jalur' => $jalur,
                    'tipe_bobot' => $tipe_bobot,
                    'kode_prodi' => $kode_prodi,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }

            DB::commit();
            return response()->json(['success' => 'Rubrik penilaian berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset/Delete Indikator Rubrics for Kaprodi
     */
    public function reset_rubrik_indikator(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'jalur' => 'required|in:reguler,obe',
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $kode_prodi = $request->kode_prodi;
        $jalur = $request->jalur;

        DB::beginTransaction();
        try {
            DB::table('akd_skripsi_rubrik_indikator')
                ->where('kode_prodi', $kode_prodi)
                ->where('jalur', $jalur)
                ->delete();

            DB::commit();
            return response()->json(['success' => 'Rubrik penilaian berhasil direset ke default']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Master Data Aspek - Get Aspek for Kaprodi
     */
    public function get_aspek($kode_prodi, Request $request)
    {
        $jalur = $request->query('jalur', 'reguler');
        $aspeks = DB::table('akd_skripsi_aspek')
            ->where('kode_prodi', $kode_prodi)
            ->where('jalur', $jalur)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $aspeks
        ]);
    }

    /**
     * Master Data Aspek - Save/Update Aspek
     */
    public function save_aspek(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'nama_aspek' => 'required|max:100',
            'bobot' => 'required|numeric|min:0|max:100',
            'jalur' => 'required|in:reguler,obe',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()->all()], 422);
        }

        $kode_prodi = $request->kode_prodi;
        $nama_aspek = trim($request->nama_aspek);
        $bobot = floatval($request->bobot);
        $jalur = $request->jalur;

        // Validasi total bobot tidak melebihi 100% jika ditambah aspek ini (untuk aspek baru)
        $id = $request->id;
        $query = DB::table('akd_skripsi_aspek')
            ->where('kode_prodi', $kode_prodi)
            ->where('jalur', $jalur);
        if ($id) {
            $query->where('id', '!=', $id);
        }
        $existing_total = $query->sum('bobot');
        if ($existing_total + $bobot > 100.01) {
            return response()->json(['error' => 'Total bobot aspek melebihi 100% (Maksimum yang tersisa: ' . (100 - $existing_total) . '%)'], 422);
        }

        $data = [
            'kode_prodi' => $kode_prodi,
            'nama_aspek' => $nama_aspek,
            'bobot' => $bobot,
            'jalur' => $jalur,
            'updated_at' => now()
        ];

        if ($id) {
            // Get original aspect name
            $original_name = DB::table('akd_skripsi_aspek')->where('id', $id)->value('nama_aspek');

            // Update
            DB::table('akd_skripsi_aspek')
                ->where('id', $id)
                ->update($data);

            // Update aspects in rubrik if renamed
            if ($original_name && $original_name !== $nama_aspek) {
                DB::table('akd_skripsi_rubrik_indikator')
                    ->where('kode_prodi', $kode_prodi)
                    ->where('jalur', $jalur)
                    ->where('aspek', $original_name)
                    ->update(['aspek' => $nama_aspek]);
            }

            $msg = 'Aspek penilaian berhasil diperbarui.';
        } else {
            $data['created_at'] = now();
            DB::table('akd_skripsi_aspek')->insert($data);
            $msg = 'Aspek penilaian berhasil ditambahkan.';
        }

        return response()->json([
            'status' => 'success',
            'message' => $msg
        ]);
    }

    /**
     * Master Data Aspek - Delete Aspek
     */
    public function delete_aspek($id, Request $request)
    {
        $aspek = DB::table('akd_skripsi_aspek')->where('id', $id)->first();
        if (!$aspek) {
            return response()->json(['error' => 'Aspek tidak ditemukan.'], 404);
        }

        // Check if there are indicators using this aspect
        $hasIndicators = DB::table('akd_skripsi_rubrik_indikator')
            ->where('kode_prodi', $aspek->kode_prodi)
            ->where('jalur', $aspek->jalur)
            ->where('aspek', $aspek->nama_aspek)
            ->exists();

        if ($hasIndicators) {
            return response()->json(['error' => 'Aspek "' . $aspek->nama_aspek . '" tidak dapat dihapus karena masih digunakan oleh indikator penilaian.'], 422);
        }

        DB::table('akd_skripsi_aspek')->where('id', $id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Aspek penilaian berhasil dihapus.'
        ]);
    }

    /**
     * Master Data Aspek - Reset Aspek to Default
     */
    public function reset_aspek(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'jalur' => 'required|in:reguler,obe',
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $kode_prodi = $request->kode_prodi;
        $jalur = $request->jalur;

        DB::beginTransaction();
        try {
            DB::table('akd_skripsi_aspek')
                ->where('kode_prodi', $kode_prodi)
                ->where('jalur', $jalur)
                ->delete();

            // Insert defaults
            DB::table('akd_skripsi_aspek')->insert([
                [
                    'kode_prodi' => $kode_prodi,
                    'nama_aspek' => 'Substansi dan Luaran',
                    'bobot' => 60.00,
                    'jalur' => $jalur,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'kode_prodi' => $kode_prodi,
                    'nama_aspek' => 'Ujian / Presentasi',
                    'bobot' => 40.00,
                    'jalur' => $jalur,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Aspek penilaian berhasil direset ke default.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get requirements config for a prodi
     */
    public function list_syarat_prodi($kode_prodi)
    {
        $data = DB::table('akd_skripsi_syarat_prodi as p')
            ->join('akd_skripsi_syarat as s', 'p.kode_syarat', '=', 's.kode_syarat')
            ->select('p.*', 's.nama_syarat', 's.jenis')
            ->where('p.kode_prodi', $kode_prodi)
            ->orderBy('p.fase', 'asc')
            ->orderBy('p.urutan', 'asc')
            ->get();

        return response()->json($data);
    }

    /**
     * Get all active master requirements
     */
    public function list_master_syarat()
    {
        $data = DB::table('akd_skripsi_syarat')
            ->where('is_aktif', 1)
            ->orderBy('nama_syarat', 'asc')
            ->get();

        return response()->json($data);
    }

    /**
     * Save/Update a requirement mapping
     */
    public function save_syarat_prodi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'kode_jenjang' => 'required|in:S1,D4,D3',
            'fase' => 'required|in:sempro,ujian',
            'kode_syarat' => 'required',
            'operator' => 'required|in:>=,<=,=,EXISTS,-',
            'nilai_target' => 'nullable',
            'petugas_validasi' => 'required',
            'tipe_upload' => 'required|in:file,url,bebas',
            'keterangan' => 'nullable',
            'urutan' => 'required|integer',
            'is_wajib' => 'required|in:0,1',
            'is_aktif' => 'required|in:0,1'
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()->all()], 422);
        }

        $data = [
            'kode_prodi' => $request->kode_prodi,
            'kode_jenjang' => $request->kode_jenjang,
            'fase' => $request->fase,
            'kode_syarat' => $request->kode_syarat,
            'operator' => $request->operator,
            'nilai_target' => $request->nilai_target,
            'petugas_validasi' => $request->petugas_validasi,
            'tipe_upload' => $request->tipe_upload,
            'keterangan' => $request->keterangan,
            'urutan' => $request->urutan,
            'is_wajib' => $request->is_wajib,
            'is_aktif' => $request->is_aktif
        ];

        if ($request->has('id') && !empty($request->id)) {
            DB::table('akd_skripsi_syarat_prodi')
                ->where('id', $request->id)
                ->update($data);
            $msg = 'Syarat berhasil diperbarui.';
        } else {
            DB::table('akd_skripsi_syarat_prodi')->insert($data);
            $msg = 'Syarat berhasil ditambahkan.';
        }

        return response()->json(['success' => $msg]);
    }

    /**
     * Delete a requirement mapping
     */
    public function delete_syarat_prodi($id)
    {
        DB::table('akd_skripsi_syarat_prodi')->where('id', $id)->delete();
        return response()->json(['success' => 'Syarat berhasil dihapus.']);
    }

    /**
     * List mahasiswa yang siap ditetapkan nilainya oleh Kaprodi
     */
    public function list_penetapan_nilai(Request $request)
    {
        $kode_prodi = $request->kode_prodi;
        $kode_fakultas = $request->kode_fakultas;

        $query = DB::table('akd_skripsi_ujian as u')
            ->join('akd_skripsi as s', 'u.id_skripsi', '=', 's.id')
            ->join('akd_mahasiswa as m', 'u.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as prodi', 'm.kode_program_studi', '=', 'prodi.kode_program_studi')
            ->leftJoin('akd_skripsi_berita_acara as ba', 'u.id', '=', 'ba.id_skripsi_ujian')
            ->leftJoin('simpeg_pegawai as p1', 'u.id_penguji1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 'u.id_penguji2', '=', 'p2.id')
            ->leftJoin('simpeg_pegawai as p3', 'u.id_penguji3', '=', 'p3.id')
            ->select(
                'u.id as id_skripsi_ujian',
                'u.id_skripsi',
                'u.nim',
                'm.nama_mahasiswa as nama_mhs',
                's.judul',
                's.target_luaran',
                DB::raw("CASE WHEN s.target_luaran IS NOT NULL AND s.target_luaran != 'buku_skripsi' THEN 1 ELSE 0 END as is_obe"),
                DB::raw("CASE WHEN s.target_luaran IS NOT NULL AND s.target_luaran != 'buku_skripsi' THEN 1 ELSE 0 END as cpmk_based"),
                'u.tanggal_ujian',
                'u.status as status_ujian',
                'ba.nilai_angka',
                'ba.nilai_huruf',
                'ba.status as status_ba',
                'ba.nomor_ba',
                'ba.batas_revisi',
                'ba.setuju_penguji1',
                'ba.setuju_penguji2',
                'ba.setuju_penguji3',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_penguji1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_penguji2"),
                DB::raw("CONCAT_WS(' ', p3.gelar_depan, p3.nama, p3.gelar_belakang) as nama_penguji3"),
                'u.id_penguji1',
                'u.id_penguji2',
                'u.id_penguji3'
            );

        if ($kode_prodi) {
            $query->where('m.kode_program_studi', $kode_prodi);
        }

        if ($kode_fakultas) {
            $query->where('prodi.kode_fakultas', $kode_fakultas);
        }

        $data = $query
            ->whereNotNull('u.id_penguji1') // hanya yang sudah diploting (ada penguji)
            ->orderByRaw('ISNULL(u.tanggal_ujian) ASC')
            ->orderBy('u.tanggal_ujian', 'desc')
            ->get();

        return response()->json($data);
    }

    /**
     * Menetapkan nilai secara resmi oleh Kaprodi
     */
    public function tetapkan_nilai(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_skripsi_ujian' => 'required',
            'status'           => 'nullable|in:lulus,tidak_lulus',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()->all()], 422);
        }

        $id_skripsi_ujian = $request->id_skripsi_ujian;

        $ujian = DB::table('akd_skripsi_ujian')->where('id', $id_skripsi_ujian)->first();
        if (!$ujian) {
            return response()->json(['error' => 'Data ujian tidak ditemukan.'], 404);
        }

        $ba = DB::table('akd_skripsi_berita_acara')->where('id_skripsi_ujian', $id_skripsi_ujian)->first();
        if (!$ba) {
            return response()->json(['error' => 'Berita Acara belum dibuat/nilai belum lengkap.'], 404);
        }

        // Ketiga penguji wajib ttd digital (tanggal tidak null)
        if (!$ba->setuju_penguji1 || !$ba->setuju_penguji2 || !$ba->setuju_penguji3) {
            return response()->json(['error' => 'Berita Acara belum ditandatangani digital oleh ketiga penguji.'], 400);
        }

        $status = $request->status;
        if (!$status) {
            if ($ba->keputusan && str_contains($ba->keputusan, 'tidak_lulus')) {
                $status = 'tidak_lulus';
            } else {
                $status = 'lulus';
            }
        }

        DB::beginTransaction();
        try {
            // Update status ujian
            DB::table('akd_skripsi_ujian')
                ->where('id', $id_skripsi_ujian)
                ->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);

            // Update status skripsi induk
            DB::table('akd_skripsi')
                ->where('id', $ujian->id_skripsi)
                ->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);

            // Sync ke akd_transkrip & akd_detail_krs
            $mhs = DB::table('akd_mahasiswa')->where('nim', $ujian->nim)->first();
            if ($mhs) {
                $mk = DB::table('akd_matakuliah')
                    ->where('kode_program_studi', $mhs->kode_program_studi)
                    ->where(function($q) {
                        $q->where('nama_matakuliah', 'like', '%skripsi%')
                          ->orWhere('nama_matakuliah', 'like', '%tugas akhir%');
                    })
                    ->where('nama_matakuliah', 'not like', '%proposal%')
                    ->orderByRaw("CASE WHEN tahun_kurikulum = '{$mhs->tahun_kurikulum}' THEN 1 ELSE 2 END")
                    ->first();

                if ($mk) {
                    $id_matakuliah = $mk->id_matakuliah;
                    $tahun_kurikulum = $mk->tahun_kurikulum;

                    // 1. Sync ke akd_transkrip
                    $cek_nilai = DB::table('akd_transkrip')
                        ->where('nim', $ujian->nim)
                        ->where('id_matakuliah', $id_matakuliah)
                        ->first();

                    if ($cek_nilai) {
                        DB::table('akd_transkrip')
                            ->where('id_transkrip', $cek_nilai->id_transkrip)
                            ->update([
                                'nilai' => $ba->nilai_huruf,
                                'updated_at' => now()
                            ]);
                    } else {
                        DB::table('akd_transkrip')->insert([
                            'nim' => $ujian->nim,
                            'id_matakuliah' => $id_matakuliah,
                            'tahun_kurikulum' => $tahun_kurikulum,
                            'nilai' => $ba->nilai_huruf,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    // 2. Sync ke akd_detail_krs (KRS semesteran)
                    $detail_ids = DB::table('akd_detail_krs as dk')
                        ->join('akd_krs as k', 'dk.id_krs', '=', 'k.id_krs')
                        ->join('akd_heregistrasi as h', 'k.id_heregistrasi', '=', 'h.id_heregistrasi')
                        ->join('akd_kelas_kuliah as kk', 'dk.id_kelas', '=', 'kk.id_kelas')
                        ->join('akd_penawaran_matakuliah as pm', 'kk.id_tawar', '=', 'pm.id_tawar')
                        ->where('h.nim', $ujian->nim)
                        ->where('pm.id_matakuliah', $id_matakuliah)
                        ->pluck('dk.id_detail_krs');

                    if ($detail_ids->isNotEmpty()) {
                        DB::table('akd_detail_krs')
                            ->whereIn('id_detail_krs', $detail_ids)
                            ->update([
                                'nilai_akhir_angka' => $ba->nilai_angka,
                                'nilai_akhir_huruf' => $ba->nilai_huruf,
                                'dtime_update'      => now()
                            ]);
                    }
                }
            }

            // Notify Dekan / Dekanat about the final score assignment by Kaprodi
            $dekan = DB::table('akd_mahasiswa as m')
                ->join('akd_program_studi as prodi', 'm.kode_program_studi', '=', 'prodi.kode_program_studi')
                ->join('akd_fakultas as fak', 'prodi.kode_fakultas', '=', 'fak.kode_fakultas')
                ->leftJoin('simpeg_pegawai as dekan_peg', 'fak.pimpinan', '=', 'dekan_peg.id')
                ->where('m.nim', $ujian->nim)
                ->select('dekan_peg.email_umuka', 'dekan_peg.nidn')
                ->first();

            if ($dekan) {
                $dekanUser = $dekan->email_umuka ?: $dekan->nidn;
                if ($dekanUser) {
                    \App\Helpers\NotificationHelper::send(
                        $dekanUser,
                        'Penetapan Nilai Skripsi & Berita Acara',
                        "Nilai kelulusan ujian skripsi mahasiswa {$ujian->nim} telah ditetapkan oleh Kaprodi. Silakan lakukan validasi Berita Acara.",
                        '/dekanat/skripsi/penetapan-skripsi',
                        'skripsi'
                    );
                }
            }

            // Notify all Dekanat accounts (Pegawai module logins matching the student's faculty)
            $dekanatUsers = DB::table('akd_mahasiswa as m')
                ->join('akd_program_studi as prodi', 'm.kode_program_studi', '=', 'prodi.kode_program_studi')
                ->join('user as u', 'prodi.kode_fakultas', '=', 'u.kode_fakultas')
                ->where('m.nim', $ujian->nim)
                ->where('u.kode_group', 4) // group 4 = Dekanat
                ->select('u.username')
                ->get();

            foreach ($dekanatUsers as $du) {
                \App\Helpers\NotificationHelper::send(
                    $du->username,
                    'Penetapan Nilai Skripsi & Berita Acara',
                    "Nilai kelulusan ujian skripsi mahasiswa {$ujian->nim} telah ditetapkan oleh Kaprodi. Silakan lakukan validasi Berita Acara.",
                    '/dekanat/skripsi/penetapan-skripsi',
                    'skripsi'
                );
            }

            DB::commit();
            return response()->json(['success' => 'Persetujuan Penetapan Nilai berhasil disimpan. Nilai telah disinkronisasi ke transkrip mahasiswa.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal melakukan penetapan nilai: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Kaprodi: List rekap bimbingan mahasiswa per prodi
     */
    public function list_bimbingan_prodi(Request $request)
    {
        $kode_prodi = $request->kode_prodi;
        if (!$kode_prodi) return response()->json(['error' => 'Parameter prodi diperlukan'], 400);

        $rows = DB::table('akd_skripsi as s')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->leftJoin('simpeg_pegawai as d', 's.id_dosen_pembimbing1', '=', 'd.id')
            ->select(
                's.id', 's.nim', 'm.nama_mahasiswa', 'p.nama_program_studi as prodi', 's.valid_id_kaprodi',
                DB::raw("TRIM(CONCAT_WS(' ', d.gelar_depan, d.nama, d.gelar_belakang)) as pembimbing"),
                DB::raw("(CASE WHEN s.fase_aktif = 'ujian' THEN 8 ELSE COALESCE(p.ta_minimal_bimbingan, 8) END) as min_bimbingan"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status = 'disetujui') as total_waiting_kaprodi"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status = 'disetujui_kaprodi') as total_waiting_dekan"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status = 'disetujui_dekan') as total_disetujui_dekan"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status IN ('disetujui', 'disetujui_kaprodi', 'disetujui_dekan')) as total_approved")
            )
            ->where('m.kode_program_studi', $kode_prodi)
            ->orderBy('m.nama_mahasiswa', 'asc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    /**
     * Kaprodi: Approve bimbingan (individual or bulk for a student)
     */
    public function approve_bimbingan_prodi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_log' => 'nullable|integer',
            'id_skripsi' => 'nullable|integer'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $id_log = $request->id_log;
        $id_skripsi = $request->id_skripsi;

        if (!$id_log && !$id_skripsi) {
            return response()->json(['error' => 'ID Log atau ID Skripsi harus diisi'], 400);
        }

        DB::beginTransaction();
        try {
            $query = DB::table('akd_skripsi_bimbingan')->where('status', 'disetujui');
            if ($id_log) {
                $query->where('id', $id_log);
                $log_row = DB::table('akd_skripsi_bimbingan')->where('id', $id_log)->first();
                if ($log_row) {
                    $id_skripsi = $log_row->id_skripsi;
                }
            } else {
                $query->where('id_skripsi', $id_skripsi);
            }

            $affected = $query->update([
                'status' => 'disetujui_kaprodi',
                'updated_at' => now()
            ]);

            if ($id_skripsi) {
                $skripsi = DB::table('akd_skripsi')->where('id', $id_skripsi)->first();
                if ($skripsi && empty($skripsi->valid_id_kaprodi)) {
                    DB::table('akd_skripsi')
                        ->where('id', $id_skripsi)
                        ->update([
                            'valid_id_kaprodi' => uniqid('kaprodi_', true),
                            'updated_at' => now()
                        ]);
                }
            }

            DB::commit();
            return response()->json(['success' => "$affected Log bimbingan berhasil disetujui Kaprodi"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyetujui bimbingan: ' . $e->getMessage()], 500);
        }
    }

    private function checkSemproByMataKuliahLulus($nim, $kode_prodi)
    {
        $mappedMk = DB::table('akd_skripsi_sempro_mk')
            ->where('kode_prodi', $kode_prodi)
            ->pluck('id_matakuliah')
            ->toArray();

        if (empty($mappedMk)) {
            return $this->checkSemproGrade($nim);
        }

        $has_grade = DB::table('akd_transkrip as t')
            ->where('t.nim', $nim)
            ->whereIn('t.id_matakuliah', $mappedMk)
            ->whereNotIn('t.nilai', ['D', 'E'])
            ->count() > 0;

        return $has_grade;
    }

    private function checkSemproGrade($nim)
    {
        $has_grade = DB::table('akd_transkrip as t')
            ->join('akd_matakuliah as mk', 't.id_matakuliah', '=', 'mk.id_matakuliah')
            ->where('t.nim', $nim)
            ->where(function($query) {
                $query->where('mk.nama_matakuliah', 'like', '%seminar proposal%')
                      ->orWhere('mk.nama_matakuliah', 'like', '%sempro%')
                      ->orWhere('mk.nama_matakuliah', 'like', '%proposal%');
            })
            ->whereNotIn('t.nilai', ['D', 'E'])
            ->count() > 0;

        return $has_grade;
    }
}




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

        DB::table('akd_skripsi')
            ->where('id', $request->id_skripsi)
            ->update([
                'id_dosen_pembimbing1' => $request->id_dosen_pembimbing1,
                'id_dosen_pembimbing2' => $request->id_dosen_pembimbing2,
                'status' => 'aktif',
                'fase_aktif' => 'bimbingan',
                'updated_at' => now()
            ]);

        return response()->json(['success' => 'Ploting pembimbing berhasil disimpan dan status skripsi diaktifkan.']);
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
            return response()->json(['error' => 'Data proposal tidak ditemukan'], 404);
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
     * Get CPMK Rubrics for Kaprodi Config
     */
    public function get_rubrik_cpmk($kode_prodi)
    {
        $rows = DB::table('akd_skripsi_rubrik_cpmk')
            ->where('kode_prodi', $kode_prodi)
            ->orderBy('kode_cpmk', 'asc')
            ->get();

        // If empty, fetch default ones (where kode_prodi is null)
        if ($rows->count() == 0) {
            $rows = DB::table('akd_skripsi_rubrik_cpmk')
                ->whereNull('kode_prodi')
                ->orderBy('kode_cpmk', 'asc')
                ->get();
        }

        // Include mapped CPLs for each CPMK
        foreach ($rows as $r) {
            $cpl = DB::table('akd_skripsi_cpmk_cpl')
                ->where('id_cpmk', $r->id)
                ->first();
            $r->kode_cpl = $cpl ? $cpl->kode_cpl : '';
        }

        return response()->json([
            'status' => 'success',
            'data' => $rows
        ]);
    }

    /**
     * Save CPMK Rubrics Config for Kaprodi
     */
    public function save_rubrik_cpmk(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
            'rubrik' => 'required|array', // array of { id_cpmk/new, kode_cpmk, nama_cpmk, bobot, kode_cpl }
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $kode_prodi = $request->kode_prodi;
        $total_bobot = 0;
        foreach ($request->rubrik as $r) {
            $total_bobot += floatval($r['bobot'] ?? 0);
        }

        if (abs($total_bobot - 100.00) > 0.01) {
            return response()->json(['error' => 'Total bobot rubrik harus tepat 100% (saat ini: ' . $total_bobot . '%)'], 422);
        }

        DB::beginTransaction();
        try {
            // Drop existing custom rubrics & CPL maps for this prodi
            $old_rubrics = DB::table('akd_skripsi_rubrik_cpmk')->where('kode_prodi', $kode_prodi)->get();
            $old_ids = $old_rubrics->pluck('id')->toArray();
            
            DB::table('akd_skripsi_cpmk_cpl')->whereIn('id_cpmk', $old_ids)->delete();
            DB::table('akd_skripsi_rubrik_cpmk')->where('kode_prodi', $kode_prodi)->delete();

            // Insert new custom rubrics
            $now = now();
            foreach ($request->rubrik as $r) {
                $id = DB::table('akd_skripsi_rubrik_cpmk')->insertGetId([
                    'kode_cpmk' => $r['kode_cpmk'],
                    'nama_cpmk' => $r['nama_cpmk'],
                    'bobot' => floatval($r['bobot']),
                    'kode_prodi' => $kode_prodi,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                if (!empty($r['kode_cpl'])) {
                    DB::table('akd_skripsi_cpmk_cpl')->insert([
                        'id_cpmk' => $id,
                        'kode_cpl' => $r['kode_cpl'],
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => 'Rubrik CPMK berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reset/Delete CPMK Rubrics for Kaprodi
     */
    public function reset_rubrik_cpmk(Request $request)
    {
        $v = Validator::make($request->all(), [
            'kode_prodi' => 'required',
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $kode_prodi = $request->kode_prodi;

        DB::beginTransaction();
        try {
            $old_rubrics = DB::table('akd_skripsi_rubrik_cpmk')->where('kode_prodi', $kode_prodi)->get();
            $old_ids = $old_rubrics->pluck('id')->toArray();
            
            if (!empty($old_ids)) {
                DB::table('akd_skripsi_cpmk_cpl')->whereIn('id_cpmk', $old_ids)->delete();
            }
            DB::table('akd_skripsi_rubrik_cpmk')->where('kode_prodi', $kode_prodi)->delete();

            DB::commit();
            return response()->json(['success' => 'Rubrik CPMK berhasil direset ke default']);
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
}


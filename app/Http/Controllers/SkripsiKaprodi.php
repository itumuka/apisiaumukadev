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
                'tgl_ujian' => $request->tgl_ujian,
                'jam_ujian' => $request->jam_ujian,
                'ruang_ujian' => $request->ruang_ujian,
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

     public function list_siap_sk(Request $request)
    {
        $kode_prodi = $request->kode_prodi;
        $angkatan = $request->angkatan;
        $query = DB::table('akd_mahasiswa as m')
            ->join('akd_skripsi as s', 'm.nim', '=', 's.nim')
            ->join('akd_program_studi as ps', 'm.kode_program_studi', '=', 'ps.kode_program_studi')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->select(
                'm.nim',
                'm.nama_mahasiswa as nama_mhs',
                'm.tahun_angkatan',
                's.id as id_skripsi',
                's.judul',
                's.id_dosen_pembimbing1',
                's.id_dosen_pembimbing2',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_pembimbing1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_pembimbing2")
            )
            ->where(function ($q) use ($kode_prodi) {
                $q->where('m.kode_program_studi', $kode_prodi)
                    ->orWhere('ps.kode_fakultas', $kode_prodi);
            })
            ->whereNotNull('s.id_dosen_pembimbing1')
            ->whereNull('s.id_sk_pembimbing');

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
            $id_sk = DB::table('akd_skripsi_sk')->insertGetId([
                'no_sk' => $request->no_sk,
                'no_surat_tugas' => $request->no_surat_tugas,
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
            'no_surat_tugas' => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(['error' => $validation->errors()->all()], 422);
        }

        try {
            DB::table('akd_skripsi_sk')
                ->where('id', $request->id)
                ->update([
                    'no_sk' => $request->no_sk,
                    'no_surat_tugas' => $request->no_surat_tugas,
                    'updated_at' => now()
                ]);

            return response()->json(['success' => 'Data SK berhasil diperbarui.']);
        } catch (\Exception $e) {
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

        $mahasiswa = DB::table('akd_skripsi as s')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as ps_mhs', 'm.kode_program_studi', '=', 'ps_mhs.kode_program_studi')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->select(
                'm.nim',
                'm.nama_mahasiswa as nama_mhs',
                'ps_mhs.nama_program_studi as nama_program_studi',
                's.judul',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_p1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_p2"),
                'p1.id as nip_p1',
                'p2.id as nip_p2'
            )
            ->where('s.id_sk_pembimbing', $id)
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
            // 1. Update Skema
            DB::table('akd_program_studi')
                ->where('kode_program_studi', $request->kode_prodi)
                ->update(['ta_sempro_skema' => $request->ta_sempro_skema]);

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
}
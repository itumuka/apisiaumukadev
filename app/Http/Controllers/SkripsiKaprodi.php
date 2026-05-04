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

        return response()->json(['success' => 'Jadwal Ujian Akhir berhasil diplot']);
    }

    public function list_siap_sk(Request $request)
    {
        $kode_prodi = $request->kode_prodi;

        $data = DB::table('akd_mahasiswa as m')
            ->join('akd_skripsi as s', 'm.nim', '=', 's.nim')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->select(
                'm.nim',
                'm.nama_mahasiswa as nama_mhs',
                's.id as id_skripsi',
                's.judul',
                's.id_dosen_pembimbing1',
                's.id_dosen_pembimbing2',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_pembimbing1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_pembimbing2")
            )
            ->where('m.kode_program_studi', $kode_prodi)
            ->whereNotNull('s.id_dosen_pembimbing1')
            ->whereNull('s.id_sk_pembimbing')
            ->get();

        return response()->json($data);
    }

    public function simpan_sk_kolektif(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'no_sk' => 'required',
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

    public function list_sk_terbit(Request $request)
    {
        $data = DB::table('akd_skripsi_sk')
            ->where('kode_prodi', $request->kode_prodi)
            ->orderBy('tgl_sk', 'desc')
            ->get();
        return response()->json($data);
    }

    public function get_sk_detail($id)
    {
        $sk = DB::table('akd_skripsi_sk as sk')
            ->join('akd_program_studi as ps', 'sk.kode_prodi', '=', 'ps.kode_program_studi')
            ->join('akd_fakultas as f', 'ps.kode_fakultas', '=', 'f.kode_fakultas')
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

        if (!$sk)
            return response()->json(['error' => 'SK tidak ditemukan'], 404);

        $mahasiswa = DB::table('akd_skripsi as s')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->select(
                'm.nim',
                'm.nama as nama_mhs',
                's.judul',
                DB::raw("CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang) as nama_p1"),
                DB::raw("CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang) as nama_p2"),
                'p1.id_pegawai as nip_p1',
                'p2.id_pegawai as nip_p2'
            )
            ->where('s.id_sk_pembimbing', $id)
            ->get();

        return response()->json([
            'sk' => $sk,
            'mahasiswa' => $mahasiswa
        ]);
    }
}
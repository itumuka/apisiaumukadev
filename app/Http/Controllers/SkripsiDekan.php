<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SkripsiDekan extends Controller
{
    /**
     * Dekan: List rekap bimbingan mahasiswa per fakultas
     */
    public function list_bimbingan_fakultas(Request $request)
    {
        $kode_fakultas = $request->kode_fakultas;
        if (!$kode_fakultas) return response()->json(['error' => 'Parameter kode_fakultas diperlukan'], 400);

        $rows = DB::table('akd_skripsi as s')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->join('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->leftJoin('simpeg_pegawai as d', 's.id_dosen_pembimbing1', '=', 'd.id')
            ->select(
                's.id', 's.nim', 'm.nama_mahasiswa', 'p.nama_program_studi as prodi',
                DB::raw("TRIM(CONCAT_WS(' ', d.gelar_depan, d.nama, d.gelar_belakang)) as pembimbing"),
                DB::raw("(CASE WHEN s.fase_aktif = 'ujian' THEN 8 ELSE COALESCE(p.ta_minimal_bimbingan, 8) END) as min_bimbingan"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status = 'disetujui_kaprodi') as total_waiting_dekan"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status = 'disetujui_dekan') as total_disetujui_dekan"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status IN ('disetujui', 'disetujui_kaprodi', 'disetujui_dekan')) as total_approved")
            )
            ->where('p.kode_fakultas', $kode_fakultas)
            ->orderBy('m.nama_mahasiswa', 'asc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    /**
     * Dekan: Approve bimbingan (individual or bulk for a student)
     */
    public function approve_bimbingan_fakultas(Request $request)
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

        $query = DB::table('akd_skripsi_bimbingan')->where('status', 'disetujui_kaprodi');
        if ($id_log) {
            $query->where('id', $id_log);
        } else {
            $query->where('id_skripsi', $id_skripsi);
        }

        $affected = $query->update([
            'status' => 'disetujui_dekan',
            'updated_at' => now()
        ]);

        return response()->json(['success' => "$affected Log bimbingan berhasil disetujui Dekan"]);
    }

    /**
     * Dekan: Reject bimbingan log (return for revision)
     */
    public function reject_bimbingan_fakultas(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_log' => 'required|integer',
            'catatan_dosen' => 'nullable|string'
        ]);
        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $affected = DB::table('akd_skripsi_bimbingan')
            ->where('id', $request->id_log)
            ->where('status', 'disetujui_kaprodi')
            ->update([
                'status' => 'revisi_dekan',
                'catatan_dosen' => $request->catatan_dosen,
                'updated_at' => now()
            ]);

        if ($affected) {
            return response()->json(['success' => 'Log bimbingan berhasil dikembalikan untuk revisi']);
        }
        return response()->json(['error' => 'Gagal menolak log bimbingan (atau log tidak ditemukan/status tidak sesuai)'], 400);
    }
}

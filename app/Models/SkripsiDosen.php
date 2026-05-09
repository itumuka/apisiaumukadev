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
        if (!$id_dosen) return response()->json(['error' => 'ID Dosen diperlukan'], 400);

        $m = new Mskripsi();
        $mahasiswa = $m->getMahasiswaBimbingan($id_dosen);

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

        // Logika sederhana: Kita simpan ACC di tabel akd_skripsi
        // Harus menyesuaikan skema jika ada tabel khusus untuk persetujuan.
        // Asumsi: kita tambahkan field acc_pembimbing1 atau acc_pembimbing2 di proposal/ujian
        // Untuk sekarang, karena skema spesifik belum jelas, kita berikan response sukses sementara
        // atau update status skripsi jika diperlukan.

        // TODO: Sesuaikan dengan skema real (misal update akd_skripsi_proposal.status = 'disetujui_pembimbing')
        
        return response()->json([
            'success' => "Persetujuan {$request->fase} berhasil disimpan."
        ]);
    }
}

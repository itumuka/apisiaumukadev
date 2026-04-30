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
}
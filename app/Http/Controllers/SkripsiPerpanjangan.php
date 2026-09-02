<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\FinancialClearanceService;

class SkripsiPerpanjangan extends Controller
{
    /**
     * Cek status dan prasyarat keuangan perpanjangan studi mahasiswa
     */
    public function cek_syarat_perpanjangan(Request $request)
    {
        $nim = $request->nim;
        if (!$nim) {
            return response()->json(['error' => 'Parameter nim diperlukan'], 400);
        }

        $mhs = DB::table('akd_mahasiswa as m')
            ->leftJoin('akd_program_studi as ps', 'm.kode_program_studi', '=', 'ps.kode_program_studi')
            ->where('m.nim', $nim)
            ->select('m.nim', 'm.nama_mahasiswa', 'm.tahun_angkatan', 'm.kode_program_studi', 'ps.nama_program_studi')
            ->first();

        if (!$mhs) {
            return response()->json(['error' => 'Mahasiswa tidak ditemukan'], 404);
        }

        $clearance = FinancialClearanceService::checkClearance($nim, 'perpanjangan_studi');

        // Cek riwayat pengajuan perpanjangan mahasiswa
        $cekta = DB::table('akd_mreg')->where('trash', '1')->first();
        $tahun = $cekta ? $cekta->tahun : date('Y');
        $semester = $cekta ? $cekta->semester : '1';

        $riwayat = DB::table('akd_skripsi_perpanjangan')
            ->where('nim', $nim)
            ->orderBy('id', 'desc')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'mahasiswa' => $mhs,
                'clearance' => $clearance,
                'pengajuan_aktif' => $riwayat,
                'semester_aktif' => [
                    'tahun' => $tahun,
                    'semester' => $semester
                ]
            ]
        ]);
    }

    /**
     * Mahasiswa mengajukan perpanjangan masa studi
     */
    public function ajukan_perpanjangan(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'alasan_perpanjangan' => 'required|string|min:10',
            'progress_terakhir' => 'nullable|string',
            'target_selesai' => 'nullable|date'
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();

        $cekta = DB::table('akd_mreg')->where('trash', '1')->first();
        $tahun = $cekta ? $cekta->tahun : date('Y');
        $semester = $cekta ? $cekta->semester : '1';

        // Evaluasi status keuangan terkini
        $clearance = FinancialClearanceService::checkClearance($nim, 'perpanjangan_studi', $tahun, $semester);
        $isLunas = $clearance['is_lunas'];

        $statusKeuangan = $isLunas ? 'lunas' : 'pending';
        $statusFinal = $isLunas ? 'disetujui' : 'diajukan';

        $data = [
            'id_skripsi' => $skripsi ? $skripsi->id : null,
            'nim' => $nim,
            'tahun' => $tahun,
            'semester' => $semester,
            'alasan_perpanjangan' => $request->alasan_perpanjangan,
            'progress_terakhir' => $request->progress_terakhir ?: 'Bimbingan Tugas Akhir',
            'target_selesai' => $request->target_selesai ?: date('Y-m-d', strtotime('+3 months')),
            'status_keuangan' => $statusKeuangan,
            'status_final' => $statusFinal,
            'updated_at' => now()
        ];

        // Cek apakah sudah pernah mengajukan di semester yang sama
        $existing = DB::table('akd_skripsi_perpanjangan')
            ->where('nim', $nim)
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->first();

        if ($existing) {
            DB::table('akd_skripsi_perpanjangan')->where('id', $existing->id)->update($data);
            $idPerpanjangan = $existing->id;
        } else {
            $data['created_at'] = now();
            $idPerpanjangan = DB::table('akd_skripsi_perpanjangan')->insertGetId($data);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isLunas 
                ? 'Pengajuan perpanjangan studi berhasil dan langsung disetujui (Keuangan Lunas).' 
                : 'Pengajuan perpanjangan studi berhasil disimpan. Silakan melunasi tagihan keuangan agar masa studi aktif kembali.',
            'data' => [
                'id' => $idPerpanjangan,
                'status_keuangan' => $statusKeuangan,
                'status_final' => $statusFinal,
                'clearance' => $clearance
            ]
        ]);
    }

    /**
     * Monitoring Daftar Mahasiswa Perpanjangan Studi untuk Kaprodi
     */
    public function list_perpanjangan_kaprodi(Request $request)
    {
        $kodeProdi = $request->kode_prodi;

        $query = DB::table('akd_skripsi_perpanjangan as p')
            ->join('akd_mahasiswa as m', 'p.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as ps', 'm.kode_program_studi', '=', 'ps.kode_program_studi')
            ->leftJoin('akd_skripsi as s', 'p.id_skripsi', '=', 's.id')
            ->leftJoin('simpeg_pegawai as d1', 's.id_dosen_pembimbing1', '=', 'd1.id')
            ->leftJoin('simpeg_pegawai as d2', 's.id_dosen_pembimbing2', '=', 'd2.id')
            ->select(
                'p.*',
                'm.nama_mahasiswa',
                'm.tahun_angkatan',
                'm.kode_program_studi',
                'ps.nama_program_studi',
                's.judul as judul_skripsi',
                DB::raw("CONCAT_WS(' ', d1.gelar_depan, d1.nama, d1.gelar_belakang) as nama_pembimbing1"),
                DB::raw("CONCAT_WS(' ', d2.gelar_depan, d2.nama, d2.gelar_belakang) as nama_pembimbing2")
            );

        if ($kodeProdi) {
            $query->where('m.kode_program_studi', $kodeProdi);
        }

        $list = $query->orderBy('p.id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $list
        ]);
    }

    /**
     * Daftar Pengajuan Perpanjangan Studi untuk Bagian Keuangan
     */
    public function list_perpanjangan_keuangan(Request $request)
    {
        $status = $request->status; // pending, lunas, ditolak

        $query = DB::table('akd_skripsi_perpanjangan as p')
            ->join('akd_mahasiswa as m', 'p.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as ps', 'm.kode_program_studi', '=', 'ps.kode_program_studi')
            ->select(
                'p.*',
                'm.nama_mahasiswa',
                'm.tahun_angkatan',
                'm.kode_program_studi',
                'ps.nama_program_studi'
            );

        if ($status) {
            $query->where('p.status_keuangan', $status);
        }

        $list = $query->orderBy('p.id', 'desc')->get();

        // Attach dynamic clearance summary for each student
        foreach ($list as $item) {
            $item->clearance = FinancialClearanceService::checkClearance($item->nim, 'perpanjangan_studi', $item->tahun, $item->semester);
        }

        return response()->json([
            'status' => 'success',
            'data' => $list
        ]);
    }

    /**
     * Verifikasi Manual / Validasi Status Keuangan oleh Bagian Keuangan
     */
    public function verifikasi_keuangan(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_perpanjangan' => 'required',
            'status_keuangan' => 'required|in:lunas,ditolak,pending',
            'catatan_keuangan' => 'nullable|string',
            'verifikator' => 'nullable|string'
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        $id = $request->id_perpanjangan;
        $statusKeuangan = $request->status_keuangan;
        $statusFinal = ($statusKeuangan === 'lunas') ? 'disetujui' : (($statusKeuangan === 'ditolak') ? 'ditolak' : 'diajukan');

        DB::table('akd_skripsi_perpanjangan')->where('id', $id)->update([
            'status_keuangan' => $statusKeuangan,
            'catatan_keuangan' => $request->catatan_keuangan,
            'diverifikasi_oleh_keuangan' => $request->verifikator ?: 'Admin Keuangan',
            'tgl_verifikasi_keuangan' => now(),
            'status_final' => $statusFinal,
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status verifikasi keuangan perpanjangan studi berhasil diperbarui.',
            'data' => [
                'status_keuangan' => $statusKeuangan,
                'status_final' => $statusFinal
            ]
        ]);
    }
}

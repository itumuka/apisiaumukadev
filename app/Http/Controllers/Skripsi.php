<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Mskripsi;
use Illuminate\Support\Facades\Storage;

class Skripsi extends Controller
{
    /**
     * Dashboard Data Utama
     */
    public function dashboard(Request $request)
    {
        $nim = $request->nim;
        if (!$nim) return response()->json(['error' => 'NIM diperlukan'], 400);

        $m = new Mskripsi();
        $res = $m->getDashboard($nim);
        
        if (isset($res['error'])) {
            return response()->json(['status' => 'error', 'message' => $res['error']], 404);
        }

        return response()->json(['status' => 'success', 'data' => $res]);
    }

    /**
     * Simpan/Update Data Proposal Mahasiswa
     */
    public function simpan_proposal(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'topik' => 'required',
            'topik_en' => 'required',
            'judul' => 'required',
            'judul_en' => 'required',
            'abstrak' => 'required',
            'abstrak_en' => 'required',
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $data = $request->only(['topik', 'topik_en', 'judul', 'judul_en', 'abstrak', 'abstrak_en']);
        $data['updated_at'] = now();

        $skripsi = DB::table('akd_skripsi')->where('nim', $request->nim)->first();
        
        if (!$skripsi) {
            $data['nim'] = $request->nim;
            $data['tanggal_pengajuan'] = now();
            $data['created_at'] = now();
            $data['fase_aktif'] = 'proposal';
            $data['status'] = 'draft';
            DB::table('akd_skripsi')->insert($data);
        } else {
            // Hanya bisa edit jika status draft/menunggu_pembimbing
            if (!in_array($skripsi->status, ['draft', 'menunggu_pembimbing'])) {
                return response()->json(['error' => 'Proposal sudah diproses, tidak dapat diubah.'], 403);
            }
            DB::table('akd_skripsi')->where('nim', $request->nim)->update($data);
        }

        return response()->json(['success' => 'Data Proposal Berhasil Disimpan']);
    }

    /**
     * Get Daftar Syarat dan Cek Kelayakan (Fase: sempro/ujian)
     */
    public function cek_kelayakan(Request $request)
    {
        $nim = $request->nim;
        $fase = $request->fase;

        if (!$nim || !$fase) {
            return response()->json(['error' => 'Parameter nim dan fase diperlukan'], 400);
        }

        $mskripsi = new Mskripsi();
        $hasil = $mskripsi->cekKelayakan($nim, $fase);

        return response()->json([
            'status' => 'success',
            'data' => $hasil
        ]);
    }

    /**
     * Upload Naskah PDF (BAB 1-3 atau BAB 1-5)
     */
    public function upload_naskah(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'file_naskah' => 'required|mimes:pdf|max:10240', // Max 10MB
            'fase' => 'required|in:sempro,ujian'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Isi data proposal terlebih dahulu'], 400);

        // Cek apakah sudah ada proposal yang sedang diproses (diajukan/dijadwalkan)
        if ($request->fase == 'sempro') {
            $existingProposal = DB::table('akd_skripsi_proposal')
                ->where('id_skripsi', $skripsi->id)
                ->where('nim', $nim)
                ->whereIn('status', ['diajukan', 'dijadwalkan'])
                ->orderBy('iterasi', 'desc')
                ->first();
            
            if ($existingProposal) {
                return response()->json(['error' => 'Anda sudah memiliki proposal yang sedang diproses. Tidak dapat mengunggah naskah baru.'], 400);
            }
        }

        $file = $request->file('file_naskah');
        $nama_file = "NASKAH_" . $request->fase . "_" . time() . "_" . $file->getClientOriginalName();
        $path = $file->storeAs("public/skripsi_naskah/{$nim}", $nama_file);

        // Simpan atau Perbarui akd_skripsi_proposal (Update jika sudah ada draft di iterasi terbaru)
        $latestProposal = DB::table('akd_skripsi_proposal')
            ->where('id_skripsi', $skripsi->id)
            ->where('nim', $nim)
            ->orderBy('iterasi', 'desc')
            ->first();

        if ($latestProposal && $latestProposal->status == 'draft') {
            $id_proposal = $latestProposal->id;
            $new_iterasi = $latestProposal->iterasi;
            DB::table('akd_skripsi_proposal')->where('id', $id_proposal)->update([
                'path_file_pdf' => $path,
                'updated_at' => now()
            ]);
        } else {
            $iterasi = $latestProposal->iterasi ?? 0;
            $new_iterasi = $iterasi + 1;
            $id_proposal = DB::table('akd_skripsi_proposal')->insertGetId([
                'id_skripsi' => $skripsi->id,
                'nim' => $nim,
                'iterasi' => $new_iterasi,
                'path_file_pdf' => $path,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Jika fase ujian, hubungkan ke akd_skripsi_ujian
        if ($request->fase == 'ujian') {
            DB::table('akd_skripsi_ujian')->updateOrInsert(
                ['id_skripsi' => $skripsi->id, 'nim' => $nim],
                ['id_proposal' => $id_proposal, 'status' => 'pending', 'updated_at' => now()]
            );
        }

        return response()->json(['success' => 'Naskah berhasil diunggah', 'id_proposal' => $id_proposal, 'iterasi' => $new_iterasi]);
    }

    /**
     * Upload Berkas Persyaratan (Sertifikat, dll)
     */
    public function upload_berkas(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'id_syarat_prodi' => 'required',
            'fase' => 'required|in:sempro,ujian',
            'tipe' => 'required|in:file,url'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data Skripsi belum ada'], 400);

        $path = $request->tipe == 'file' ? 
                $request->file('file_berkas')->storeAs("public/skripsi_berkas/{$nim}", time() . "_" . $request->file('file_berkas')->getClientOriginalName()) : 
                $request->url_berkas;

        DB::table('akd_skripsi_berkas')->updateOrInsert(
            ['nim' => $nim, 'id_syarat_prodi' => $request->id_syarat_prodi, 'fase' => $request->fase],
            ['id_skripsi' => $skripsi->id, 'tipe' => $request->tipe, 'path_file' => $path, 'nama_file' => $request->tipe == 'file' ? $request->file('file_berkas')->getClientOriginalName() : 'URL Link', 'updated_at' => now()]
        );

        return response()->json(['success' => 'Berkas berhasil diunggah']);
    }

    /**
     * Get Konfigurasi TA Program Studi
     */
    public function config_prodi(Request $request)
    {
        $nim = $request->nim;
        if (!$nim) return response()->json(['error' => 'NIM diperlukan'], 400);

        $config = DB::table('akd_mahasiswa as m')
            ->join('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->select('p.ta_sks_minimal', 'p.ta_ada_sempro', 'p.ta_minimal_bimbingan', 
                     'p.ta_komponen_bayar', 'p.ta_komponen_bayar_ujian', 'p.ta_nama_tugas_akhir')
            ->where('m.nim', $nim)->first();

        return response()->json(['status' => 'success', 'data' => $config]);
    }

    /**
     * Get Log Bimbingan Mahasiswa
     */
    public function log_bimbingan(Request $request)
    {
        $nim = $request->nim;
        if (!$nim) return response()->json(['error' => 'NIM diperlukan'], 400);

        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['status' => 'success', 'data' => []]);

        $logs = DB::table('akd_skripsi_bimbingan')
            ->where('id_skripsi', $skripsi->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $logs]);
    }

    /**
     * Tambah Log Bimbingan Mahasiswa
     */
    public function tambah_bimbingan(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'tanggal' => 'required|date',
            'topik' => 'required',
            'uraian' => 'required',
            'file_lampiran' => 'nullable|mimes:pdf,doc,docx|max:5120' // Max 5MB
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        
        if (!$skripsi) {
            return response()->json(['error' => 'Data Skripsi belum ada. Silakan ajukan proposal terlebih dahulu.'], 400);
        }

        // Cek apakah pembimbing sudah di-ploting
        if (!$skripsi->id_dosen_pembimbing1) {
            return response()->json(['error' => 'Pembimbing belum di-ploting. Hubungi bagian akademik.'], 400);
        }

        $path = null;
        if ($request->hasFile('file_lampiran')) {
            $file = $request->file('file_lampiran');
            $nama_file = "BIMBINGAN_" . time() . "_" . $file->getClientOriginalName();
            $path = $file->storeAs("public/skripsi_bimbingan/{$nim}", $nama_file);
        }

        DB::table('akd_skripsi_bimbingan')->insert([
            'nim' => $nim,
            'id_skripsi' => $skripsi->id,
            'id_dosen' => $skripsi->id_dosen_pembimbing1,
            'tanggal' => $request->tanggal,
            'topik' => $request->topik,
            'uraian' => $request->uraian,
            'path_file' => $path,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => 'Catatan bimbingan berhasil disimpan.']);
    }
}
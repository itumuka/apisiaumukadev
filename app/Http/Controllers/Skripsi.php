<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Mskripsi;
use Illuminate\Support\Facades\Storage;
use App\Helpers\NotificationHelper;

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
            'target_luaran' => 'nullable|string',
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $data = $request->only(['topik', 'topik_en', 'judul', 'judul_en', 'abstrak', 'abstrak_en']);
        $data['target_luaran'] = $request->target_luaran ?? 'buku_skripsi';
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
            // Hanya bisa edit jika status masih tahap awal/proses bimbingan
            if (!in_array($skripsi->status, ['draft', 'menunggu_pembimbing', 'aktif'])) {
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

        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        $hasil['judul_proposal'] = $skripsi ? $skripsi->judul : '';

        // Add ta_is_obe and ujian_skripsi_lunas fields dynamically
        $mhs = DB::table('akd_mahasiswa')->where('nim', $nim)->first();
        $is_obe = 1;
        $bayar_ujian = true;
        if ($mhs) {
            $prodiConfig = DB::table('akd_program_studi')
                ->where('kode_program_studi', $mhs->kode_program_studi)
                ->select('ta_komponen_bayar_ujian', 'ta_is_obe')
                ->first();
            if ($prodiConfig) {
                $is_obe = isset($prodiConfig->ta_is_obe) ? $prodiConfig->ta_is_obe : 1;
                if ($fase == 'ujian') {
                    // Check if student has a dispensation for SKRIPSI
                    $cekta = DB::table('akd_mreg')->where('trash', '1')->first();
                    $hasDispensasi = false;
                    if ($cekta) {
                        $hasDispensasi = DB::table('akd_dispensasi')
                            ->where('nim', $nim)
                            ->where('tahun', $cekta->tahun)
                            ->where('semester', $cekta->semester)
                            ->where('jenis', 'SKRIPSI')
                            ->exists();
                    }

                    $hasFullScholarship = DB::table('keu_beasiswa_mahasiswa as bm')
                        ->join('keu_sumber_beasiswa as s', 'bm.id_sumber_beasiswa', '=', 's.id_sumber_beasiswa')
                        ->where('bm.nim', $nim)
                        ->where('bm.status_aktif', 1)
                        ->where('s.jenis_beasiswa', 'full')
                        ->exists();

                    $hasUjianScholarship = $hasFullScholarship || DB::table('keu_beasiswa_mahasiswa as bm')
                        ->join('keu_beasiswa_cakupan as bc', 'bm.id_sumber_beasiswa', '=', 'bc.id_sumber_beasiswa')
                        ->where('bm.nim', $nim)
                        ->where('bm.status_aktif', 1)
                        ->where('bc.persentase_potongan', 100.00)
                        ->where(function($q) use ($prodiConfig) {
                            $q->where('bc.kode_komponen', 'like', '%' . $prodiConfig->ta_komponen_bayar_ujian . '%')
                              ->orWhere('bc.kode_komponen', 'like', '%Ujian%');
                        })
                        ->exists();

                    if ($hasDispensasi || $hasUjianScholarship) {
                        $bayar_ujian = true;
                    } else if (!empty($prodiConfig->ta_komponen_bayar_ujian)) {
                        $nama_biaya = $prodiConfig->ta_komponen_bayar_ujian;
                        $bayar_ujian = DB::table('keu_tagihan')
                            ->where('nim', $nim)
                            ->where('nama_biaya', 'like', '%' . $nama_biaya . '%')
                            ->where('status', '1')
                            ->count() > 0;
                    } else {
                        // Jika komponen bayar ujian kosong (seperti D3 Tugas Akhir), dianggap lunas / tanpa bayar ujian
                        $bayar_ujian = true;
                    }
                }
            }
        }
        $hasil['ta_is_obe'] = $is_obe;
        $hasil['ujian_skripsi_lunas'] = $bayar_ujian;

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
            $ujianExist = DB::table('akd_skripsi_ujian')
                ->where('id_skripsi', $skripsi->id)
                ->where('nim', $nim)
                ->first();
            if (!$ujianExist) {
                DB::table('akd_skripsi_ujian')->insert([
                    'id_skripsi' => $skripsi->id,
                    'nim' => $nim,
                    'id_proposal' => $id_proposal,
                    'status' => 'revisi',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                DB::table('akd_skripsi_ujian')
                    ->where('id', $ujianExist->id)
                    ->update([
                        'id_proposal' => $id_proposal,
                        'updated_at' => now()
                    ]);
            }
        }

        return response()->json(['success' => 'Naskah berhasil diunggah', 'id_proposal' => $id_proposal, 'iterasi' => $new_iterasi]);
    }

    /**
     * Hapus Naskah Draft Mahasiswa (Sempro/Ujian)
     */
    public function hapus_naskah(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'fase' => 'required|in:sempro,ujian'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data skripsi tidak ditemukan'], 404);

        $proposal = DB::table('akd_skripsi_proposal')
            ->where('id_skripsi', $skripsi->id)
            ->where('nim', $nim)
            ->orderBy('iterasi', 'desc')
            ->first();

        if (!$proposal) {
            return response()->json(['error' => 'Data naskah tidak ditemukan'], 404);
        }

        if ($proposal->status !== 'draft') {
            return response()->json(['error' => 'Naskah tidak dapat dihapus karena sudah diproses'], 403);
        }

        if (!$proposal->path_file_pdf) {
            return response()->json(['error' => 'Tidak ada file naskah untuk dihapus'], 400);
        }

        if (Storage::exists($proposal->path_file_pdf)) {
            Storage::delete($proposal->path_file_pdf);
        }

        DB::table('akd_skripsi_proposal')->where('id', $proposal->id)->update([
            'path_file_pdf' => null,
            'updated_at' => now()
        ]);

        return response()->json(['success' => 'Naskah berhasil dihapus']);
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
     * Ajukan Pendaftaran Seminar Proposal
     */
    public function ajukan_sempro(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data skripsi belum ada. Silakan simpan proposal terlebih dahulu.'], 400);

        $proposal = DB::table('akd_skripsi_proposal')
            ->where('id_skripsi', $skripsi->id)
            ->where('nim', $nim)
            ->orderBy('iterasi', 'desc')
            ->first();

        if (!$proposal) {
            return response()->json(['error' => 'Data proposal belum ditemukan. Silakan unggah naskah terlebih dahulu.'], 422);
        }

        if (!$proposal->path_file_pdf) {
            return response()->json(['error' => 'Naskah proposal belum diunggah.'], 422);
        }

        if (in_array($proposal->status, ['diajukan', 'dijadwalkan', 'lulus'])) {
            return response()->json(['error' => 'Pendaftaran seminar proposal sudah diajukan sebelumnya.'], 409);
        }

        DB::table('akd_skripsi_proposal')
            ->where('id', $proposal->id)
            ->update([
                'status' => 'diajukan',
                'updated_at' => now()
            ]);

        return response()->json(['success' => 'Pendaftaran seminar proposal berhasil diajukan.']);
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
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
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

        // Notify Dosen Pembimbing about the new guidance submission
        $dosen = DB::table('simpeg_pegawai')
            ->where('id', $skripsi->id_dosen_pembimbing1)
            ->select('email_umuka', 'nidn')
            ->first();

        if ($dosen) {
            $targetUser = $dosen->email_umuka ?: $dosen->nidn;
            if ($targetUser) {
                $studentName = DB::table('akd_mahasiswa')->where('nim', $nim)->value('nama_mahasiswa') ?? $nim;
                \App\Helpers\NotificationHelper::send(
                    $targetUser,
                    'Bimbingan Baru Masuk',
                    "Mahasiswa {$studentName} ({$nim}) mengajukan bimbingan baru: \"" . substr($request->topik, 0, 50) . "\".",
                    '/dosen/skripsi/bimbingan',
                    'skripsi'
                );
            }
        }

        return response()->json(['success' => 'Catatan bimbingan berhasil disimpan.']);
    }

    /**
     * Update Log Bimbingan Mahasiswa (Hanya untuk status 'pending' atau 'revisi')
     */
    public function update_bimbingan(Request $request, $id)
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
        $log = DB::table('akd_skripsi_bimbingan')->where('id', $id)->first();

        if (!$log) {
            return response()->json(['error' => 'Catatan bimbingan tidak ditemukan.'], 404);
        }

        if ($log->nim !== $nim) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke catatan bimbingan ini.'], 403);
        }

        if (!in_array($log->status, ['pending', 'revisi'])) {
            return response()->json(['error' => 'Catatan bimbingan sudah disetujui, tidak dapat diubah.'], 403);
        }

        $data = [
            'tanggal' => $request->tanggal,
            'topik' => $request->topik,
            'uraian' => $request->uraian,
            'updated_at' => now()
        ];

        if ($request->hasFile('file_lampiran')) {
            // Hapus file lama jika ada
            if ($log->path_file && Storage::exists($log->path_file)) {
                Storage::delete($log->path_file);
            }

            $file = $request->file('file_lampiran');
            $nama_file = "BIMBINGAN_" . time() . "_" . $file->getClientOriginalName();
            $path = $file->storeAs("public/skripsi_bimbingan/{$nim}", $nama_file);
            $data['path_file'] = $path;
        }

        DB::table('akd_skripsi_bimbingan')->where('id', $id)->update($data);

        return response()->json(['success' => 'Catatan bimbingan berhasil diperbarui.']);
    }

    /**
     * Hapus Log Bimbingan Mahasiswa (Hanya untuk status 'pending' atau 'revisi')
     */
    public function hapus_bimbingan(Request $request, $id)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $nim = $request->nim;
        $log = DB::table('akd_skripsi_bimbingan')->where('id', $id)->first();

        if (!$log) {
            return response()->json(['error' => 'Catatan bimbingan tidak ditemukan.'], 404);
        }

        if ($log->nim !== $nim) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke catatan bimbingan ini.'], 403);
        }

        if (!in_array($log->status, ['pending', 'revisi'])) {
            return response()->json(['error' => 'Catatan bimbingan sudah disetujui, tidak dapat dihapus.'], 403);
        }

        // Hapus file lampiran jika ada
        if ($log->path_file && Storage::exists($log->path_file)) {
            Storage::delete($log->path_file);
        }

        DB::table('akd_skripsi_bimbingan')->where('id', $id)->delete();

        return response()->json(['success' => 'Catatan bimbingan berhasil dihapus.']);
    }

    /**
     * Admin: Rekap Bimbingan per Mahasiswa
     */
    public function rekap_bimbingan(Request $request)
    {
        $rows = DB::table('akd_skripsi as s')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->leftJoin('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->leftJoin('simpeg_pegawai as d', 's.id_dosen_pembimbing1', '=', 'd.id')
            ->select(
                's.id', 's.nim', 'm.nama_mahasiswa', 'p.nama_program_studi as prodi',
                DB::raw("TRIM(CONCAT_WS(' ', d.gelar_depan, d.nama, d.gelar_belakang)) as pembimbing"),
                DB::raw("(CASE WHEN s.fase_aktif = 'ujian' THEN 8 ELSE COALESCE(p.ta_minimal_bimbingan, 8) END) as min_bimbingan"),
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status IN ('disetujui', 'disetujui_kaprodi', 'disetujui_dekan', 'revisi')) as total_bimbingan")
            )
            ->orderBy('m.nama_mahasiswa', 'asc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    /**
     * Get Realisasi & Target Luaran Skripsi
     */
    public function get_luaran(Request $request)
    {
        $nim = $request->nim;
        if (!$nim) return response()->json(['error' => 'Parameter nim diperlukan'], 400);

        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data skripsi tidak ditemukan'], 404);

        $luaran = DB::table('akd_skripsi_luaran')->where('id_skripsi', $skripsi->id)->first();
        
        $ujian = DB::table('akd_skripsi_ujian')->where('id_skripsi', $skripsi->id)->first();
        $ujian_locked = false;
        $notice_message = null;

        if ($ujian) {
            if (in_array($ujian->status, ['ditetapkan', 'lulus', 'tidak_lulus'])) {
                $ujian_locked = true;
                $notice_message = 'Ujian Anda telah selesai dilaksanakan & nilai telah difinalisasi.';
            } else if ($ujian->status == 'diajukan') {
                $notice_message = 'Pendaftaran Ujian Terkirim – Menunggu Penjadwalan Kaprodi.';
            } else if (in_array($ujian->status, ['disetujui', 'dijadwalkan'])) {
                $notice_message = 'Pendaftaran Ujian Telah Disetujui & Dijadwalkan oleh Kaprodi.';
            } else if (in_array($ujian->status, ['dinilai', 'menunggu_penetapan'])) {
                // Not locked, but show a notice about grading/revision!
                $ba = DB::table('akd_skripsi_berita_acara')->where('id_skripsi_ujian', $ujian->id)->first();
                if ($ba && $ba->keputusan == 'lulus_dengan_perbaikan') {
                    $notice_message = 'Keputusan: Lulus dengan Perbaikan (Revisi). Silakan perbarui judul/link luaran & naskah jika diperlukan sebelum masa revisi berakhir (' . ($ba->batas_revisi ? date('d-m-Y', strtotime($ba->batas_revisi)) : '-') . ').';
                } else {
                    $notice_message = 'Ujian selesai. Silakan sesuaikan/lengkapi link luaran jurnal/berkas pendukung jika diperlukan.';
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'target_luaran' => $skripsi->target_luaran,
                'luaran' => $luaran,
                'ujian_locked' => $ujian_locked,
                'notice_message' => $notice_message,
                'ujian_status' => $ujian ? $ujian->status : null
            ]
        ]);
    }

    /**
     * Batalkan Pendaftaran Ujian Skripsi (Hanya jika status 'diajukan')
     */
    public function batalkan_ujian(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data skripsi tidak ditemukan.'], 404);

        $ujian = DB::table('akd_skripsi_ujian')
            ->where('id_skripsi', $skripsi->id)
            ->where('nim', $nim)
            ->first();

        if (!$ujian) {
            return response()->json(['error' => 'Pendaftaran ujian tidak ditemukan.'], 404);
        }

        if ($ujian->status !== 'diajukan') {
            return response()->json(['error' => 'Pendaftaran ujian tidak dapat dibatalkan karena sudah diproses atau dijadwalkan oleh Kaprodi.'], 400);
        }

        DB::table('akd_skripsi_ujian')
            ->where('id', $ujian->id)
            ->update([
                'status' => 'pending',
                'updated_at' => now()
            ]);

        return response()->json(['success' => 'Pendaftaran ujian berhasil dibatalkan. Silakan sesuaikan data Anda dan ajukan kembali.']);
    }

    /**
     * Simpan/Update Realisasi Luaran Skripsi
     */
    public function simpan_luaran(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required',
            'jenis_luaran' => 'required|in:buku_skripsi,jurnal_sinta,jurnal_internasional,prosiding,paten,hki,lainnya',
            'judul_luaran' => 'nullable|string',
            'nama_media' => 'nullable|string',
            'url_link' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $skripsi = DB::table('akd_skripsi')->where('nim', $request->nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data skripsi tidak ditemukan'], 404);

        $data = [
            'jenis_luaran' => $request->jenis_luaran,
            'judul_luaran' => $request->judul_luaran,
            'nama_media' => $request->nama_media,
            'url_link' => $request->url_link,
            'updated_at' => now()
        ];

        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            $path = $file->store('skripsi_luaran_bukti', 'public');
            $data['file_bukti'] = $path;
        }

        $luaran = DB::table('akd_skripsi_luaran')->where('id_skripsi', $skripsi->id)->first();

        if ($luaran) {
            DB::table('akd_skripsi_luaran')->where('id_skripsi', $skripsi->id)->update($data);
        } else {
            $data['id_skripsi'] = $skripsi->id;
            $data['nim'] = $request->nim;
            $data['status_validasi'] = 'menunggu';
            $data['created_at'] = now();
            DB::table('akd_skripsi_luaran')->insert($data);
        }

        // Sinkronisasi target_luaran ke tabel utama akd_skripsi
        DB::table('akd_skripsi')
            ->where('id', $skripsi->id)
            ->update(['target_luaran' => $request->jenis_luaran]);

        // Kirim notifikasi sistem ke para dosen penguji
        $ujian = DB::table('akd_skripsi_ujian')->where('id_skripsi', $skripsi->id)->first();
        if ($ujian) {
            $mhs = DB::table('akd_mahasiswa')->where('nim', $request->nim)->first();
            $namaMhs = $mhs ? $mhs->nama_mahasiswa : $request->nim;
            
            $dosenIds = array_filter([$ujian->id_penguji1, $ujian->id_penguji2, $ujian->id_penguji3]);
            if (!empty($dosenIds)) {
                $emails = DB::table('simpeg_pegawai')
                    ->whereIn('id', $dosenIds)
                    ->pluck('email_umuka')
                    ->toArray();

                foreach ($emails as $email) {
                    if ($email) {
                        NotificationHelper::send(
                            $email,
                            'Pembaruan Revisi & Luaran Ujian',
                            "Mahasiswa {$namaMhs} ({$request->nim}) telah memperbarui tautan dokumen/luaran revisi skripsi.",
                            'dosen/skripsi/ujian',
                            'ujian_revisi'
                        );
                    }
                }
            }
        }

        return response()->json(['success' => 'Data Realisasi Luaran Berhasil Disimpan']);
    }

    /**
     * Ajukan Pendaftaran Ujian Skripsi
     */
    public function ajukan_ujian(Request $request)
    {
        $v = Validator::make($request->all(), [
            'nim' => 'required'
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()->all()], 422);

        $nim = $request->nim;
        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data skripsi belum ada.'], 400);

        $ujian = DB::table('akd_skripsi_ujian')
            ->where('id_skripsi', $skripsi->id)
            ->where('nim', $nim)
            ->first();

        if (!$ujian || $ujian->status !== 'pending') {
            if ($ujian && in_array($ujian->status, ['diajukan', 'disetujui', 'dijadwalkan', 'dinilai', 'menunggu_penetapan', 'ditetapkan', 'lulus', 'tidak_lulus'])) {
                // If title (judul) is updated, save it
                if ($request->has('judul') && !empty($request->judul)) {
                    DB::table('akd_skripsi')
                        ->where('id', $skripsi->id)
                        ->update(['judul' => $request->judul]);
                }
                return response()->json(['success' => 'Pembaruan data pendaftaran ujian berhasil disimpan.']);
            }
            return response()->json(['error' => 'Anda belum mendapatkan persetujuan (ACC Ujian) dari Dosen Pembimbing.'], 403);
        }

        // Update judul jika dikirimkan dari form pendaftaran ujian
        if ($request->has('judul') && !empty($request->judul)) {
            DB::table('akd_skripsi')
                ->where('id', $skripsi->id)
                ->update(['judul' => $request->judul]);
        }

        DB::table('akd_skripsi_ujian')
            ->where('id', $ujian->id)
            ->update([
                'status' => 'diajukan',
                'updated_at' => now()
            ]);

        return response()->json(['success' => 'Pendaftaran ujian skripsi berhasil diajukan.']);
    }

    /**
     * Ambil Portofolio Pencapaian CPL Skripsi Mahasiswa
     */
    public function get_portofolio_cpl(Request $request)
    {
        $nim = $request->nim;
        if (!$nim) return response()->json(['error' => 'Parameter nim diperlukan'], 400);

        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        if (!$skripsi) return response()->json(['error' => 'Data skripsi tidak ditemukan.'], 404);

        $ujian = DB::table('akd_skripsi_ujian')
            ->where('id_skripsi', $skripsi->id)
            ->where('nim', $nim)
            ->first();

        if (!$ujian) {
            return response()->json([
                'status' => 'success',
                'data' => null
            ]);
        }

        // Ambil nilai per Indikator
        $scores = DB::table('akd_skripsi_nilai_indikator')
            ->where('id_skripsi_ujian', $ujian->id)
            ->get();

        if ($scores->count() == 0) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'nilai_angka' => $ujian->nilai_angka,
                    'nilai_ujian' => $ujian->nilai_ujian,
                    'status_ujian' => $ujian->status,
                    'cpl_portfolio' => [],
                    'cpmk_scores' => []
                ]
            ]);
        }
        $mhs = DB::table('akd_mahasiswa')->where('nim', $nim)->first();
        $kode_prodi = $mhs ? $mhs->kode_program_studi : null;

        // Tentukan jalur (reguler vs obe)
        $jalur = 'reguler';
        if ($skripsi && !empty($skripsi->target_luaran) && $skripsi->target_luaran !== 'buku_skripsi') {
            $jalur = 'obe';
        }

        // Ambil kriteria Indikator untuk prodi mahasiswa
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

        // Hitung rata-rata nilai per Indikator dari semua penguji/verifikator
        $indicator_averages = [];
        $grouped_scores = $scores->groupBy('id_rubrik_indikator');
        foreach ($grouped_scores as $rubrik_id => $rubrik_scores) {
            $indicator_averages[$rubrik_id] = $rubrik_scores->avg('nilai');
        }

        $scores_formatted = [];
        foreach ($indicator_averages as $rubrik_id => $avg) {
            $s_row = $grouped_scores->get($rubrik_id)->first();
            $nama_ind = $s_row->nama_indikator_snapshot ?? (isset($rubrics[$rubrik_id]) ? $rubrics[$rubrik_id]->nama_indikator : 'Kriteria ' . $rubrik_id);
            $kode_ind = isset($rubrics[$rubrik_id]) ? $rubrics[$rubrik_id]->kode_indikator : '';
            $kkm = isset($rubrics[$rubrik_id]) ? (float)$rubrics[$rubrik_id]->kkm : 70.00;

            $scores_formatted[] = [
                'id_cpmk' => $rubrik_id,
                'kode_cpmk' => $kode_ind,
                'nama_cpmk' => $nama_ind,
                'nilai' => round($avg, 2),
                'kkm' => $kkm,
                'status' => round($avg, 2) >= $kkm ? 'Lulus' : 'Belum Lulus'
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'nilai_angka' => $ujian->nilai_angka,
                'nilai_ujian' => $ujian->nilai_ujian,
                'status_ujian' => $ujian->status,
                'cpl_portfolio' => [],
                'cpmk_scores' => $scores_formatted
            ]
        ]);
    }

    /**
     * Get data for printing guidance booklet
     */
    public function get_cetak_bimbingan(Request $request)
    {
        $nim = $request->nim;
        if (!$nim) return response()->json(['error' => 'NIM diperlukan'], 400);

        // 1. Get student profile, program study, faculty, and head of program study (Kaprodi)
        $mhs = DB::table('akd_mahasiswa as m')
            ->join('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->leftJoin('akd_fakultas as f', 'p.kode_fakultas', '=', 'f.kode_fakultas')
            ->leftJoin('simpeg_pegawai as k', 'p.pimpinan_prodi', '=', 'k.id')
            ->select(
                'm.nim', 'm.nama_mahasiswa', 'p.nama_program_studi', 'f.nama_fakultas',
                DB::raw("TRIM(CONCAT_WS(' ', k.gelar_depan, k.nama, k.gelar_belakang)) as nama_kaprodi"),
                'k.nidn as nidn_kaprodi',
                'p.pimpinan_prodi'
            )
            ->where('m.nim', $nim)
            ->first();

        if (!$mhs) return response()->json(['error' => 'Data mahasiswa tidak ditemukan'], 404);

        $skripsi = DB::table('akd_skripsi as s')
            ->leftJoin('simpeg_pegawai as p1', 's.id_dosen_pembimbing1', '=', 'p1.id')
            ->leftJoin('simpeg_pegawai as p2', 's.id_dosen_pembimbing2', '=', 'p2.id')
            ->select(
                's.*',
                DB::raw("TRIM(CONCAT_WS(' ', p1.gelar_depan, p1.nama, p1.gelar_belakang)) as nama_pembimbing1"),
                'p1.nidn as nidn_pembimbing1',
                DB::raw("TRIM(CONCAT_WS(' ', p2.gelar_depan, p2.nama, p2.gelar_belakang)) as nama_pembimbing2"),
                'p2.nidn as nidn_pembimbing2'
            )
            ->where('s.nim', $nim)
            ->first();

        if ($skripsi) {
            $updateSkripsi = [];

            // Auto-generate/fetch QR for Kaprodi (saved in akd_skripsi)
            if ($mhs && $mhs->pimpinan_prodi && empty($skripsi->valid_id_kaprodi)) {
                $valid_id_kaprodi = uniqid('kaprodi_', true);
                $updateSkripsi['valid_id_kaprodi'] = $valid_id_kaprodi;
                $skripsi->valid_id_kaprodi = $valid_id_kaprodi;
            }

            // Auto-generate/fetch QR for Pembimbing 1 (saved in akd_skripsi)
            if ($skripsi->id_dosen_pembimbing1 && empty($skripsi->valid_id_pembimbing1)) {
                $valid_id_p1 = uniqid('pemb1_', true);
                $updateSkripsi['valid_id_pembimbing1'] = $valid_id_p1;
                $skripsi->valid_id_pembimbing1 = $valid_id_p1;
            }

            // Auto-generate/fetch QR for Pembimbing 2 (saved in akd_skripsi)
            if ($skripsi->id_dosen_pembimbing2 && empty($skripsi->valid_id_pembimbing2)) {
                $valid_id_p2 = uniqid('pemb2_', true);
                $updateSkripsi['valid_id_pembimbing2'] = $valid_id_p2;
                $skripsi->valid_id_pembimbing2 = $valid_id_p2;
            }

            if (!empty($updateSkripsi)) {
                DB::table('akd_skripsi')->where('id', $skripsi->id)->update($updateSkripsi);
            }

            // Map Kaprodi's valid_id directly to the student profile for easy frontend retrieval
            $mhs->valid_id_kaprodi = $skripsi->valid_id_kaprodi;
        }

        // 3. Get approved bimbingan logs (minimal approved by Dosen Pembimbing)
        // Approved by Dosen means status is 'disetujui' or 'disetujui_kaprodi'.
        $logs = [];
        if ($skripsi) {
            $logs = DB::table('akd_skripsi_bimbingan as b')
                ->leftJoin('simpeg_pegawai as d', 'b.id_dosen', '=', 'd.id')
                ->select(
                    'b.id', 'b.tanggal', 'b.topik', 'b.uraian', 'b.status', 'b.catatan_dosen', 'b.created_at', 'b.updated_at',
                    DB::raw("TRIM(CONCAT_WS(' ', d.gelar_depan, d.nama, d.gelar_belakang)) as nama_dosen"),
                    'd.nidn as nidn_dosen',
                    'b.id_dosen',
                    'b.valid_id'
                )
                ->where(function($query) use ($skripsi, $nim) {
                    $query->where('b.id_skripsi', $skripsi->id)
                          ->orWhere('b.nim', $nim);
                })
                ->whereIn('b.status', ['disetujui', 'disetujui_kaprodi', 'disetujui_dekan'])
                ->orderBy('b.tanggal', 'asc')
                ->orderBy('b.id', 'asc')
                ->get();

            // Auto-generate/fetch QR for each log's valid_id if missing
            foreach ($logs as $log) {
                if (empty($log->valid_id)) {
                    $valid_id = uniqid('bimb_', true);
                    DB::table('akd_skripsi_bimbingan')
                        ->where('id', $log->id)
                        ->update(['valid_id' => $valid_id, 'updated_at' => now()]);
                    $log->valid_id = $valid_id;
                }
            }
        }

        // Debugging: get all raw logs regardless of status
        $all_raw_logs = [];
        if ($skripsi) {
            $all_raw_logs = DB::table('akd_skripsi_bimbingan')
                ->where('nim', $nim)
                ->select('id', 'status', 'id_skripsi')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'mahasiswa' => $mhs,
                'skripsi' => $skripsi,
                'logs' => $logs,
                'debug_all_logs' => $all_raw_logs
            ]
        ]);
    }
}
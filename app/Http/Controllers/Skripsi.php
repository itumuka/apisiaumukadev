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
                    if (!empty($prodiConfig->ta_komponen_bayar_ujian)) {
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
            DB::table('akd_skripsi_ujian')->updateOrInsert(
                ['id_skripsi' => $skripsi->id, 'nim' => $nim],
                ['id_proposal' => $id_proposal, 'status' => 'pending', 'updated_at' => now()]
            );
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

        return response()->json(['success' => 'Catatan bimbingan berhasil disimpan.']);
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
                DB::raw("(SELECT COUNT(*) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status IN ('disetujui','revisi')) as total_bimbingan")
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
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'target_luaran' => $skripsi->target_luaran,
                'luaran' => $luaran
            ]
        ]);
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

        if (!$ujian) {
            // Ambil proposal/naskah terakhir jika ada
            $latestProposal = DB::table('akd_skripsi_proposal')
                ->where('id_skripsi', $skripsi->id)
                ->where('nim', $nim)
                ->orderBy('iterasi', 'desc')
                ->first();
            
            $id_proposal = $latestProposal ? $latestProposal->id : null;

            // Inisialisasi otomatis pendaftaran ujian
            $ujianId = DB::table('akd_skripsi_ujian')->insertGetId([
                'id_skripsi' => $skripsi->id,
                'nim' => $nim,
                'id_proposal' => $id_proposal,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $ujian = DB::table('akd_skripsi_ujian')->where('id', $ujianId)->first();
        }

        if (in_array($ujian->status, ['diajukan', 'dijadwalkan', 'lulus'])) {
            return response()->json(['error' => 'Pendaftaran ujian sudah diajukan sebelumnya.'], 409);
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

        // Ambil nilai per CPMK
        $scores = DB::table('akd_skripsi_nilai_cpmk')
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

        // Check if student's prodi has custom rubrics
        $hasCustom = false;
        if ($kode_prodi) {
            $hasCustom = DB::table('akd_skripsi_rubrik_cpmk')
                ->where('kode_prodi', $kode_prodi)
                ->exists();
        }

        // Fetch active CPL descriptions to map in PHP (prevent SQL join duplication)
        $active_cpls = collect([]);
        if ($kode_prodi) {
            $active_cpls = DB::table('akd_cpl')
                ->where('is_aktif', 1)
                ->where(function ($q) use ($kode_prodi) {
                    $q->where('kode_prodi', $kode_prodi)
                      ->orWhereNull('kode_prodi');
                })
                ->get();
        }

        // Ambil kriteria CPMK dan pemetaan CPL (termasuk KKM) untuk prodi mahasiswa
        $cpmk_cpl = DB::table('akd_skripsi_cpmk_cpl as cc')
            ->join('akd_skripsi_rubrik_cpmk as r', 'cc.id_cpmk', '=', 'r.id')
            ->select('cc.kode_cpl', 'r.id as id_cpmk', 'r.kode_cpmk', 'r.nama_cpmk', 'r.kkm')
            ->where(function ($query) use ($kode_prodi, $hasCustom) {
                if ($hasCustom) {
                    $query->where('r.kode_prodi', $kode_prodi);
                } else {
                    $query->whereNull('r.kode_prodi');
                }
            })
            ->get();

        // Map CPL description in PHP
        foreach ($cpmk_cpl as $item) {
            $cpl_match = null;
            if ($active_cpls->isNotEmpty()) {
                // Prioritize prodi-specific CPL over global/null CPL
                $cpl_match = $active_cpls->where('kode_cpl', $item->kode_cpl)->where('kode_prodi', $kode_prodi)->first();
                if (!$cpl_match) {
                    $cpl_match = $active_cpls->where('kode_cpl', $item->kode_cpl)->first();
                }
            }
            $item->cpl_deskripsi = $cpl_match ? $cpl_match->deskripsi : '';
        }

        // Hitung rata-rata nilai per CPMK dari semua penguji/verifikator
        $cpmk_averages = [];
        $grouped_scores = $scores->groupBy('id_cpmk');
        foreach ($grouped_scores as $cpmk_id => $cpmk_scores) {
            $cpmk_averages[$cpmk_id] = $cpmk_scores->avg('nilai');
        }

        // Group by CPL dan hitung pencapaian
        $cpl_achievements = [];
        $cpl_groups = $cpmk_cpl->groupBy('kode_cpl');
        foreach ($cpl_groups as $cpl_code => $mappings) {
            $sum = 0;
            $count = 0;
            foreach ($mappings as $m) {
                if (isset($cpmk_averages[$m->id_cpmk])) {
                    $sum += $cpmk_averages[$m->id_cpmk];
                    $count++;
                }
            }
            if ($count > 0) {
                $achievement = round($sum / $count, 2);
                $cpl_achievements[] = [
                    'cpl' => $cpl_code,
                    'deskripsi' => $mappings->first()->cpl_deskripsi ?? '',
                    'achievement' => $achievement,
                    'status' => $achievement >= 70.00 ? 'Tercapai' : 'Perlu Penguatan'
                ];
            }
        }

        $cpmk_scores_formatted = [];
        foreach ($cpmk_averages as $cpmk_id => $avg) {
            $item = $cpmk_cpl->firstWhere('id_cpmk', $cpmk_id);
            $kkm = $item ? (float)$item->kkm : 70.00;
            $cpmk_scores_formatted[] = [
                'id_cpmk' => $cpmk_id,
                'kode_cpmk' => $item ? $item->kode_cpmk : '',
                'nama_cpmk' => $item ? $item->nama_cpmk : 'Kriteria ' . $cpmk_id,
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
                'cpl_portfolio' => $cpl_achievements,
                'cpmk_scores' => $cpmk_scores_formatted
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
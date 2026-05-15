<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Mskripsi extends Model
{
    /**
     * Hitung Kelayakan Mahasiswa berdasarkan Syarat Engine Dinamis
     */
    public function cekKelayakan($nim, $fase)
    {
        // 1. Profil Mahasiswa
        $mhs = DB::table('akd_mahasiswa')
            ->join('akd_program_studi', 'akd_mahasiswa.kode_program_studi', '=', 'akd_program_studi.kode_program_studi')
            ->select('akd_mahasiswa.nim', 'akd_mahasiswa.kode_program_studi', 'akd_program_studi.kode_jenjang_pendidikan', 'akd_mahasiswa.status_mhs')
            ->where('nim', $nim)->first();

        $skripsi = DB::table('akd_skripsi')->where('nim', $nim)->first();
        $flag_pkkmb = $this->getSkripsiFlag($skripsi, 'is_pkkmb');
        $flag_kkn = $this->getSkripsiFlag($skripsi, 'is_kkn');
        $flag_pkpm = $this->getSkripsiFlag($skripsi, 'is_pkpm');
        $min_bimbingan_ujian = 8;

        if (!$mhs) return ['error' => 'Mahasiswa tidak ditemukan'];

        // 2. Get Program Studi Configuration
        $prodiConfig = DB::table('akd_program_studi')
            ->where('kode_program_studi', $mhs->kode_program_studi)
            ->select('ta_sks_minimal', 'ta_ada_sempro', 'ta_minimal_bimbingan', 'ta_komponen_bayar', 'ta_komponen_bayar_ujian')
            ->first();

        // 3. Daftar Syarat from syarat_prodi table
        $syaratList = DB::table('akd_skripsi_syarat_prodi as p')
            ->join('akd_skripsi_syarat as s', 'p.kode_syarat', '=', 's.kode_syarat')
            ->where('p.kode_prodi', $mhs->kode_program_studi)
            ->where('p.fase', $fase)
            ->where('p.is_aktif', 1)
            ->orderBy('p.urutan', 'ASC')
            ->get();

        $hasil = [];
        $semua_lolos = true;

        // Prep Data Akademik
        $stats = $this->getAcademicStats($nim);
        $ipk = $stats['ipk'];
        $total_sks = $stats['total_sks'];
        $total_e = $stats['total_e'];
        $total_bimbingan_valid = 0;

        if (in_array($fase, ['sempro', 'ujian']) && $prodiConfig) {
            $total_bimbingan_valid = DB::table('akd_skripsi_bimbingan')
                ->where('nim', $nim)
                ->whereIn('status', ['disetujui', 'revisi'])
                ->count();
        }
        
        // If no syarat from table, create from prodi config
        if ($syaratList->isEmpty() && $prodiConfig) {
            $index = 1;
            
            // 1. IPK Minimal 2.00
            $ipk_minimal = 2.00;
            $is_ipk_lolos = $ipk >= $ipk_minimal;
            if (!$is_ipk_lolos) $semua_lolos = false;
            
            $hasil[] = [
                'no' => $index++,
                'id_syarat_prodi' => null,
                'syarat' => 'IPK Minimal 2.00',
                'isi' => number_format($ipk, 2) . ' / ' . $ipk_minimal . ' (IPK Anda / Minimal)',
                'hubungi' => 'Bagian Akademik',
                'status' => $is_ipk_lolos ? 'v' : 'x',
                'jenis' => 'sistem',
                'is_wajib' => 1,
                'tipe_upload' => null,
                'kode_syarat' => 'IPK_MIN'
            ];
            
            // 2. SKS requirement from prodi config
            if ($prodiConfig->ta_sks_minimal) {
                $is_terpenuhi = $total_sks >= $prodiConfig->ta_sks_minimal;
                if (!$is_terpenuhi) $semua_lolos = false;
                
                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Jumlah SKS Ditempuh',
                    'isi' => $total_sks . ' / ' . $prodiConfig->ta_sks_minimal . ' SKS',
                    'hubungi' => 'Bagian Akademik',
                    'status' => $is_terpenuhi ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'SKS_MIN'
                ];
            }
            
            // 3. Grade requirement (no D/E)
            $is_terpenuhi = $total_e == 0;
            if (!$is_terpenuhi) $semua_lolos = false;
            
            $hasil[] = [
                'no' => $index++,
                'id_syarat_prodi' => null,
                'syarat' => 'Bebas Nilai D/E',
                'isi' => $total_e . ' matakuliah dengan nilai D/E',
                'hubungi' => 'Bagian Akademik',
                'status' => $is_terpenuhi ? 'v' : 'x',
                'jenis' => 'sistem',
                'is_wajib' => 1,
                'tipe_upload' => null,
                'kode_syarat' => 'BEBAS_E'
            ];
            
            // 4. Bimbingan requirement for Seminar Proposal
            if ($fase == 'sempro' && $prodiConfig->ta_minimal_bimbingan) {
                $bimbingan_lolos = $total_bimbingan_valid >= $prodiConfig->ta_minimal_bimbingan;
                if (!$bimbingan_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Jumlah Log Bimbingan Tervalidasi',
                    'isi' => $total_bimbingan_valid . ' / ' . $prodiConfig->ta_minimal_bimbingan . ' log bimbingan tervalidasi (ACC/Revisi)',
                    'hubungi' => 'Dosen Pembimbing',
                    'status' => $bimbingan_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'BIMBINGAN_ACC'
                ];
            }

            // 5. Payment requirement based on phase
            if ($fase == 'sempro' && $prodiConfig->ta_komponen_bayar) {
                $bayar_ta = DB::table('keu_tagihan')
                    ->where('nim', $nim)
                    ->where('nama_biaya', 'like', '%' . $prodiConfig->ta_komponen_bayar . '%')
                    ->where('status', '1')
                    ->count() > 0;
                
                if (!$bayar_ta) $semua_lolos = false;
                
                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Pembayaran Biaya Seminar Proposal',
                    'isi' => $bayar_ta ? 'Sudah Lunas' : 'Belum Lunas',
                    'hubungi' => 'Bagian Keuangan',
                    'status' => $bayar_ta ? 'v' : 'x',
                    'jenis' => 'pembayaran',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'BAYAR_SEMPRO'
                ];
            }

            if ($fase == 'sempro') {
                $skema = $prodiConfig->ta_sempro_skema ?? 'skripsi';
                
                if ($skema == 'matakuliah') {
                    // Check if student has passed any of the mapped courses
                    $sempro_lulus = DB::table('akd_skripsi_sempro_mk as m')
                        ->join('akd_matakuliah as mk', 'm.id_matakuliah', '=', 'mk.id_matakuliah')
                        ->join('akd_penawaran_matakuliah as pm', 'mk.id_matakuliah', '=', 'pm.id_matakuliah')
                        ->join('akd_kelas_kuliah as kk', 'pm.id_tawar', '=', 'kk.id_tawar')
                        ->join('akd_detail_krs as dk', 'kk.id_kelas', '=', 'dk.id_kelas')
                        ->join('akd_krs as k', 'dk.id_krs', '=', 'k.id_krs')
                        ->join('akd_heregistrasi as h', 'k.id_heregistrasi', '=', 'h.id_heregistrasi')
                        ->where('h.nim', $nim)
                        ->where('m.kode_prodi', $mhs->kode_program_studi)
                        ->whereNotNull('dk.nilai_akhir_huruf')
                        ->whereNotIn('dk.nilai_akhir_huruf', ['', 'E', 'D', 'T'])
                        ->count() > 0;

                    $hasil[] = [
                        'no' => $index++,
                        'id_syarat_prodi' => null,
                        'syarat' => 'Lulus Mata Kuliah Seminar Proposal',
                        'isi' => $sempro_lulus ? 'Sudah Lulus' : 'Belum Lulus Mata Kuliah Terkait',
                        'hubungi' => 'Kaprodi',
                        'status' => $sempro_lulus ? 'v' : 'x',
                        'jenis' => 'sistem',
                        'is_wajib' => 1,
                        'tipe_upload' => null,
                        'kode_syarat' => 'ACC_SEMPRO'
                    ];
                    if (!$sempro_lulus) $semua_lolos = false;
                } else {
                    $sempro_acc = $skripsi && strtolower((string) ($skripsi->fase_aktif ?? '')) === 'sempro';
                    if (!$sempro_acc) $semua_lolos = false;

                    $hasil[] = [
                        'no' => $index++,
                        'id_syarat_prodi' => null,
                        'syarat' => 'ACC Sempro Pembimbing',
                        'isi' => $sempro_acc ? 'Sudah ACC' : 'Belum ACC',
                        'hubungi' => 'Dosen Pembimbing',
                        'status' => $sempro_acc ? 'v' : 'x',
                        'jenis' => 'sistem',
                        'is_wajib' => 1,
                        'tipe_upload' => null,
                        'kode_syarat' => 'ACC_SEMPRO'
                    ];
                }
            }

            if ($fase == 'ujian') {
                $pkkmb_lolos = $flag_pkkmb === 1;
                if (!$pkkmb_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Lulus PKKMB',
                    'isi' => $pkkmb_lolos ? 'Sudah Lulus' : 'Belum Lulus',
                    'hubungi' => 'Bagian Akademik',
                    'status' => $pkkmb_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'PKKMB'
                ];

                $kkn_lolos = $flag_kkn === 1;
                if (!$kkn_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Lulus KKN',
                    'isi' => $kkn_lolos ? 'Sudah Lulus' : 'Belum Lulus',
                    'hubungi' => 'Bagian Akademik',
                    'status' => $kkn_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'KKN'
                ];

                $pkpm_lolos = $flag_pkpm === 1;
                if (!$pkpm_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Lulus PKPM',
                    'isi' => $pkpm_lolos ? 'Sudah Lulus' : 'Belum Lulus',
                    'hubungi' => 'Bagian Akademik',
                    'status' => $pkpm_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'PKPM'
                ];

                $bimbingan_ujian_lolos = $total_bimbingan_valid >= $min_bimbingan_ujian;
                if (!$bimbingan_ujian_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Jumlah Log Bimbingan Tervalidasi',
                    'isi' => $total_bimbingan_valid . ' / ' . $min_bimbingan_ujian . ' log bimbingan tervalidasi (ACC/Revisi)',
                    'hubungi' => 'Dosen Pembimbing',
                    'status' => $bimbingan_ujian_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'BIMBINGAN_8X'
                ];
            }
            
            if ($fase == 'ujian' && $prodiConfig->ta_komponen_bayar_ujian) {
                $bayar_ujian = DB::table('keu_tagihan')
                    ->where('nim', $nim)
                    ->where('nama_biaya', 'like', '%' . $prodiConfig->ta_komponen_bayar_ujian . '%')
                    ->where('status', '1')
                    ->count() > 0;
                
                if (!$bayar_ujian) $semua_lolos = false;
                
                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Pembayaran Biaya Ujian Skripsi',
                    'isi' => $bayar_ujian ? 'Sudah Lunas' : 'Belum Lunas',
                    'hubungi' => 'Bagian Keuangan',
                    'status' => $bayar_ujian ? 'v' : 'x',
                    'jenis' => 'pembayaran',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'BAYAR_UJIAN'
                ];
            }

            // 6. Seminar Proposal Requirement (if enabled by prodi)
            if ($fase == 'ujian' && $prodiConfig->ta_ada_sempro != 0) {
                $sempro_lulus = DB::table('akd_skripsi_proposal as p')
                    ->join('akd_skripsi as s', 'p.id_skripsi', '=', 's.id')
                    ->where('s.nim', $nim)
                    ->where('p.status', 'lulus')
                    ->count() > 0;
                
                if (!$sempro_lulus) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Lulus Seminar Proposal',
                    'isi' => $sempro_lulus ? 'Sudah Lulus' : 'Belum Lulus / Belum Seminar',
                    'hubungi' => 'Kaprodi / Admin Skripsi',
                    'status' => $sempro_lulus ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'LULUS_SEMPRO'
                ];
            }
            
            return [
                'syarat_list' => $hasil,
                'semua_lolos' => $semua_lolos,
                'ipk_calc' => $ipk,
                'sks_calc' => $total_sks
            ];
        }

        $index = 1;
        foreach ($syaratList as $rule) {
            $is_terpenuhi = false;
            $isi_aktual = '-';
            $status_ikon = 'x'; 
            
            if ($rule->jenis == 'sistem') {
                if ($rule->kode_syarat == 'IPK_MIN') {
                    $isi_aktual = (string)$ipk;
                    $is_terpenuhi = $this->compareValue($ipk, $rule->operator, (float)$rule->nilai_target);
                } 
                else if ($rule->kode_syarat == 'SKS_MIN') {
                    $isi_aktual = $total_sks . " SKS";
                    $is_terpenuhi = $this->compareValue($total_sks, $rule->operator, (float)$rule->nilai_target);
                }
                else if ($rule->kode_syarat == 'BEBAS_E') {
                    $isi_aktual = $total_e . " Matakuliah";
                    $is_terpenuhi = $this->compareValue($total_e, $rule->operator, (float)$rule->nilai_target); 
                }
                else if ($rule->kode_syarat == 'STATUS_AKTIF') {
                    $isi_aktual = $mhs->status_mhs == 'A' ? 'Aktif' : 'Non-Aktif';
                    if($mhs->status_mhs == 'A') $is_terpenuhi = true;
                }
                else if ($rule->kode_syarat == 'LULUS_MK_TA') {
                    $has_ta = $this->checkHasTA($nim);
                    $isi_aktual = $has_ta > 0 ? "Sudah Diambil" : "Belum Diambil";
                    $is_terpenuhi = $has_ta > 0;
                }
                else if ($rule->kode_syarat == 'LULUS_SEMPRO') {
                    $sempro_lulus = DB::table('akd_skripsi_proposal as p')
                        ->join('akd_skripsi as s', 'p.id_skripsi', '=', 's.id')
                        ->where('s.nim', $nim)
                        ->where('p.status', 'lulus')
                        ->count() > 0;
                    $isi_aktual = $sempro_lulus ? "Sudah Lulus" : "Belum Lulus";
                    $is_terpenuhi = $sempro_lulus;
                }
                else if ($rule->kode_syarat == 'BIMBINGAN_ACC') {
                    $bimbingan_valid = DB::table('akd_skripsi_bimbingan')
                        ->where('nim', $nim)
                        ->whereIn('status', ['disetujui', 'revisi'])
                        ->count();
                    $target_bimbingan = is_numeric($rule->nilai_target) ? (float)$rule->nilai_target : ($fase == 'ujian' ? 8 : (float) ($prodiConfig->ta_minimal_bimbingan ?? 0));
                    if ($target_bimbingan <= 0) {
                        $target_bimbingan = ($fase == 'ujian') ? 8 : 1;
                    }
                    $operator = $rule->operator ?: '>=';
                    $isi_aktual = $bimbingan_valid . ' / ' . $target_bimbingan . ' Log Bimbingan Tervalidasi (ACC/Revisi)';
                    $is_terpenuhi = $this->compareValue($bimbingan_valid, $operator, $target_bimbingan);
                }
                else if ($rule->kode_syarat == 'BIMBINGAN_8X') {
                    $bimbingan_valid = DB::table('akd_skripsi_bimbingan')
                        ->where('nim', $nim)
                        ->whereIn('status', ['disetujui', 'revisi'])
                        ->count();
                    $isi_aktual = $bimbingan_valid . ' / 8 Log Bimbingan Tervalidasi (ACC/Revisi)';
                    $is_terpenuhi = $bimbingan_valid >= 8;
                }
                else if ($rule->kode_syarat == 'PKKMB') {
                    $target_flag = is_numeric($rule->nilai_target) ? (float)$rule->nilai_target : 1;
                    $operator = $rule->operator ?: '>=';
                    $isi_aktual = $flag_pkkmb == 1 ? 'Sudah Lulus' : 'Belum Lulus';
                    $is_terpenuhi = $this->compareValue($flag_pkkmb, $operator, $target_flag);
                }
                else if ($rule->kode_syarat == 'KKN') {
                    $target_flag = is_numeric($rule->nilai_target) ? (float)$rule->nilai_target : 1;
                    $operator = $rule->operator ?: '>=';
                    $isi_aktual = $flag_kkn == 1 ? 'Sudah Lulus' : 'Belum Lulus';
                    $is_terpenuhi = $this->compareValue($flag_kkn, $operator, $target_flag);
                }
                else if ($rule->kode_syarat == 'PKPM') {
                    $target_flag = is_numeric($rule->nilai_target) ? (float)$rule->nilai_target : 1;
                    $operator = $rule->operator ?: '>=';
                    $isi_aktual = $flag_pkpm == 1 ? 'Sudah Lulus' : 'Belum Lulus';
                    $is_terpenuhi = $this->compareValue($flag_pkpm, $operator, $target_flag);
                }
                else if ($rule->kode_syarat == 'ACC_SEMPRO') {
                    $skema = $prodiConfig->ta_sempro_skema ?? 'skripsi';
                    if ($skema == 'matakuliah') {
                        $is_terpenuhi = DB::table('akd_skripsi_sempro_mk as m')
                            ->join('akd_matakuliah as mk', 'm.id_matakuliah', '=', 'mk.id_matakuliah')
                            ->join('akd_penawaran_matakuliah as pm', 'mk.id_matakuliah', '=', 'pm.id_matakuliah')
                            ->join('akd_kelas_kuliah as kk', 'pm.id_tawar', '=', 'kk.id_tawar')
                            ->join('akd_detail_krs as dk', 'kk.id_kelas', '=', 'dk.id_kelas')
                            ->join('akd_krs as k', 'dk.id_krs', '=', 'k.id_krs')
                            ->join('akd_heregistrasi as h', 'k.id_heregistrasi', '=', 'h.id_heregistrasi')
                            ->where('h.nim', $nim)
                            ->where('m.kode_prodi', $mhs->kode_program_studi)
                            ->whereNotNull('dk.nilai_akhir_huruf')
                            ->whereNotIn('dk.nilai_akhir_huruf', ['', 'E', 'D', 'T'])
                            ->count() > 0;
                        $isi_aktual = $is_terpenuhi ? 'Sudah Lulus (Matakuliah)' : 'Belum Lulus Matakuliah Terkait';
                    } else {
                        $is_terpenuhi = $skripsi && strtolower((string) ($skripsi->fase_aktif ?? '')) === 'sempro';
                        $isi_aktual = $is_terpenuhi ? 'Sudah ACC' : 'Belum ACC';
                    }
                }

                $status_ikon = $is_terpenuhi ? 'v' : 'x';
            }
            else if ($rule->jenis == 'pembayaran') {
                // Use prodi config for payment component name if available
                if ($fase == 'sempro' && $prodiConfig->ta_komponen_bayar) {
                    $nama_biaya = $prodiConfig->ta_komponen_bayar;
                } else if ($fase == 'ujian' && $prodiConfig->ta_komponen_bayar_ujian) {
                    $nama_biaya = $prodiConfig->ta_komponen_bayar_ujian;
                } else {
                    $nama_biaya = $rule->nilai_target ?: ($fase == 'sempro' ? 'Bimbingan Skripsi' : 'Ujian Skripsi');
                }
                
                $cek_tagihan = DB::table('keu_tagihan')
                    ->where('nim', $nim)
                    ->where('nama_biaya', 'like', '%' . $nama_biaya . '%')
                    ->where('status', '1')
                    ->count();
                $isi_aktual = $cek_tagihan > 0 ? 'Sudah Lunas' : 'Belum Lunas';
                $is_terpenuhi = $cek_tagihan > 0;
                $status_ikon = $is_terpenuhi ? 'v' : 'x';
            }
            else if ($rule->jenis == 'berkas') {
                $cek_berkas = DB::table('akd_skripsi_berkas')
                    ->where('nim', $nim)
                    ->where('id_syarat_prodi', $rule->id)
                    ->where('fase', $fase)
                    ->first();
                
                if ($cek_berkas) {
                    $isi_aktual = "Sudah (" . ($cek_berkas->tipe == 'url' ? 'URL' : 'Berkas') . ")";
                    $is_terpenuhi = true;
                    $status_ikon = 'i';
                } else {
                    $isi_aktual = "Belum Ada Berkas";
                    $is_terpenuhi = false;
                    $status_ikon = 'x'; 
                }
            }

            if (!$is_terpenuhi && $rule->is_wajib == 1) {
                $semua_lolos = false;
            }

            $hasil[] = [
                'no' => $index++,
                'id_syarat_prodi' => $rule->id,
                'syarat' => $rule->nama_syarat,
                'isi' => $isi_aktual,
                'hubungi' => $rule->petugas_validasi,
                'status' => $status_ikon,
                'jenis' => $rule->jenis,
                'is_wajib' => $rule->is_wajib,
                'tipe_upload' => $rule->tipe_upload,
                'kode_syarat' => $rule->kode_syarat
            ];
        }

        if ($fase == 'ujian') {
            $kodeSyaratProdi = $syaratList->pluck('kode_syarat')->map(function ($kode) {
                return strtoupper((string) $kode);
            })->all();

            if (!in_array('PKKMB', $kodeSyaratProdi)) {
                $pkkmb_lolos = $flag_pkkmb === 1;
                if (!$pkkmb_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Lulus PKKMB',
                    'isi' => $pkkmb_lolos ? 'Sudah Lulus' : 'Belum Lulus',
                    'hubungi' => 'Bagian Akademik',
                    'status' => $pkkmb_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'PKKMB'
                ];
            }

            if (!in_array('KKN', $kodeSyaratProdi)) {
                $kkn_lolos = $flag_kkn === 1;
                if (!$kkn_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Lulus KKN',
                    'isi' => $kkn_lolos ? 'Sudah Lulus' : 'Belum Lulus',
                    'hubungi' => 'Bagian Akademik',
                    'status' => $kkn_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'KKN'
                ];
            }

            if (!in_array('PKPM', $kodeSyaratProdi)) {
                $pkpm_lolos = $flag_pkpm === 1;
                if (!$pkpm_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Lulus PKPM',
                    'isi' => $pkpm_lolos ? 'Sudah Lulus' : 'Belum Lulus',
                    'hubungi' => 'Bagian Akademik',
                    'status' => $pkpm_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'PKPM'
                ];
            }

            if (!in_array('BIMBINGAN_8X', $kodeSyaratProdi) && !in_array('BIMBINGAN_ACC', $kodeSyaratProdi)) {
                $bimbingan_ujian_lolos = $total_bimbingan_valid >= $min_bimbingan_ujian;
                if (!$bimbingan_ujian_lolos) $semua_lolos = false;

                $hasil[] = [
                    'no' => $index++,
                    'id_syarat_prodi' => null,
                    'syarat' => 'Jumlah Log Bimbingan Tervalidasi',
                    'isi' => $total_bimbingan_valid . ' / ' . $min_bimbingan_ujian . ' log bimbingan tervalidasi (ACC/Revisi)',
                    'hubungi' => 'Dosen Pembimbing',
                    'status' => $bimbingan_ujian_lolos ? 'v' : 'x',
                    'jenis' => 'sistem',
                    'is_wajib' => 1,
                    'tipe_upload' => null,
                    'kode_syarat' => 'BIMBINGAN_8X'
                ];
            }
        }

        return [
            'syarat_list' => $hasil,
            'semua_lolos' => $semua_lolos,
            'ipk_calc' => $ipk,
            'sks_calc' => $total_sks
        ];
    }

    /**
     * Data Lengkap untuk Dashboard Skripsi
     */
    public function getDashboard($nim)
    {
        // 1. Profil & Config Prodi
        $mhs = DB::table('akd_mahasiswa as m')
            ->leftJoin('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->select('m.nim', 'm.nama_mahasiswa', 'm.kode_program_studi', 'p.nama_program_studi', 'p.kode_jenjang_pendidikan',
                     'p.ta_sks_minimal', 'p.ta_ada_sempro', 'p.ta_sempro_skema', 'p.ta_minimal_bimbingan', 
                     'p.ta_komponen_bayar', 'p.ta_komponen_bayar_ujian', 'p.ta_nama_tugas_akhir')
            ->where('m.nim', $nim)->first();

        if (!$mhs) return ['error' => 'Data Mahasiswa atau Program Studi tidak sinkron.'];

        // 2. Stats Akademik
        $stats = $this->getAcademicStats($nim);

        $bayar_ta = true;
        if ($mhs->ta_komponen_bayar) {
            $unpaid = DB::table('keu_tagihan')
                ->where('nim', $nim)
                ->where('nama_biaya', 'like', '%' . $mhs->ta_komponen_bayar . '%')
                ->where('status', '0')
                ->count();
            
            if ($unpaid > 0) {
                $bayar_ta = false;
            } else {
                $paid = DB::table('keu_tagihan')
                    ->where('nim', $nim)
                    ->where('nama_biaya', 'like', '%' . $mhs->ta_komponen_bayar . '%')
                    ->where('status', '1')
                    ->count();
                if ($paid == 0) $bayar_ta = false;
            }
        }
        
        $bayar_ujian = true;
        if ($mhs->ta_komponen_bayar_ujian) {
            $unpaid_ujian = DB::table('keu_tagihan')
                ->where('nim', $nim)
                ->where('nama_biaya', 'like', '%' . $mhs->ta_komponen_bayar_ujian . '%')
                ->where('status', '0')
                ->count();
            
            if ($unpaid_ujian > 0) {
                $bayar_ujian = false;
            } else {
                $paid_ujian = DB::table('keu_tagihan')
                    ->where('nim', $nim)
                    ->where('nama_biaya', 'like', '%' . $mhs->ta_komponen_bayar_ujian . '%')
                    ->where('status', '1')
                    ->count();
                if ($paid_ujian == 0) $bayar_ujian = false;
            }
        }

        // 4. Data Skripsi Induk
        $skripsi = DB::table('akd_skripsi as s')
            ->leftJoin('simpeg_pegawai as d1', 's.id_dosen_pembimbing1', '=', 'd1.id')
            ->leftJoin('simpeg_pegawai as d2', 's.id_dosen_pembimbing2', '=', 'd2.id')
            ->select('s.*', 
                DB::raw("CONCAT_WS(' ', d1.gelar_depan, d1.nama, d1.gelar_belakang) as nama_pembimbing1"),
                DB::raw("CONCAT_WS(' ', d2.gelar_depan, d2.nama, d2.gelar_belakang) as nama_pembimbing2")
            )
            ->where('s.nim', $nim)->first();

        // 5. Data Sempro & Bimbingan
        $sempro = null;
        if ($mhs->ta_ada_sempro) {
            if (($mhs->ta_sempro_skema ?? 'skripsi') == 'matakuliah') {
                $is_lulus_mk = DB::table('akd_skripsi_sempro_mk as m')
                    ->join('akd_matakuliah as mk', 'm.id_matakuliah', '=', 'mk.id_matakuliah')
                    ->join('akd_penawaran_matakuliah as pm', 'mk.id_matakuliah', '=', 'pm.id_matakuliah')
                    ->join('akd_kelas_kuliah as kk', 'pm.id_tawar', '=', 'kk.id_tawar')
                    ->join('akd_detail_krs as dk', 'kk.id_kelas', '=', 'dk.id_kelas')
                    ->join('akd_krs as k', 'dk.id_krs', '=', 'k.id_krs')
                    ->join('akd_heregistrasi as h', 'k.id_heregistrasi', '=', 'h.id_heregistrasi')
                    ->where('h.nim', $nim)
                    ->where('m.kode_prodi', $mhs->kode_program_studi)
                    ->whereNotNull('dk.nilai_akhir_huruf')
                    ->whereNotIn('dk.nilai_akhir_huruf', ['', 'E', 'D', 'T'])
                    ->first();

                if ($is_lulus_mk) {
                    $sempro = (object)[
                        'status' => 'lulus',
                        'keterangan' => 'Lulus via Mata Kuliah: ' . $is_lulus_mk->nama_matakuliah,
                        'tanggal_sempro' => $is_lulus_mk->created_at ?? now()
                    ];
                }
            } else {
                $sempro = $skripsi ? DB::table('akd_skripsi_proposal')->where('id_skripsi', $skripsi->id)->where('nim', $nim)->orderBy('iterasi', 'desc')->first() : null;
            }
        }
        $total_bimbingan = $skripsi ? DB::table('akd_skripsi_bimbingan')->where('id_skripsi', $skripsi->id)->whereIn('status', ['disetujui', 'revisi'])->count() : 0;
        $ujian = $skripsi ? DB::table('akd_skripsi_ujian')->where('id_skripsi', $skripsi->id)->first() : null;

        // 6. Logika CTA
        $cta = $this->calculateCTA($mhs, $bayar_ta, $skripsi, $sempro, $total_bimbingan, $bayar_ujian, $ujian, $stats);

        return [
            'mhs' => [
                'nim' => $mhs->nim,
                'nama' => $mhs->nama_mahasiswa,
                'prodi' => $mhs->nama_program_studi,
                'jenjang' => $mhs->kode_jenjang_pendidikan
            ],
            'config' => [
                'nama_ta' => $mhs->ta_nama_tugas_akhir ?? 'Skripsi',
                'ada_sempro' => $mhs->ta_ada_sempro ?? 1,
                'sks_min' => $mhs->ta_sks_minimal ?? 110,
                'min_bimbingan' => $mhs->ta_minimal_bimbingan ?? 8
            ],
            'akademik' => $stats,
            'pembayaran' => [
               'ta_lunas' => $bayar_ta,
               'ujian_lunas' => $bayar_ujian
            ],
            'skripsi' => $skripsi,
            'sempro' => $sempro,
            'bimbingan' => [
                'total' => $total_bimbingan,
                'persen' => round(($total_bimbingan / (max(1, $mhs->ta_minimal_bimbingan))) * 100)
            ],
            'ujian' => $ujian,
            'cta' => $cta
        ];
    }

    private function getAcademicStats($nim)
    {
        $transkrip = collect(DB::select("
             SELECT mk.sks_matakuliah, MIN(akd_transkrip.nilai) as nilai, MAX(p.mutu) as mutu
            FROM akd_transkrip
            JOIN akd_matakuliah mk ON mk.id_matakuliah = akd_transkrip.id_matakuliah 
            JOIN akd_predikat_nilai_huruf p ON akd_transkrip.nilai = p.nilai_huruf_akhir 
            WHERE akd_transkrip.nim = '$nim'
            GROUP BY akd_transkrip.id_matakuliah
        "));

        $total_sks = $transkrip->sum('sks_matakuliah');
        $total_mutu = $transkrip->sum(function($item) {
            return $item->sks_matakuliah * $item->mutu;
        });
        
        return [
            'ipk' => $total_sks > 0 ? round($total_mutu / $total_sks, 2) : 0,
            'total_sks' => $total_sks,
            'total_e' => $transkrip->whereIn('nilai', ['D', 'E'])->count()
        ];
    }

    private function checkHasTA($nim)
    {
        return collect(DB::select("
            SELECT id_krs FROM akd_detail_krs dk
            JOIN akd_kelas_kuliah kk ON dk.id_kelas = kk.id_kelas
            JOIN akd_penawaran_matakuliah pm ON kk.id_tawar = pm.id_tawar
            JOIN akd_matakuliah mk ON pm.id_matakuliah = mk.id_matakuliah
            JOIN akd_krs k ON dk.id_krs = k.id_krs
            JOIN akd_heregistrasi h ON k.id_heregistrasi = h.id_heregistrasi
            WHERE h.nim = '$nim' AND (mk.nama_matakuliah LIKE '%Tugas Akhir%' OR mk.nama_matakuliah LIKE '%Skripsi%')
        "))->count();
    }

    private function calculateCTA($mhs, $bayar_ta, $skripsi, $sempro, $total_bimbingan, $bayar_ujian, $ujian, $stats)
    {
        $min_bimbingan_ujian = 8;

        if (!$bayar_ta) {
            return ['label' => 'Lunasi Pembayaran ' . $mhs->ta_nama_tugas_akhir, 'url' => 'mahasiswa/statuspembayaran', 'warna' => 'warning', 'disabled' => false];
        }

        if (!$skripsi) {
            // Cek SKS Minimal
            $sks_min = $mhs->ta_sks_minimal ?? 110;
            if ($stats['total_sks'] < $sks_min) {
                return ['label' => 'SKS Belum Mencukupi (Min. ' . $sks_min . ')', 'url' => '#', 'warna' => 'secondary', 'disabled' => true];
            }
            return ['label' => 'Mulai Pengajuan Proposal', 'url' => 'skripsi/dashboard#form-proposal', 'warna' => 'warning', 'disabled' => false];
        }

        if ($skripsi->status == 'draft' || $skripsi->status == 'menunggu_pembimbing') {
            return ['label' => 'Menunggu Ploting Pembimbing oleh Kaprodi', 'url' => '#', 'warna' => 'secondary', 'disabled' => true];
        }

        if ($skripsi->fase_aktif == 'bimbingan' && $total_bimbingan < $mhs->ta_minimal_bimbingan) {
            return ['label' => 'Tambah Log Bimbingan (' . $total_bimbingan . '/' . $mhs->ta_minimal_bimbingan . ')', 'url' => 'skripsi/bimbingan', 'warna' => 'warning', 'disabled' => false];
        }

        if ($mhs->ta_ada_sempro && $skripsi && strtolower((string) ($skripsi->fase_aktif ?? '')) !== 'sempro') {
            return ['label' => 'Menunggu ACC Sempro Pembimbing', 'url' => '#', 'warna' => 'secondary', 'disabled' => true];
        }

        if ($mhs->ta_ada_sempro && (!$sempro || $sempro->status == 'draft')) {
            return ['label' => 'Daftar Seminar Proposal', 'url' => 'skripsi/seminar', 'warna' => 'warning', 'disabled' => false];
        }

        if ($mhs->ta_ada_sempro && $sempro->status == 'diajukan') {
            return ['label' => 'Menunggu Jadwal & Validasi Sempro', 'url' => '#', 'warna' => 'secondary', 'disabled' => true];
        }

        if ($total_bimbingan >= $min_bimbingan_ujian && !$bayar_ujian) {
            return ['label' => 'Bimbingan Selesai! Lunasi Biaya Ujian', 'url' => 'mahasiswa/statuspembayaran', 'warna' => 'warning', 'disabled' => false];
        }

        if ($total_bimbingan >= $min_bimbingan_ujian && $bayar_ujian && (!$ujian || $ujian->status == 'pending')) {
            return ['label' => 'Daftar Ujian Sidang Akhir', 'url' => 'skripsi/ujian', 'warna' => 'warning', 'disabled' => false];
        }

        if ($ujian && $ujian->status == 'dijadwalkan') {
            return ['label' => 'Lihat Jadwal Ujian Sidang', 'url' => 'mahasiswa/skripsi/ujian', 'warna' => 'info', 'disabled' => false];
        }

        if ($ujian && $ujian->status == 'lulus') {
            return ['label' => 'Selamat! Menuju Yudisium 🎓', 'url' => '#', 'warna' => 'success', 'disabled' => true];
        }

        return ['label' => 'Cek Progress TA', 'url' => 'skripsi/dashboard', 'warna' => 'warning', 'disabled' => false];
    }

    private function compareValue($source, $op, $target) {
        switch ($op) {
            case '>=': return $source >= $target;
            case '<=': return $source <= $target;
            case '=': return $source == $target;
            case '<': return $source < $target;
            case '>': return $source > $target;
            default: return false;
        }
    }

    private function getSkripsiFlag($skripsi, $field)
    {
        if (!$skripsi || !is_object($skripsi) || !property_exists($skripsi, $field)) {
            return 0;
        }

        return (int) $skripsi->{$field} === 1 ? 1 : 0;
    }

    /**
     * Data Mahasiswa Bimbingan untuk Dosen
     */
    public function getMahasiswaBimbingan($id_dosen, $tahun = null, $semester = null)
    {
        $query = DB::table('akd_skripsi as s')
            ->join('akd_mahasiswa as m', 's.nim', '=', 'm.nim')
            ->join('akd_program_studi as p', 'm.kode_program_studi', '=', 'p.kode_program_studi')
            ->select(
                's.id as id_skripsi',
                's.nim',
                'm.nama_mahasiswa',
                'p.nama_program_studi',
                'p.ta_minimal_bimbingan as ta_minimal_bimbingan',
                's.judul',
                's.topik',
                's.status as status_skripsi',
                's.fase_aktif',
                's.id_dosen_pembimbing1',
                's.id_dosen_pembimbing2',
                DB::raw("(SELECT COUNT(id) FROM akd_skripsi_bimbingan b WHERE b.id_skripsi = s.id AND b.status IN ('disetujui', 'revisi')) as total_bimbingan_acc")
            )
            ->where(function ($query) use ($id_dosen) {
                $query->where('s.id_dosen_pembimbing1', $id_dosen)
                    ->orWhere('s.id_dosen_pembimbing2', $id_dosen);
            })
            ->whereNotIn('s.status', ['draft', 'menunggu_pembimbing']);

        if ($tahun && $semester) {
            $query->join('akd_heregistrasi as r', function($join) use ($tahun, $semester) {
                $join->on('m.nim', '=', 'r.nim')
                     ->where('r.tahun', '=', $tahun)
                     ->where('r.semester', '=', $semester);
            });
        }

        return $query->orderBy('s.updated_at', 'desc')->get();
    }

    /**
     * Log Bimbingan untuk divalidasi Dosen
     */
    public function getLogBimbinganDosen($id_skripsi, $id_dosen)
    {
        // Hanya memunculkan bimbingan yang dikirimkan ke dosen tersebut (jika ada kolom target_dosen)
        // Jika tidak ada pembagian, semua dosen pembimbing bisa acc.
        // Kita asumsikan Dosen 1 dan Dosen 2 bisa melihat semua log mahasiswa tersebut.
        $logs = DB::table('akd_skripsi_bimbingan')
            ->where('id_skripsi', $id_skripsi)
            // ->where('dosen_tujuan', $id_dosen) // Uncomment jika skema memiliki target dosen
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $logs;
    }
}


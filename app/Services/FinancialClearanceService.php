<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FinancialClearanceService
{
    /**
     * Check financial clearance for a student on a specific academic activity.
     *
     * @param string $nim
     * @param string $kegiatan (e.g. 'perpanjangan_studi', 'pendaftaran_wisuda')
     * @param string|null $tahun
     * @param string|null $semester
     * @return array
     */
    public static function checkClearance($nim, $kegiatan = 'perpanjangan_studi', $tahun = null, $semester = null)
    {
        $mhs = DB::table('akd_mahasiswa as m')
            ->leftJoin('akd_program_studi as ps', 'm.kode_program_studi', '=', 'ps.kode_program_studi')
            ->where('m.nim', $nim)
            ->select('m.*', 'ps.nama_program_studi', 'ps.kode_jenjang_pendidikan as prodi_jenjang')
            ->first();

        if (!$mhs) {
            return [
                'status' => 'error',
                'message' => 'Mahasiswa tidak ditemukan',
                'is_lunas' => false,
                'rincian' => []
            ];
        }

        $jenjang = 'S1';
        if ($mhs->prodi_jenjang == 1 || 
            strpos(strtolower($mhs->nama_program_studi), 'd3') !== false || 
            strpos(strtolower($mhs->nama_program_studi), 'diploma') !== false || 
            in_array($mhs->kode_program_studi, ['PT10', 'PH07'])) {
            $jenjang = 'D3';
        }

        $angkatan = $mhs->tahun_angkatan;

        // Fetch matched rules from keu_syarat_kelayakan
        $rules = DB::table('keu_syarat_kelayakan')
            ->where('kegiatan', $kegiatan)
            ->where('is_aktif', 1)
            ->where(function($q) use ($jenjang) {
                $q->where('jenjang', $jenjang)->orWhereNull('jenjang');
            })
            ->where(function($q) use ($angkatan) {
                $q->where('angkatan', $angkatan)->orWhereNull('angkatan');
            })
            ->where(function($q) use ($mhs) {
                $q->where('kode_prodi', $mhs->kode_program_studi)->orWhereNull('kode_prodi');
            })
            ->orderByRaw("CASE WHEN kode_prodi IS NOT NULL THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN angkatan IS NOT NULL THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN jenjang IS NOT NULL THEN 1 ELSE 2 END")
            ->get();

        // Check full scholarship & dispensation
        $hasFullScholarship = DB::table('keu_beasiswa_mahasiswa as bm')
            ->join('keu_sumber_beasiswa as s', 'bm.id_sumber_beasiswa', '=', 's.id_sumber_beasiswa')
            ->where('bm.nim', $nim)
            ->where('bm.status_aktif', 1)
            ->where('s.jenis_beasiswa', 'full')
            ->exists();

        $cekta = DB::table('akd_mreg')->where('trash', '1')->first();
        $hasDispensasi = false;
        if ($cekta) {
            $hasDispensasi = DB::table('akd_dispensasi')
                ->where('nim', $nim)
                ->where('tahun', $tahun ?: $cekta->tahun)
                ->where('semester', $semester ?: $cekta->semester)
                ->whereIn('jenis', ['SKRIPSI', 'TA', 'TUGAS AKHIR', 'PERPANJANGAN'])
                ->exists();
        }

        $semua_lunas = true;
        $rincian = [];

        foreach ($rules as $r) {
            if ($hasFullScholarship || $hasDispensasi) {
                $rincian[] = [
                    'kode_komponen' => $r->kode_komponen,
                    'label' => $r->nama_komponen_label,
                    'status' => 'lunas',
                    'is_lunas' => true,
                    'tunggakan' => 0,
                    'keterangan' => $hasFullScholarship ? 'Lunas (Beasiswa Penuh)' : 'Lunas (Dispensasi)'
                ];
                continue;
            }

            // Check component specific scholarship
            $hasCompScholarship = DB::table('keu_beasiswa_mahasiswa as bm')
                ->join('keu_beasiswa_cakupan as bc', 'bm.id_sumber_beasiswa', '=', 'bc.id_sumber_beasiswa')
                ->where('bm.nim', $nim)
                ->where('bm.status_aktif', 1)
                ->where('bc.persentase_potongan', 100.00)
                ->where('bc.kode_komponen', $r->kode_komponen)
                ->exists();

            if ($hasCompScholarship) {
                $rincian[] = [
                    'kode_komponen' => $r->kode_komponen,
                    'label' => $r->nama_komponen_label,
                    'status' => 'lunas',
                    'is_lunas' => true,
                    'tunggakan' => 0,
                    'keterangan' => 'Lunas (Beasiswa Komponen)'
                ];
                continue;
            }

            // Query unpaid and paid bills in keu_tagihan
            $unpaidQuery = DB::table('keu_tagihan')
                ->where('nim', $nim)
                ->where('kode_komponen', $r->kode_komponen)
                ->where('status', '0');

            $paidQuery = DB::table('keu_tagihan')
                ->where('nim', $nim)
                ->where('kode_komponen', $r->kode_komponen)
                ->where('status', '1');

            $unpaid = $unpaidQuery->get();
            $paid = $paidQuery->get();

            $is_comp_lunas = $unpaid->isEmpty() && $paid->isNotEmpty();
            if (!$is_comp_lunas && $r->is_wajib) {
                $semua_lunas = false;
            }

            $totalTunggakan = $unpaid->sum('biaya');
            $keterangan = '';
            if ($unpaid->isNotEmpty()) {
                $keterangan = "Menunggak {$unpaid->count()} tagihan (Rp " . number_format($totalTunggakan, 0, ',', '.') . ")";
            } else if ($paid->isNotEmpty()) {
                $keterangan = "Sudah Lunas";
            } else {
                $keterangan = "Belum Ada Tagihan Terbit";
                // If required component has no bill generated yet, treat as not fulfilled
                if ($r->is_wajib) {
                    $semua_lunas = false;
                    $is_comp_lunas = false;
                }
            }

            $rincian[] = [
                'kode_komponen' => $r->kode_komponen,
                'label' => $r->nama_komponen_label,
                'status' => $is_comp_lunas ? 'lunas' : 'belum_lunas',
                'is_lunas' => $is_comp_lunas,
                'is_wajib' => (bool)$r->is_wajib,
                'tunggakan' => $totalTunggakan,
                'keterangan' => $keterangan
            ];
        }

        return [
            'status' => 'success',
            'nim' => $nim,
            'kegiatan' => $kegiatan,
            'jenjang' => $jenjang,
            'angkatan' => $angkatan,
            'is_lunas' => $semua_lunas,
            'rincian' => $rincian
        ];
    }
}

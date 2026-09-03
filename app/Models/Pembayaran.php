<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class Pembayaran extends Model
{
    use HasFactory;

    /**
     * Update nim pada keu_virtual_akun dengan kode virtual akun baru (10 digit)
     * Menerima kode_biling yang dipisah dengan strip (-) dan convert ke array
     * Semua kode_biling yang dipilih akan di-update dengan kode VA yang sama
     * 
     * @param Request $request
     * @return mixed
     */
    public function updateByKodeBiling(Request $request)
    {
        // Ambil kode_biling dari request (format: "kode1-kode2-kode3")
        $kodeBilingString = $request->kode_biling ?? $request->kodejamak ?? '';
        
        if (empty($kodeBilingString)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode billing tidak boleh kosong'
            ], 400);
        }

        // Convert string dengan separator strip (-) menjadi array
        $kodeBilingArray = explode('-', $kodeBilingString);
        
        // Filter array untuk menghilangkan nilai kosong
        $kodeBilingArray = array_filter($kodeBilingArray, function($kode) {
            return !empty(trim($kode));
        });
        
        // Re-index array
        $kodeBilingArray = array_values($kodeBilingArray);

        if (empty($kodeBilingArray)) {
            return response()->json([
                'success' => false,
                'message' => 'Array kode billing kosong setelah di-filter'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Generate kode virtual akun baru 10 digit
            $kodeVABaru = $this->generateKodeVirtualAkun();

            // Update keu_virtual_akun: SET nim = kode VA baru WHERE kode IN (kode_biling_array)
            $updatedCount = DB::table('keu_virtual_akun')
                ->whereIn('kode', $kodeBilingArray)
                ->update([
                    'nim' => $kodeVABaru
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Virtual akun berhasil di-update',
                'data' => [
                    'kode_virtual_akun_baru' => $kodeVABaru,
                    'kode_biling_array' => $kodeBilingArray,
                    'total_updated' => $updatedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal meng-update virtual akun: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Virtual Account on-demand for student
     * Supports 3 Banks: BNI, Bank Mega Syariah (BMS), Bank Jateng Syariah (BJS)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateVa(Request $request)
    {
        $nim = $request->nim;
        $bank = strtolower($request->bank ?? 'bni'); // bni | bms | bjt
        $idTagihan = $request->id_tagihan;

        if (empty($nim)) {
            return response()->json([
                'success' => false,
                'message' => 'NIM mahasiswa tidak boleh kosong.'
            ], 400);
        }

        // Parse id_tagihan from array or comma/dash separated string
        $idTagihanArray = [];
        if (is_array($idTagihan)) {
            $idTagihanArray = array_filter(array_map('intval', $idTagihan));
        } elseif (is_string($idTagihan) && !empty($idTagihan)) {
            $delimiter = strpos($idTagihan, ',') !== false ? ',' : '-';
            $idTagihanArray = array_filter(array_map('intval', explode($delimiter, $idTagihan)));
        } elseif (!empty($request->kodejamak)) {
            // Backward compatibility: resolve id_tagihan from kodejamak (kode_biling)
            $codes = explode('-', $request->kodejamak);
            $idTagihanArray = DB::table('keu_tagihan')
                ->whereIn('kode_biling', $codes)
                ->where('nim', $nim)
                ->where('status', 0)
                ->pluck('id_tagihan')
                ->toArray();
        }

        if (empty($idTagihanArray)) {
            return response()->json([
                'success' => false,
                'message' => 'Pilih minimal satu tagihan yang akan dibayar.'
            ], 400);
        }

        // Fetch student & unpaid bills
        $bills = DB::table('keu_tagihan')
            ->whereIn('id_tagihan', $idTagihanArray)
            ->where('nim', $nim)
            ->where('status', 0)
            ->get();

        if ($bills->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan atau sudah berstatus lunas.'
            ], 404);
        }

        // Fetch student profile details
        $mhs = DB::table('akd_mahasiswa as m')
            ->join('akd_program_studi as ps', 'm.kode_program_studi', '=', 'ps.kode_program_studi')
            ->leftJoin('akd_fakultas as f', 'ps.kode_fakultas', '=', 'f.kode_fakultas')
            ->where('m.nim', $nim)
            ->select('m.nim', 'm.nama_mahasiswa', 'm.tahun_angkatan', 'm.telp', 'ps.nama_program_studi', 'f.nama_fakultas')
            ->first();

        if (!$mhs) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil mahasiswa tidak ditemukan.'
            ], 404);
        }

        // Calculate total amount and combined description
        $totalAmount = 0;
        $namaTagihan = [];
        $firstBill = $bills->first();

        foreach ($bills as $b) {
            $sudahBayar = DB::table('keu_bayar')->where('id_tagihan', $b->id_tagihan)->sum('bayar') ?: 0;
            $sisa = (int)$b->biaya - (int)$sudahBayar;
            $totalAmount += ($sisa > 0 ? $sisa : (int)$b->biaya);
            $namaTagihan[] = $b->nama_biaya;
        }

        $namaTagihanGabungan = implode(', ', array_unique($namaTagihan));
        if (strlen($namaTagihanGabungan) > 240) {
            $namaTagihanGabungan = substr($namaTagihanGabungan, 0, 237) . '...';
        }

        // Determine Bank-Specific VA Rules
        $cleanNumericNim = preg_replace('/[^0-9]/', '', $nim);
        $bankName = '';
        $accountID = '';
        $nomorVA = '';

        if ($bank === 'bni') {
            // BNI eCollection: 16 digits (988 + 22601 + 8 digits random numeric)
            $bankName = 'Bank BNI (Bank Negara Indonesia)';
            $accountID = 'BNI';
            do {
                $rand8 = str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $nomorVA = '98822601' . $rand8;
                $exists = DB::table('keu_virtual_akun')->where('nim', $nomorVA)->exists();
            } while ($exists);

        } elseif ($bank === 'bms') {
            // Bank Mega Syariah: Month (2) + Year (2) + 7 digit numeric NIM
            $bankName = 'Bank Mega Syariah';
            $accountID = 'BMS';
            $nomorVA = date('m') . date('y') . substr($cleanNumericNim, -7);

        } elseif ($bank === 'bjt') {
            // Bank Jateng Syariah: 7 digit numerik terakhir NIM
            $bankName = 'Bank Jateng Syariah';
            $accountID = 'BJT';
            $nomorVA = substr($cleanNumericNim, -7);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Pilihan bank tidak valid. Pilih BNI, BMS, atau BJT.'
            ], 400);
        }

        // Generate 10-digit random billing code
        do {
            $kodeBiling = strval(mt_rand(1000000000, 9999999999));
            $exists = DB::table('keu_virtual_akun')->where('kode', $kodeBiling)->exists();
        } while ($exists);

        DB::beginTransaction();
        try {
            // Check & delete old unpaid virtual accounts for these bills if any
            $oldCodes = $bills->pluck('kode_biling')->filter()->unique()->toArray();
            if (!empty($oldCodes)) {
                DB::table('keu_virtual_akun')->whereIn('kode', $oldCodes)->delete();
            }

            // Insert new virtual account
            $taLabel = $firstBill->tahun ? ($firstBill->tahun . '/' . ((int)$firstBill->tahun + 1)) : date('Y') . '/' . (date('Y') + 1);

            DB::table('keu_virtual_akun')->insert([
                'kode' => $kodeBiling,
                'nim' => $nomorVA,
                'nama' => $mhs->nama_mahasiswa,
                'jurusan' => $mhs->nama_program_studi,
                'fakultas' => $mhs->nama_fakultas ?? '-',
                'ta' => $taLabel,
                'telp' => $mhs->telp ?? '-',
                'tagihan' => $namaTagihanGabungan,
                'nominal' => $totalAmount,
                'account_id' => $accountID,
                'created_at' => now(),
            ]);

            // Update keu_tagihan with new billing code
            DB::table('keu_tagihan')
                ->whereIn('id_tagihan', $bills->pluck('id_tagihan')->toArray())
                ->update(['kode_biling' => $kodeBiling]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Nomor Virtual Account ' . $bankName . ' berhasil dibuat.',
                'data' => [
                    'bank' => $bank,
                    'bank_code' => $accountID,
                    'bank_name' => $bankName,
                    'nomor_va' => $nomorVA,
                    'total_nominal' => $totalAmount,
                    'kode_biling' => $kodeBiling,
                    'nama_mahasiswa' => $mhs->nama_mahasiswa,
                    'nama_tagihan' => $namaTagihanGabungan,
                    'item_count' => $bills->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat Virtual Account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate kode virtual akun 10 digit (numeric) yang unik
     * 
     * @return string
     */
    private function generateKodeVirtualAkun()
    {
        do {
            // Generate 10 digit random number
            $kodeVA = str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
            
            // Cek apakah kode sudah ada di database (cek di kolom nim)
            $exists = DB::table('keu_virtual_akun')
                ->where('nim', $kodeVA)
                ->exists();
        } while ($exists);

        return $kodeVA;
    }

}


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


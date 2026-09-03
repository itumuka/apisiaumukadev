<?php

namespace App\Services\Payment\Drivers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BniDriver extends BaseDriver
{
    /**
     * Generate BNI eCollection 16-digit Virtual Account
     */
    public function generateVaNumber($mhs, $bills, $bankConfig): string
    {
        $prefix = $bankConfig->prefix_va ?: '98822601';
        $totalLength = (int)($bankConfig->panjang_va ?: 16);
        $randomLength = max(1, $totalLength - strlen($prefix));

        $min = (int)pow(10, $randomLength - 1);
        $max = (int)pow(10, $randomLength) - 1;

        do {
            $randomDigits = (string)mt_rand($min, $max);
            $vaNumber = $prefix . $randomDigits;
            $exists = DB::table('keu_virtual_akun')->where('nim', $vaNumber)->exists();
        } while ($exists);

        return $vaNumber;
    }

    /**
     * Daftarkan nomor billing ke API server BNI (eCollection)
     */
    public function registerToBankServer($billingData, $bankConfig): array
    {
        $endpoint = $bankConfig->api_endpoint;
        if (empty($endpoint)) {
            // Jika endpoint tidak dikonfigurasi, gunakan fallback siap inquiry
            return [
                'success' => true,
                'message' => 'Virtual Account BNI siap di database.'
            ];
        }

        try {
            // Panggil service internal BNI eCollection
            $response = Http::timeout(5)->post(rtrim($endpoint, '/') . '/api/create-va', [
                'trx_id' => $billingData['kode_biling'],
                'amount' => $billingData['nominal'],
                'customer_name' => $billingData['nama'],
                'customer_phone' => $billingData['telp'] ?? '',
                'va' => $billingData['nomor_va'],
                'exp_hours' => 72 // 3 hari default
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Berhasil mendaftarkan VA ke BNI eCollection.',
                    'raw' => $response->json()
                ];
            } else {
                Log::warning('BNI Service API returned non-200: ' . $response->body());
                // Tetap izinkan jika service lokal belum live agar mahasiswa tidak macet
                return [
                    'success' => true,
                    'message' => 'VA BNI tersimpan di database lokal (Service response: ' . $response->status() . ').'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Gagal menghubungi BNI API Gateway: ' . $e->getMessage());
            // Fallback graceful jika microservice internal sedang offline
            return [
                'success' => true,
                'message' => 'VA BNI tersimpan di database lokal (Gateway offline mode).'
            ];
        }
    }
}

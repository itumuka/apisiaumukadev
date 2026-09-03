<?php

namespace App\Services\Payment\Drivers;

use Illuminate\Support\Facades\DB;

class GenericDriver extends BaseDriver
{
    /**
     * Generate Virtual Account secara dinamis berdasarkan konfigurasi skema_va
     */
    public function generateVaNumber($mhs, $bills, $bankConfig): string
    {
        $skema = strtoupper($bankConfig->skema_va);
        $prefix = $bankConfig->prefix_va ?: '';
        $cleanNim = $this->cleanDigits($mhs->nim);
        $totalLength = (int)($bankConfig->panjang_va ?: 16);

        switch ($skema) {
            case 'PREFIX_NIM':
                // Prefix Bank + NIM
                return $prefix . $cleanNim;

            case 'SUBSTR_NIM':
                // N-digit terakhir NIM
                return substr($cleanNim, -$totalLength);

            case 'DATE_NIM':
                // Bulan (2) + Tahun (2) + 7 digit NIM
                return date('m') . date('y') . substr($cleanNim, -7);

            case 'PREFIX_RANDOM':
            default:
                $randomLen = max(1, $totalLength - strlen($prefix));
                $min = (int)pow(10, $randomLen - 1);
                $max = (int)pow(10, $randomLen) - 1;

                do {
                    $rand = (string)mt_rand($min, $max);
                    $va = $prefix . $rand;
                    $exists = DB::table('keu_virtual_akun')->where('nim', $va)->exists();
                } while ($exists);

                return $va;
        }
    }
}

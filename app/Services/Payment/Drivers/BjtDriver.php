<?php

namespace App\Services\Payment\Drivers;

class BjtDriver extends BaseDriver
{
    /**
     * Generate Bank Jateng Syariah Virtual Account
     * Format: 7 Digit Numerik Terakhir NIM Mahasiswa
     */
    public function generateVaNumber($mhs, $bills, $bankConfig): string
    {
        $cleanNim = $this->cleanDigits($mhs->nim);
        $panjang = (int)($bankConfig->panjang_va ?: 7);
        return substr($cleanNim, -$panjang);
    }
}

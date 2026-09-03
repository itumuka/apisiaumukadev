<?php

namespace App\Services\Payment\Drivers;

class BmsDriver extends BaseDriver
{
    /**
     * Generate Bank Mega Syariah Virtual Account
     * Format: Month (2) + Year (2) + 7 Digit Numerik NIM
     */
    public function generateVaNumber($mhs, $bills, $bankConfig): string
    {
        $cleanNim = $this->cleanDigits($mhs->nim);
        $last7 = substr($cleanNim, -7);
        return date('m') . date('y') . $last7;
    }
}

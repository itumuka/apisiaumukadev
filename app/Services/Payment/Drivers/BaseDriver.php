<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\BankDriverInterface;
use Illuminate\Support\Facades\DB;

abstract class BaseDriver implements BankDriverInterface
{
    /**
     * Helper untuk mengekstrak hanya digit angka dari string
     */
    protected function cleanDigits(string $str): string
    {
        return preg_replace('/[^0-9]/', '', $str);
    }

    /**
     * Default implementation untuk PUSH jika tidak meng-override
     */
    public function registerToBankServer($billingData, $bankConfig): array
    {
        // Default return success untuk tipe PULL atau jika tidak memerlukan external push
        return [
            'success' => true,
            'message' => 'Lokal billing siap (PULL/Inquiry ready).'
        ];
    }
}

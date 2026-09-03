<?php

namespace App\Services\Payment;

interface BankDriverInterface
{
    /**
     * Generate Nomor Virtual Account berdasarkan data mahasiswa dan tagihan
     *
     * @param object $mhs Data profil mahasiswa
     * @param array $bills Koleksi tagihan yang dipilih
     * @param object $bankConfig Data konfigurasi bank dari keu_bank_h2h
     * @return string Nomor VA yang valid
     */
    public function generateVaNumber($mhs, $bills, $bankConfig): string;

    /**
     * Daftarkan nomor VA ke server bank jika tipe_integrasi == 'PUSH'
     *
     * @param array $billingData
     * @param object $bankConfig
     * @return array ['success' => bool, 'message' => string, 'raw' => mixed]
     */
    public function registerToBankServer($billingData, $bankConfig): array;
}

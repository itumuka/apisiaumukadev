<?php

namespace App\Services\Payment;

use App\Services\Payment\Drivers\BniDriver;
use App\Services\Payment\Drivers\BmsDriver;
use App\Services\Payment\Drivers\BjtDriver;
use App\Services\Payment\Drivers\GenericDriver;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentManager
{
    /**
     * Ambil seluruh bank yang berstatus aktif untuk pilihan pembayaran mahasiswa
     */
    public function getActiveBanks()
    {
        return DB::table('keu_bank_h2h')
            ->where('is_active', 1)
            ->orderBy('urutan', 'asc')
            ->get();
    }

    /**
     * Ambil konfigurasi bank berdasarkan kode bank
     */
    public function getBankConfig(string $kodeBank)
    {
        return DB::table('keu_bank_h2h')
            ->where('kode_bank', strtoupper(trim($kodeBank)))
            ->first();
    }

    /**
     * Instansiasi driver class untuk bank bersangkutan
     */
    public function resolveDriver($bankConfig): BankDriverInterface
    {
        $driverClass = $bankConfig->driver_class;

        if ($driverClass && class_exists($driverClass)) {
            return new $driverClass();
        }

        // Fallback default berdasarkan kode_bank
        switch (strtoupper($bankConfig->kode_bank)) {
            case 'BNI':
                return new BniDriver();
            case 'BMS':
                return new BmsDriver();
            case 'BJT':
                return new BjtDriver();
            default:
                return new GenericDriver();
        }
    }

    /**
     * Eksekusi pembuatan Virtual Account lengkap dengan PULL/PUSH
     */
    public function createVirtualAccount($mhs, $bills, $bankConfig): array
    {
        if (!$bankConfig || !$bankConfig->is_active) {
            throw new Exception('Metode pembayaran bank ini sedang tidak aktif atau tidak ditemukan.');
        }

        $driver = $this->resolveDriver($bankConfig);

        // 1. Generate nomor VA menggunakan driver
        $nomorVa = $driver->generateVaNumber($mhs, $bills, $bankConfig);

        // 2. Generate kode billing unik 10 digit
        do {
            $kodeBiling = strval(mt_rand(1000000000, 9999999999));
            $exists = DB::table('keu_virtual_akun')->where('kode', $kodeBiling)->exists();
        } while ($exists);

        // 3. Hitung total nominal & nama tagihan
        $totalNominal = 0;
        $namaTagihanList = [];
        $firstBill = $bills[0];

        foreach ($bills as $b) {
            $sudahBayar = DB::table('keu_bayar')->where('id_tagihan', $b->id_tagihan)->sum('bayar') ?: 0;
            $sisa = (int)$b->biaya - (int)$sudahBayar;
            $totalNominal += ($sisa > 0 ? $sisa : (int)$b->biaya);
            $namaTagihanList[] = $b->nama_biaya;
        }

        // Tambah biaya admin jika ada
        $biayaAdmin = (int)($bankConfig->biaya_admin ?: 0);
        $grandTotal = $totalNominal + $biayaAdmin;

        $namaTagihanGabungan = implode(', ', array_unique($namaTagihanList));
        if (strlen($namaTagihanGabungan) > 240) {
            $namaTagihanGabungan = substr($namaTagihanGabungan, 0, 237) . '...';
        }

        $billingData = [
            'kode_biling' => $kodeBiling,
            'nomor_va' => $nomorVa,
            'nama' => $mhs->nama_mahasiswa,
            'jurusan' => $mhs->nama_program_studi,
            'fakultas' => $mhs->nama_fakultas ?? '-',
            'ta' => $firstBill->tahun ? ($firstBill->tahun . '/' . ((int)$firstBill->tahun + 1)) : date('Y') . '/' . (date('Y') + 1),
            'telp' => $mhs->telp ?? '-',
            'tagihan' => $namaTagihanGabungan,
            'nominal' => $grandTotal,
            'account_id' => $bankConfig->kode_bank,
        ];

        // 4. Jika tipe PUSH, tembak API server bank terlebih dahulu
        if (strtoupper($bankConfig->tipe_integrasi) === 'PUSH') {
            $pushResult = $driver->registerToBankServer($billingData, $bankConfig);
            if (!$pushResult['success']) {
                throw new Exception('Gagal mendaftarkan VA ke server ' . $bankConfig->nama_bank . ': ' . $pushResult['message']);
            }
        }

        // 5. Simpan transaksi ke database lokal
        DB::beginTransaction();
        try {
            // Hapus VA lama yang belum terbayar untuk tagihan-tagihan ini jika ada
            $oldBillingCodes = array_filter(array_unique(array_map(function ($b) {
                return $b->kode_biling;
            }, $bills)));

            if (!empty($oldBillingCodes)) {
                DB::table('keu_virtual_akun')->whereIn('kode', $oldBillingCodes)->delete();
            }

            // Insert keu_virtual_akun
            DB::table('keu_virtual_akun')->insert([
                'kode' => $billingData['kode_biling'],
                'nim' => $billingData['nomor_va'],
                'nama' => $billingData['nama'],
                'jurusan' => $billingData['jurusan'],
                'fakultas' => $billingData['fakultas'],
                'ta' => $billingData['ta'],
                'telp' => $billingData['telp'],
                'tagihan' => $billingData['tagihan'],
                'nominal' => $billingData['nominal'],
                'account_id' => $billingData['account_id'],
                'created_at' => now(),
            ]);

            // Update keu_tagihan dengan kode_biling baru
            $idTagihans = array_map(function ($b) {
                return $b->id_tagihan;
            }, $bills);

            DB::table('keu_tagihan')
                ->whereIn('id_tagihan', $idTagihans)
                ->update(['kode_biling' => $billingData['kode_biling']]);

            DB::commit();

            // Format panduan pembayaran dengan nomor VA
            $panduanMbanking = str_replace('{NOMOR_VA}', $nomorVa, $bankConfig->panduan_mbanking ?? '');
            $panduanAtm = str_replace('{NOMOR_VA}', $nomorVa, $bankConfig->panduan_atm ?? '');
            $panduanAntarbank = str_replace('{NOMOR_VA}', $nomorVa, $bankConfig->panduan_antarbank ?? '');

            return [
                'bank' => strtolower($bankConfig->kode_bank),
                'bank_code' => $bankConfig->kode_bank,
                'bank_name' => $bankConfig->nama_bank,
                'nomor_va' => $nomorVa,
                'total_nominal' => $grandTotal,
                'biaya_admin' => $biayaAdmin,
                'kode_biling' => $kodeBiling,
                'nama_mahasiswa' => $mhs->nama_mahasiswa,
                'nama_tagihan' => $namaTagihanGabungan,
                'item_count' => count($bills),
                'panduan' => [
                    'mbanking' => $panduanMbanking,
                    'atm' => $panduanAtm,
                    'antarbank' => $panduanAntarbank,
                ]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

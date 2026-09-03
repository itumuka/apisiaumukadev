<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pembayaran;

class PembayaranController extends Controller
{
    protected $pembayaran;

    public function __construct()
    {
        $this->pembayaran = new Pembayaran();
    }

    /**
     * Update record berdasarkan kode_biling yang dipilih
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateByKodeBiling(Request $request)
    {
        $result = $this->pembayaran->updateByKodeBiling($request);
        return $result;
    }

    /**
     * Generate on-demand multi-bank virtual account for student
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateVa(Request $request)
    {
        return $this->pembayaran->generateVa($request);
    }
}


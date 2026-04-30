<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\DB;

class NilaiUTSImport implements ToCollection, WithStartRow
{

    // private $table;
    // public function __construct($table)
    // {
    //     $this->table = $table;
    // }
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            DB::table('akd_detail_krs')
            ->where('id_detail_krs', $row[4])
            ->update([
                'nilai_uts'  =>  $row[3],
                'dtime_update'  =>  date('Y-m-d H:i:s')
            ]);

        }
    }

    public function startRow(): int
    {
        return 7;
    }
}

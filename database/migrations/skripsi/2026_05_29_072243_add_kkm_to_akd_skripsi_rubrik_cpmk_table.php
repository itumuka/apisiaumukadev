<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKkmToAkdSkripsiRubrikCpmkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi_rubrik_cpmk', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi_rubrik_cpmk', 'kkm')) {
                $table->decimal('kkm', 5, 2)->default(70.00)->after('bobot');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_skripsi_rubrik_cpmk', function (Blueprint $table) {
            if (Schema::hasColumn('akd_skripsi_rubrik_cpmk', 'kkm')) {
                $table->dropColumn('kkm');
            }
        });
    }
}

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsObeToAkdMatakuliahTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_matakuliah', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_matakuliah', 'is_obe')) {
                $table->tinyInteger('is_obe')->default(1)->comment('1=OBE (CPMK), 0=Non-OBE (Direct Score)')->after('kode_bayar');
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
        Schema::table('akd_matakuliah', function (Blueprint $table) {
            if (Schema::hasColumn('akd_matakuliah', 'is_obe')) {
                $table->dropColumn('is_obe');
            }
        });
    }
}

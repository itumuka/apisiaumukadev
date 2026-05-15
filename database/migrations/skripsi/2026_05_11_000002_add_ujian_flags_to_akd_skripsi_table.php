<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUjianFlagsToAkdSkripsiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi', 'is_pkkmb')) {
                $table->tinyInteger('is_pkkmb')->default(0)->comment('Flag kelulusan PKKMB: 0=belum, 1=sudah')->after('status');
            }

            if (!Schema::hasColumn('akd_skripsi', 'is_kkn')) {
                $table->tinyInteger('is_kkn')->default(0)->comment('Flag kelulusan KKN: 0=belum, 1=sudah')->after('is_pkkmb');
            }

            if (!Schema::hasColumn('akd_skripsi', 'is_pkpm')) {
                $table->tinyInteger('is_pkpm')->default(0)->comment('Flag kelulusan PKPM: 0=belum, 1=sudah')->after('is_kkn');
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
        Schema::table('akd_skripsi', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('akd_skripsi', 'is_pkkmb')) {
                $dropColumns[] = 'is_pkkmb';
            }

            if (Schema::hasColumn('akd_skripsi', 'is_kkn')) {
                $dropColumns[] = 'is_kkn';
            }

            if (Schema::hasColumn('akd_skripsi', 'is_pkpm')) {
                $dropColumns[] = 'is_pkpm';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
}

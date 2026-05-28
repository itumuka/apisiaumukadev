<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTargetLuaranToAkdSkripsiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi', 'target_luaran')) {
                $table->string('target_luaran', 50)->nullable()->default('buku_skripsi')->after('status');
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
            if (Schema::hasColumn('akd_skripsi', 'target_luaran')) {
                $table->dropColumn('target_luaran');
            }
        });
    }
}

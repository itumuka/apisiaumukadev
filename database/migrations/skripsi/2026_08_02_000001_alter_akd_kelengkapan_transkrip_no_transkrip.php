<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAkdKelengkapanTranskripNoTranskrip extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_kelengkapan_transkrip', function (Blueprint $table) {
            $table->string('no_transkrip', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_kelengkapan_transkrip', function (Blueprint $table) {
            $table->string('no_transkrip', 15)->nullable()->change();
        });
    }
}

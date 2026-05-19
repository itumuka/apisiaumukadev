<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidationStatusToProdi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('akd_program_studi')) {
            Schema::table('akd_program_studi', function (Blueprint $table) {
                if (!Schema::hasColumn('akd_program_studi', 'ta_sempro_is_validated')) {
                    $table->tinyInteger('ta_sempro_is_validated')
                          ->default(1)
                          ->comment('1=Validated/Approved, 0=Pending/Draft');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('akd_program_studi')) {
            Schema::table('akd_program_studi', function (Blueprint $table) {
                if (Schema::hasColumn('akd_program_studi', 'ta_sempro_is_validated')) {
                    $table->dropColumn('ta_sempro_is_validated');
                }
            });
        }
    }
}

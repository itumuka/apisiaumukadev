<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidIdToSkripsiTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi', 'valid_id_kaprodi')) {
                $table->string('valid_id_kaprodi', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('akd_skripsi', 'valid_id_pembimbing1')) {
                $table->string('valid_id_pembimbing1', 100)->nullable()->after('valid_id_kaprodi');
            }
            if (!Schema::hasColumn('akd_skripsi', 'valid_id_pembimbing2')) {
                $table->string('valid_id_pembimbing2', 100)->nullable()->after('valid_id_pembimbing1');
            }
        });

        Schema::table('akd_skripsi_bimbingan', function (Blueprint $table) {
            if (!Schema::hasColumn('akd_skripsi_bimbingan', 'valid_id')) {
                $table->string('valid_id', 100)->nullable()->after('catatan_dosen');
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
            if (Schema::hasColumn('akd_skripsi', 'valid_id_kaprodi')) {
                $table->dropColumn('valid_id_kaprodi');
            }
            if (Schema::hasColumn('akd_skripsi', 'valid_id_pembimbing1')) {
                $table->dropColumn('valid_id_pembimbing1');
            }
            if (Schema::hasColumn('akd_skripsi', 'valid_id_pembimbing2')) {
                $table->dropColumn('valid_id_pembimbing2');
            }
        });

        Schema::table('akd_skripsi_bimbingan', function (Blueprint $table) {
            if (Schema::hasColumn('akd_skripsi_bimbingan', 'valid_id')) {
                $table->dropColumn('valid_id');
            }
        });
    }
}

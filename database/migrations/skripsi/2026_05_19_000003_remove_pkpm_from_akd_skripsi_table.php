<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Remove PKPM module from system
     * PKPM has been replaced by KKN module; is_pkpm column no longer needed
     */
    public function up(): void
    {
        if (Schema::hasTable('akd_skripsi')) {
            Schema::table('akd_skripsi', function (Blueprint $table) {
                if (Schema::hasColumn('akd_skripsi', 'is_pkpm')) {
                    $table->dropColumn('is_pkpm');
                }
            });
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        if (Schema::hasTable('akd_skripsi')) {
            Schema::table('akd_skripsi', function (Blueprint $table) {
                if (!Schema::hasColumn('akd_skripsi', 'is_pkpm')) {
                    $table->tinyInteger('is_pkpm')->default(0)->comment('Flag kelulusan PKPM: 0=belum, 1=sudah')->after('is_kkn');
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterAkdSkripsiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('akd_skripsi', function (Blueprint $table) {
            $table->string('topik', 255)->nullable()->after('nim');
            $table->string('topik_en', 255)->nullable()->after('topik');
            $table->text('judul_en')->nullable()->after('judul');
            $table->text('abstrak')->nullable()->after('judul_en');
            $table->text('abstrak_en')->nullable()->after('abstrak');
            $table->date('tanggal_pengajuan')->nullable()->after('abstrak_en');
            
            // Modify existing columns
            // Using raw SQL for enum modification as Blueprint change() can be tricky with enums in some DBs
        });

        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN fase_aktif ENUM('proposal','sempro','bimbingan','ujian') DEFAULT 'proposal'");
        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN status ENUM('draft','menunggu_pembimbing','aktif','lulus','tidak_lulus') DEFAULT 'draft'");
        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN id_dosen_pembimbing1 INT NULL");
        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN id_dosen_pembimbing2 INT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('akd_skripsi', function (Blueprint $table) {
            $table->dropColumn([
                'topik', 
                'topik_en', 
                'judul_en', 
                'abstrak', 
                'abstrak_en', 
                'tanggal_pengajuan'
            ]);
        });
        
        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN fase_aktif ENUM('sempro','bimbingan','ujian') DEFAULT 'sempro'");
        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN status ENUM('draft','aktif','lulus','tidak_lulus') DEFAULT 'draft'");
        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN id_dosen_pembimbing1 VARCHAR(20) NULL");
        DB::statement("ALTER TABLE akd_skripsi MODIFY COLUMN id_dosen_pembimbing2 VARCHAR(20) NULL");
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAkdSkripsiSkDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('akd_skripsi_sk_detail', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('id_sk')->index();
            $table->integer('id_skripsi')->index();
            $table->timestamps();
        });

        // Optional: migrate existing relationships from akd_skripsi to the new table
        // to maintain backward compatibility for existing SK data.
        DB::statement('
            INSERT INTO akd_skripsi_sk_detail (id_sk, id_skripsi, created_at, updated_at)
            SELECT id_sk_pembimbing, id, NOW(), NOW() 
            FROM akd_skripsi 
            WHERE id_sk_pembimbing IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('akd_skripsi_sk_detail');
    }
}

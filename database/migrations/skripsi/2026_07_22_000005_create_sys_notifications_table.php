<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSysNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sys_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 50)->index(); // Target user (nim/nidn/username)
            $table->string('title', 100);             // Notification title
            $table->text('message');                  // Notification message
            $table->string('type', 30);               // Category (e.g. skripsi, krs, dll)
            $table->string('target_url')->nullable(); // Action URL
            $table->boolean('is_read')->default(false); // Read status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sys_notifications');
    }
}

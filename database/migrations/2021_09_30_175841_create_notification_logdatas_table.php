<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationLogdatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_logdatas', function (Blueprint $table) {
            $table->id();
            $table->string('sendToUser')->nullable();
            $table->string('device_token')->nullable();
            $table->string('title')->nullable();
            $table->string('body')->nullable();
            $table->string('data')->nullable();
            $table->longText('curl_response')->nullable();
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
        Schema::dropIfExists('notification_logdatas');
    }
}

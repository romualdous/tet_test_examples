<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caller_id')->index();
            $table->unsignedBigInteger('listener_id')->nullable()->index();
            $table->unsignedBigInteger('topic_id')->nullable()->index();
            $table->string('channel');
            $table->string('token');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration')->nullable()->default(0);
            $table->enum('status', ['requested', 'cancelled', 'finished', 'on-going']);
            $table->timestamps();

            $table->foreign('caller_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('listener_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}

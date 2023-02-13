<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('reviewer_id');
            $table->unsignedBigInteger('recipient_id');
            $table->boolean('feels_better')->nullable();
            $table->boolean('would_talk_again')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('reviewer_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('recipient_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->unique(['conversation_id', 'recipient_id']);
            $table->unique(['conversation_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}

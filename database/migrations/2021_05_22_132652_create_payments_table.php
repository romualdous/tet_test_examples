<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('users');
            $table->string('charge_id');
            $table->string('payment_intent')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('currency');
            $table->enum('status', ['succeeded', 'failed']);
            $table->timestamps();

            $table->unique(['payer_id', 'charge_id', 'payment_intent']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}

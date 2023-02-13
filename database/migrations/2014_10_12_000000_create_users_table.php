<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('photo')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number')->unique()->nullable();
            $table->string('gender')->nullable();
            $table->enum('type', ['customer', 'listener', 'both']);
            $table->float('rating', 8, 1, true)->nullable();
            $table->enum('status', ['online', 'on-call', 'offline'])->default('offline');
            $table->text('bio')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_code')->nullable();
            $table->string('device_token_customer')->nullable();
            $table->string('device_token_listener')->nullable();
            $table->rememberToken();
            $table->softDeletes();

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
        Schema::dropIfExists('users');
    }
}

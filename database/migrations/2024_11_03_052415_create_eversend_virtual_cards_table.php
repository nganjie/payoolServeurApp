<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eversend_virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('card_id')->nullable();
            //$table->string('firstName');
            //$table->string('lastName');
            //$table->string('email')->unique();
            //$table->string('phone');
            $table->string('country', 2); // 'country' avec le code pays sur 2 caractères
            $table->string('state');
            $table->string('city');
            $table->string('address');
            $table->string('zipCode');
            $table->string('idType'); // Type d’identification
            $table->string('idNumber');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('eversend_virtual_cards');
    }
};

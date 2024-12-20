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
            $table->string('security_code');
            $table->string('expiration');
            $table->string('currency', 3); // Code devise (ISO)
            $table->string('status');
            $table->boolean('is_physical');
            $table->string('title');
            $table->string('color');
            $table->string('name');
            $table->decimal('amount', 15, 2); // Montant avec 2 décimales
            //$table->string('card_id')->unique(); // Identifiant unique pour la carte
            $table->string('brand');
            $table->string('mask');
            $table->string('number');
            $table->string('owner_id');
            $table->boolean('is_non_subscription');
            $table->string('last_used_on')->nullable();
            $table->json('billing_address')->nullable(); 
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

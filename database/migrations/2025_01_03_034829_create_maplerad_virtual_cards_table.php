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
        Schema::create('maplerad_virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('card_id')->nullable();
            $table->string('name');
            $table->string('card_number');
            $table->string('masked_pan');
            $table->string('expiry');
            $table->string('cvv');
            $table->string('status');
            $table->string('type');
            $table->string('issuer');
            $table->string('currency');
            $table->decimal('balance', 15, 2);
            $table->boolean('auto_approve');
            $table->json('address')->nullable(); 
            $table->boolean('is_default')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_penalize')->default(false);
            $table->integer('nb_trx_failed')->default(0);
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
        Schema::dropIfExists('maplerad_virtual_cards');
    }
};

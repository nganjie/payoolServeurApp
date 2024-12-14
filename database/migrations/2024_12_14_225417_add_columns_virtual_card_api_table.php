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
        Schema::table('virtual_card_apis', function (Blueprint $table) {
            $table->integer('nb_trx_failled')->default(2);
            $table->decimal('penality_price',8,2,true)->default(0);
            $table->boolean('is_activate_penality')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('virtual_card_apis', function (Blueprint $table) {
            $table->dropColumn('nb_trx_failled');
            $table->dropColumn('penality_price');
            $table->dropColumn('is_activate_penality');
        });
    }
};

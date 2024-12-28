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
        Schema::table('strowallet_virtual_cards', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_penalize')->default(false);
            $table->integer('nb_trx_failed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('strowallet_virtual_cards', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
            $table->dropColumn('is_penalize');
            $table->dropColumn('nb_trx_failed');
        });
    }
};

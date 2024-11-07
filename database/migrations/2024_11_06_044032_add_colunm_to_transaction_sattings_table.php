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
        Schema::table('transaction_settings', function (Blueprint $table) {
            $table->double('fixed_final_charge')->nullable(true);
            $table->double('fixed_month_charge')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_settings', function (Blueprint $table) {
            $table->dropColumn('fixed_final_charge');
            $table->dropColumn('fixed_month_charge');
        });
    }
};

<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\TransactionSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'admin_id'          => 1,
                'slug'              => "transfer-money",
                'title'             => "Transfer Money Charge",
                'fixed_charge'       => 1,
                'percent_charge'     => 1,
                'min_limit'         => 10,
                'max_limit'         => 1000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],
            [
                'admin_id'          => 1,
                'slug'              => "virtual_card",
                'title'             => "Virtual Card Charges",
                'fixed_charge'      => 2,
                'percent_charge'    => 1,
                'min_limit'         => 100,
                'max_limit'         => 50000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],
            [
                'admin_id'          => 1,
                'slug'              => "reload_card",
                'title'             => "Card Reload Charges",
                'fixed_charge'      => 2,
                'percent_charge'    => 1,
                'min_limit'         => 100,
                'max_limit'         => 50000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],
            [
                'admin_id'          => 1,
                'slug'              => "reload_card_soleaspay",
                'title'             => "Card Reload Charges",
                'fixed_charge'      => 2,
                'percent_charge'    => 1,
                'min_limit'         => 100,
                'max_limit'         => 50000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],
            [
                'admin_id'          => 1,
                'slug'              => "reload_card_eversend",
                'title'             => "Card Reload Charges",
                'fixed_charge'      => 2,
                'percent_charge'    => 1,
                'min_limit'         => 100,
                'max_limit'         => 50000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],
            [
                'admin_id'          => 1,
                'slug'              => "gift_card",
                'title'             => "Gift Card Charges",
                'fixed_charge'      => 1,
                'percent_charge'    => 1,
                'min_limit'         => 2,
                'max_limit'         => 50000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],
            [
                'admin_id'          => 1,
                'slug'              => "withdraw_card_soleaspay",
                'title'             => "Virtual Card Money withdraw Charges",
                'fixed_charge'      => 2,
                'percent_charge'    => 1,
                'min_limit'         => 100,
                'max_limit'         => 50000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],
            [
                'admin_id'          => 1,
                'slug'              => "withdraw_card_eversend",
                'title'             => "Virtual Card Money withdraw Charges",
                'fixed_charge'      => 2,
                'percent_charge'    => 1,
                'min_limit'         => 100,
                'max_limit'         => 50000,
                'monthly_limit'     => 0,
                'daily_limit'       => 0,
            ],

        ];
        TransactionSetting::insert($data);
    }
}

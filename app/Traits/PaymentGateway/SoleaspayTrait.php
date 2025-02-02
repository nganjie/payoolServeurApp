<?php

namespace App\Traits\PaymentGateway;

use Exception;
use Illuminate\Support\Str;
use App\Models\TemporaryData;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Constants\NotificationConst;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Constants\PaymentGatewayConst;
use App\Models\Admin\AdminNotification;
use App\Models\Admin\BasicSettings;
use App\Notifications\User\AddMoney\ApprovedMail;
use Illuminate\Support\Facades\Config;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Session;

trait SoleaspayTrait
{
    public function soleaspayInit($output = null)
    {
        if(!$output) $output = $this->output;

        $credentials = $this->getSoleaspayCredentials($output);

        $reference = $this->generateSoleaspayReference();

        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;
        $amount =round($amount);
        //dd($amount);

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }

        $success_url = route('user.add.money.soleaspay.success');
        $fails_url = route('user.add.money.soleaspay.fails');
        $basic_setting = BasicSettings::first();
        

        $data = [
            'shopName'      => $basic_setting->site_name,
            'area'          =>$output['currency']['currency_code'] ?? "USD",
            'amount'        => $amount,
            'email'         => $user_email,
            'orderId'     => $reference,
            'description' => 'Add money',
            'apiKey'      => $credentials->public_key,
            'currency'      => $output['currency']['currency_code'] ?? "USD",
            'successUrl'  => $success_url,
            'failureUrl'   => $fails_url,
            'customer'      => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
            'line' => "UP"
        ];
        //dd($data);

        $payment = $this->initializeSoleaspayPayment($data);

        if($payment['success'] == false) {
            throw new Exception($payment['message']);
        }

        $this->soleaspayJunkInsert($data);

        if ($payment['success'] == false) {
            return;
        }
        //dd($payment);

        return redirect($payment['link']);
    }

    public function soleaspayJunkInsert($response)
    {
        $output = $this->output;
        $user = auth()->guard(get_auth_guard())->user();
        $creator_table = $creator_id = $wallet_table = $wallet_id = null;

        $creator_table = auth()->guard(get_auth_guard())->user()->getTable();
        $creator_id = auth()->guard(get_auth_guard())->user()->id;
        $wallet_table = $output['wallet']->getTable();
        $wallet_id = $output['wallet']->id;

        $data = [
            'gateway'      => $output['gateway']->id,
            'currency'     => $output['currency']->id,
            'amount'       => json_decode(json_encode($output['amount']), true),
            'response'     => $response,
            'wallet_table'  => $wallet_table,
            'wallet_id'     => $wallet_id,
            'creator_table' => $creator_table,
            'creator_id'    => $creator_id,
            'creator_guard' => get_auth_guard(),
        ];

        Session::put('identifier', $response['orderId']);
        Session::put('output', $output);

        return TemporaryData::create([
            'type'          => PaymentGatewayConst::SOLEASPAY,
            'identifier'    => $response['orderId'],
            'data'          => $data,
        ]);
    }

    public function getSoleaspayCredentials($output)
    {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));

        $public_key = $gateway->credentials[0]->value ?? '';
        //$secret_key =  $gateway->credentials[0]->value ?? '';

        return (object) [
            'public_key' => $public_key,
            //'secret_key' => $secret_key,
        ];
    }

    public function generateSoleaspayReference()
    {
        $basic_setting = BasicSettings::first();
        return $basic_setting->site_name . uniqid();
    }

    public function initializeSoleaspayPayment($data)
    {
        // Implement the actual API call to Soleaspay here
        // For demonstration, we'll mock a response
        $payment = Http::post(
            'https://checkout.soleaspay.com?mode=api',
            $data
        ); 
        return $payment;
    }

    public function soleaspaySuccess($output = null)
    {
        if(!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception(__('Transaction failed. Record didn\'t saved properly. Please try again'));
        return $this->createTransactionSoleaspay($output);
    }

    public function createTransactionSoleaspay($output)
    {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordSoleaspay($output, $trx_id);
        $this->insertChargesSoleaspay($output, $inserted_id);
        $this->insertDeviceSoleaspay($output, $inserted_id);
        $this->removeTempDataSoleaspay($output);

        if($this->requestIsApiUser()) {
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }

        if($basic_setting->email_notification == true){
            $user->notify(new ApprovedMail($user, $output, $trx_id));
        }
    }

    public function updateWalletBalanceSoleaspay($output)
    {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    public function insertRecordSoleaspay($output, $trx_id)
    {
        $token = $this->output['tempData']['identifier'] ?? "";
        DB::beginTransaction();
        try {
            if(Auth::guard(get_auth_guard())->check()){
                $user_id = auth()->guard(get_auth_guard())->user()->id;
            }

            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user_id,
                'user_wallet_id'                => $output['wallet']->id,
                'payment_gateway_currency_id'   => $output['currency']->id,
                'type'                          => $output['type'],
                'trx_id'                        => $trx_id,
                'request_amount'                => $output['amount']->requested_amount,
                'payable'                       => $output['amount']->total_amount,
                'available_balance'             => $output['wallet']->balance + $output['amount']->requested_amount,
                'remark'                        => ucwords(remove_speacial_char($output['type'], " ")) . " With " . $output['gateway']->name,
                'details'                       => 'Soleaspay Payment Successful',
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateWalletBalanceSoleaspay($output);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again'));
        }
        return $id;
    }

    public function insertChargesSoleaspay($output, $id)
    {
        if(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }
        DB::beginTransaction();
        try {
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $output['amount']->percent_charge,
                'fixed_charge'      => $output['amount']->fixed_charge,
                'total_charge'      => $output['amount']->total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            $notification_content = [
                'title'         => "Add Money",
                'message'       => "Your Wallet"." (".$output['wallet']->currency->code.")  "."balance has been added"." ".$output['amount']->requested_amount.' '. $output['wallet']->currency->code,
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => get_image($user->image, 'user-profile'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again'));
        }
    }

    public function insertDeviceSoleaspay($output, $id)
    {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();

        DB::beginTransaction();
        try {
            DB::table("transaction_devices")->insert([
                'transaction_id' => $id,
                'ip'            => $client_ip,
                'mac'           => '',
                'city'          => $location['city'] ?? "",
                'country'       => $location['country'] ?? "",
                'longitude'     => $location['lon'] ?? "",
                'latitude'      => $location['lat'] ?? "",
                'timezone'      => $location['timezone'] ?? "",
                'browser'       => $agent->browser() ?? "",
                'os'            => $agent->platform() ?? "",
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again'));
        }
    }

    public function removeTempDataSoleaspay($output)
    {
        TemporaryData::where("identifier", $output['tempData']['identifier'])->delete();
    }

    // ********* For API **********
    public function soleaspayInitApi($output = null)
    {
        if(!$output) $output = $this->output;
        $credentials = $this->getSoleaspayCredentials($output);

        $reference = $this->generateSoleaspayReference();

        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        $success_url = route('user.add.money.soleaspay.success', "r-source=".PaymentGatewayConst::APP);
        $fails_url = route('user.add.money.soleaspay.fails', "r-source=".PaymentGatewayConst::APP);
        $basic_setting = BasicSettings::first();

        $data = [
            'shopName'      => $basic_setting->site_name,
            'area'          => $output['currency']['currency_code'] ?? "USD",
            'amount'        => $amount,
            'email'         => $user_email,
            'orderId'     => $reference,
            'description' => 'Add money',
            'apiKey'      => $credentials->public_key,
            'currency'      => $output['currency']['currency_code'] ?? "USD",
            'successUrl'  => $success_url,
            'failureUrl'   => $fails_url,
            'customer'      => [
                'email'        => $user_email,
                "phone_number" => $user_phone,
                "name"         => $user_name
            ],
        ];

        $payment = $this->initializeSoleaspayPayment($data, $output);
        $data['link'] = $payment['link'];
        $data['trx'] = $data['orderId'];

        $this->soleaspayJunkInsert($data);

        if ($payment['success'] == false) {
            return;
        }

        return $data;
    }
}

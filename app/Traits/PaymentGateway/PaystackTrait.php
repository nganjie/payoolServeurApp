<?php

namespace App\Traits\PaymentGateway;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\Api\Helpers;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\BasicSettings;
use App\Models\TemporaryData;
use App\Models\UserNotification;
use App\Notifications\User\AddMoney\ApprovedMail;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Str;
use App\Traits\AdminNotifications\AddMoneyNotifications;


trait PaystackTrait
{
    use AddMoneyNotifications;

    public function paystackInit($output = null) {
        if(!$output) $output = $this->output;
        $credentials = $this->getPaystackCredentials($output);
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;
        $currency = $output['currency']['currency_code']??"USD";

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
        }

        $return_url = route('user.add.money.payment.callback.response');

        $url = "https://api.paystack.co/transaction/initialize";

        $fields             = [
            'email'         => $user_email,
            'amount'        => get_amount($amount, null, 2) * 100,
            'currency'        => $currency,
            'callback_url'  => $return_url
        ];

        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $credentials->secret_key",
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        $response   = json_decode($result);
        if($response->status == true) {
            $this->paystackJunkInsert($response,$response->data->reference,$credentials);
            return redirect($response->data->authorization_url)->with('output',$output);
        }else{
            throw new Exception($response->message??" "."Something Is Wrong, Please Contact With Owner");
        }

    }

    public function getPaystackCredentials($output) {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));
        $public_key_sample = ['public_key','Public Key','public-key'];
        $secret_key_sample = ['secret_key','Secret Key','secret-key'];

        $public_key = '';
        $outer_break = false;
        foreach($public_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->paystackPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paystackPlainText($label);

                if($label == $modify_item) {
                    $public_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }


        $secret_key = '';
        $outer_break = false;
        foreach($secret_key_sample as $item) {
            if($outer_break == true) {
                break;
            }
            $modify_item = $this->paystackPlainText($item);
            foreach($gateway->credentials ?? [] as $gatewayInput) {
                $label = $gatewayInput->label ?? "";
                $label = $this->paystackPlainText($label);

                if($label == $modify_item) {
                    $secret_key = $gatewayInput->value ?? "";
                    $outer_break = true;
                    break;
                }
            }
        }



        $mode = $gateway->env;

        $paypal_register_mode = [
            PaymentGatewayConst::ENV_SANDBOX => "sandbox",
            PaymentGatewayConst::ENV_PRODUCTION => "live",
        ];
        if(array_key_exists($mode,$paypal_register_mode)) {
            $mode = $paypal_register_mode[$mode];
        }else {
            $mode = "sandbox";
        }

        return (object) [
            'public_key'     => $public_key,
            'secret_key' => $secret_key,
            'mode'          => $mode,

        ];

    }

    public function paystackPlainText($string) {
        $string = Str::lower($string);
        return preg_replace("/[^A-Za-z0-9]/","",$string);
    }

    public function paystackJunkInsert($response,$temp_record_token,$credentials) {
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
                'amount'       => json_decode(json_encode($output['amount']),true),
               'response'      => $response,
                'credentials'   => $credentials,
                'wallet_id'     => $wallet_id,
                'creator_table' => $creator_table,
                'creator_id'    => $creator_id,
                'creator_guard' => get_auth_guard(),
            ];

        return TemporaryData::create([
            'type'          => PaymentGatewayConst::PAYSTACK,
            'identifier'    => $temp_record_token,
            'data'          => $data,
        ]);
    }

    public function paystackSuccess($output = null) {
        if(!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception(__("Transaction Failed. The record didn't save properly. Please try again"));
        $callback_status = $output['tempData']['data']->callback_data->data->status;
        if($callback_status == 'success'){
            $status = PaymentGatewayConst::STATUSSUCCESS;
        }else{
            $status = PaymentGatewayConst::STATUSPENDING;
        }
        return $this->createTransactionPaystack($output,$status);

    }

    public function createTransactionPaystack($output,$status) {
        $basic_setting = BasicSettings::first();
        $user = auth()->user();
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordPaystack($output,$trx_id,$status);
        $this->insertChargesPaystack($output,$inserted_id);
        $this->adminNotification($trx_id,$output,$status);
        $this->insertDevicePaystack($output,$inserted_id);
        $this->removeTempDataPaystack($output);

        if($this->requestIsApiUser()) {
            // logout user
            $api_user_login_guard = $this->output['api_login_guard'] ?? null;
            if($api_user_login_guard != null) {
                auth()->guard($api_user_login_guard)->logout();
            }
        }
        try{
            if( $basic_setting->email_notification == true){
                $user->notify(new ApprovedMail($user,$output,$trx_id));
            }
        }catch(Exception $e){}
    }

    public function insertRecordPaystack($output,$trx_id,$status) {
        if($status == PaymentGatewayConst::STATUSSUCCESS){
            $available_balance = $output['wallet']->balance + $output['amount']->requested_amount;
        }else{
            $available_balance = $output['wallet']->balance;
        }
        $trx_id = $trx_id;
        DB::beginTransaction();
        try{
            if(Auth::guard(get_auth_guard())->check()){
                $user_id = auth()->guard(get_auth_guard())->user()->id;
            }
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       =>  $user_id,
                'user_wallet_id'                => $output['wallet']->id,
                'payment_gateway_currency_id'   => $output['currency']->id,
                'type'                          => "ADD-MONEY",
                'trx_id'                        => $trx_id,
                'request_amount'                => $output['amount']->requested_amount,
                'payable'                       => $output['amount']->total_amount,
                'available_balance'             => $available_balance,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::TYPEADDMONEY," ")) . " With " . $output['gateway']->name,
                'details'                       => PaymentGatewayConst::PAYSTACK." payment successful",
                'status'                        => $status,
                'attribute'                      =>PaymentGatewayConst::SEND,
                'created_at'                    => now(),
            ]);
            if($status == PaymentGatewayConst::STATUSSUCCESS){
                $this->updateWalletBalancePaystack($output);
            }
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }

    public function updateWalletBalancePaystack($output) {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;
        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    public function insertChargesPaystack($output,$id) {
        if(Auth::guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
        }
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $output['amount']->percent_charge,
                'fixed_charge'      => $output['amount']->fixed_charge,
                'total_charge'      => $output['amount']->total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         => __("Add Money"),
                'message'       => __("Your Wallet")." (".$output['wallet']->currency->code.")  ".__("balance  has been added")." ".$output['amount']->requested_amount.' '. $output['wallet']->currency->code,
                'time'          => Carbon::now()->diffForHumans(),
                'image'         =>  get_image($user->image,'user-profile')
            ];

            UserNotification::create([
                'type'      => NotificationConst::BALANCE_ADDED,
                'user_id'  =>  auth()->user()->id,
                'message'   => $notification_content,
            ]);
            try{
                (new PushNotificationHelper())->prepare([auth()->user()->id],[
                    'title' => $notification_content['title'],
                    'desc'  => $notification_content['message'],
                    'user_type' => 'user',
                ])->send();
             }catch(Exception $e) {}
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }

    public function insertDevicePaystack($output,$id) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();

        // $mac = exec('getmac');
        // $mac = explode(" ",$mac);
        // $mac = array_shift($mac);
        $mac = "";

        DB::beginTransaction();
        try{
            DB::table("transaction_devices")->insert([
                'transaction_id'=> $id,
                'ip'            => $client_ip,
                'mac'           => $mac,
                'city'          => $location['city'] ?? "",
                'country'       => $location['country'] ?? "",
                'longitude'     => $location['lon'] ?? "",
                'latitude'      => $location['lat'] ?? "",
                'timezone'      => $location['timezone'] ?? "",
                'browser'       => $agent->browser() ?? "",
                'os'            => $agent->platform() ?? "",
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }

    public function removeTempDataPaystack($output) {
        TemporaryData::where("identifier",$output['tempData']['identifier'])->delete();
    }
    public function isPayStack($gateway)
    {
        $search_keyword = ['Paystack','paystack','payStack','pay-stack','paystack gateway', 'paystack payment gateway'];
        $gateway_name = $gateway->name;

        $search_text = Str::lower($gateway_name);
        $search_text = preg_replace("/[^A-Za-z0-9]/","",$search_text);
        foreach($search_keyword as $keyword) {
            $keyword = Str::lower($keyword);
            $keyword = preg_replace("/[^A-Za-z0-9]/","",$keyword);
            if($keyword == $search_text) {
                return true;
                break;
            }
        }
        return false;
    }
    //for api
    public function paystackInitApi($output = null) {
        if(!$output) $output = $this->output;
        $credentials = $this->getPaystackCredentials($output);;
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount,2,'.','') : 0;
        $currency = $output['currency']['currency_code']??"USD";
        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
        }

        $user = auth()->guard(get_auth_guard())->user();
        $user_email = $user->email;
        $return_url =route('api.user.add.money.payment.callback.response', "r-source=" . PaymentGatewayConst::APP);

        $url = "https://api.paystack.co/transaction/initialize";

        $fields  = [
            'email'         => $user_email,
            'amount'        => get_amount($amount, null, 2) * 100,
            'currency'       => $currency,
            'callback_url'  => $return_url
        ];


        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $credentials->secret_key",
            "Cache-Control: no-cache",
        ));

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        $response   = json_decode($result);

        if($response->status == true) {
            $this->paystackJunkInsert($response,$response->data->reference,$credentials);
            $data['link'] = $response->data->authorization_url;
            $data['trx'] =  $response->data->reference;
            return $data;
        }else{
            throw new Exception($response->message??" "."Something Is Wrong, Please Contact With Owner");
        }

    }

}

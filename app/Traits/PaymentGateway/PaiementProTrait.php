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
use App\Models\User;
use App\Notifications\User\AddMoney\ApprovedMail;
use Illuminate\Support\Facades\Config;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Session;

trait PaiementProTrait
{
    public function paiementproInit($output = null)
    {
        if(!$output) $output = $this->output;

        $credentials = $this->getpaiementproCredentials($output);

        $reference = $this->generatepaiementproReference();
        $paiementmode=$output['paiementmode']?$output['paiementmode']:'AIRTELNG';

        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
            $user_lastname=$user->lastname;
        }

        $success_url = route('user.add.money.paiementpro.success');
        //$url_site=route('user.add.money');
        $fails_url = route('user.add.money.paiementpro.fails');
        $basic_setting = BasicSettings::first();
        //dump($credentials);
        

        /*$data = [
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
            'line' => "UP"
        ];*/
        /*$data=[
            "merchantId" => $credentials->merchant_id,
            "countryCurrencyCode" => '952',
            "referenceNumber" => $reference,
            "amount" => $amount, // ou "string" si vous traitez cela comme un texte
            "channel" => "OMCM",
            "phoneNumber"=>"+237679015958",
           "customerId" => $user->id,
           "customerEmail" => $user_email,
           "customerFirstName" => $user_name,
           "customerLastName" => $user_lastname,
           "customerPhoneNumber" => $user_phone,
           "description" => "api php",
           "notificationURL" => $fails_url,
           "returnURL" => $success_url,
           "returnContext" => '{"utilisateur_id":"'.$user->id.'","data2":"data 2"}',
           //"hashcode" => '{"utilisateur_id":"'.$user->id.'","data2":"data 2"}'

        ];*/
        $data=[
            'merchantId' => $credentials->merchant_id,
        'amount' => $amount,
        'description' => "Api PHP",
        'channel' => $paiementmode,
        'countryCurrencyCode' => "952",
        'referenceNumber' => $reference,
        'customerEmail' => $user_email,
        'customerFirstName' => $user_name,
        'customerLastname' => $user_lastname,
        'customerPhoneNumber' => $user_phone,
        'notificationURL' => $fails_url,
        'returnURL' => $success_url,
        'returnContext' => '{"data":"data 1","data2":"data 2"}',
        ];

        $payment = $this->initializepaiementproPayment($data);
        $payment=json_decode($payment,true);
        //dump($payment);
        //dump($payment['url']);

        if($payment['success'] == false) {
            throw new Exception($payment['message']);
        }

        $this->paiementproJunkInsert($data);

        if ($payment['success'] == false) {
            return;
        }

        return redirect($payment['url']);
    }

    public function paiementproJunkInsert($response)
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
        dump($response);
        dump($response['customerEmail']);
        Session::put('identifier', $response['customerEmail']);
        Session::put('output', $output);

        return TemporaryData::create([
            'type'          => PaymentGatewayConst::PAIEMENTPRO,
            'identifier'    => $response['customerEmail'],
            'data'          => $data,
        ]);
    }

    public function getpaiementproCredentials($output)
    {
        $gateway = $output['gateway'] ?? null;
        if(!$gateway) throw new Exception(__("Payment gateway not available"));

        $public_key = $gateway->credentials[0]->value ?? '';
        //$secret_key =  $gateway->credentials[0]->value ?? '';

        return (object) [
            'merchant_id' => $public_key,
            //'secret_key' => $secret_key,
        ];
    }

    public function generatepaiementproReference()
    {
        $basic_setting = BasicSettings::first();
        return $basic_setting->site_name . uniqid();
    }

    public function initializepaiementproPayment($data)
    {
        // Implement the actual API call to paiementpro here
        // For demonstration, we'll mock a response
        $data = json_encode($data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    
    
        $response = curl_exec($ch);
       
        curl_close($ch);
        dump($data);
        /*$payment = Http::post(
            'https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php',
            $data
        ); */
        return $response;
    }

    public function paiementproSuccess($output = null)
    {
        if(!$output) $output = $this->output;
        $token = $this->output['tempData']['identifier'] ?? "";
        if(empty($token)) throw new Exception(__('Transaction failed. Record didn\'t saved properly. Please try again'));
        return $this->createTransactionpaiementpro($output);
    }

    public function createTransactionpaiementpro($output)
    {
        $basic_setting = BasicSettings::first();
        $user = User::where('id',auth()->user()->id)->first();
        $trx_id = 'AM'.getTrxNum();
        $inserted_id = $this->insertRecordpaiementpro($output, $trx_id);
        $this->insertChargespaiementpro($output, $inserted_id);
        $this->insertDevicepaiementpro($output, $inserted_id);
        $this->removeTempDatapaiementpro($output);

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

    public function updateWalletBalancepaiementpro($output)
    {
        $update_amount = $output['wallet']->balance + $output['amount']->requested_amount;

        $output['wallet']->update([
            'balance'   => $update_amount,
        ]);
    }

    public function insertRecordpaiementpro($output, $trx_id)
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
                'details'                       => 'paiementpro Payment Successful',
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateWalletBalancepaiementpro($output);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again'));
        }
        return $id;
    }

    public function insertChargespaiementpro($output, $id)
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

    public function insertDevicepaiementpro($output, $id)
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

    public function removeTempDatapaiementpro($output)
    {
        TemporaryData::where("identifier", $output['tempData']['identifier'])->delete();
    }

    // ********* For API **********
    public function paiementproInitApi($output = null)
    {
        if(!$output) $output = $this->output;
        $credentials = $this->getpaiementproCredentials($output);

        $reference = $this->generatepaiementproReference();

        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;

        if(auth()->guard(get_auth_guard())->check()){
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_phone = $user->full_mobile ?? '';
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }
        $success_url = route('user.add.money.paiementpro.success', "r-source=".PaymentGatewayConst::APP);
        $fails_url = route('user.add.money.paiementpro.fails', "r-source=".PaymentGatewayConst::APP);
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

        $payment = $this->initializepaiementproPayment($data, $output);
        $data['link'] = $payment['link'];
        $data['trx'] = $data['orderId'];

        $this->paiementproJunkInsert($data);

        if ($payment['success'] == false) {
            return;
        }

        return $data;
    }
}

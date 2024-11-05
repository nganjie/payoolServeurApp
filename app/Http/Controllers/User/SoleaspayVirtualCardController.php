<?php

namespace App\Http\Controllers\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\Admin\TransactionSetting;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Models\User;
use App\Models\SoleaspayVirtualCard;
use App\Models\VirtualCardApi;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as FacadesRoute;
use Illuminate\Support\Facades\Validator;

class SoleaspayVirtualCardController extends Controller

{
    protected $api;
    protected $card_limit;
    public function __construct()
    {
        //$user=User::where('id',auth()->user()->id)->first();
        //dump(auth()->user());
        $cardApi = VirtualCardApi::where('name',Auth::check()?auth()->user()->name_api:Admin::first()->name_api)->first();
        $this->api =  $cardApi;
        $this->card_limit =  $cardApi->card_limit;
    }
    public function index()
    {
        //dump($this->api);
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        // Update card details
        $myCards = SoleaspayVirtualCard::where('user_id',auth()->user()->id)->get();
        //dump($this->api);
        //dump(Auth::check());
        //dump(auth()->user()->name_api);
        if( count($myCards) > 0){
            // Get Token
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://soleaspay.com/api/action/auth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "public_apikey" : "'. $this->api->config->soleaspay_public_key .'",
                "private_secretkey" : "'.$this->api->config->soleaspay_secret_key.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
            ));

            $response = json_decode(curl_exec($curl), true);
            if(!isset($response) && !array_key_exists('access_token', $response)){
                return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
            }
            $token = $response['access_token'];

            curl_close($curl);
            
            foreach ($myCards as $myCard) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $this->api->config->soleaspay_url.$myCard->card_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "Authorization: Bearer ".$token
                    ),
                ));

                $response = json_decode(curl_exec($curl), true);
                curl_close($curl);
                //dd($response);
                if ( isset($response) && key_exists('success', $response) && $response['success'] == true ) {

                    $card = $response['data']['card'];
                    $myCard->grade = $card['grade'];
                    $myCard->category = $card['category'];
                    $myCard->pin = $card['pin'];
                    $myCard->account_id = $card['ref'];
                    // $mycard->card_hash = $result->card_hash;
                    
                    $myCard->card_pan = $card['card_pan'];
                    $myCard->masked_card = $card['masked_pan'];
                    $myCard->cvv = $card['cvv'];
                    
                    $myCard->card_type = $card['card_type'];
                    //$myCard->name_on_card = $card['billing_name'];
                    // $mycard->callback = $result->callback_url;
                    $myCard->ref_id = $card['card_id'];
                    //$mycard->secret = $trx;
                    //$myCard->bg = "#0E0D2F";
                    //$mycard->city = $cardUser->billing_city;
                    //$mycard->state = $cardUser->billing_state;
                    //$mycard->zip_code = $cardUser->billing_postal_code;
                    //$mycard->address = $cardUser->billing_address;
                    $myCard->expiration = $card['expired_at'];
                    $myCard->amount =  $card['balance'];
                    $myCard->currency = $card['currency'];
                    // $mycard->charge =  $total_charge;
                    if ($card['active']) {
                        $myCard->is_active = 1;
                    } else {
                        $myCard->is_active = 0;
                    }
                    if ($card['disabled']) {
                        $myCard->is_disabled = 1;
                    } else {
                        $myCard->is_disabled = 0;
                    }
                    $myCard->save();
    
                }
            }
        }
        $page_title = __("Virtual Card");
        $myCards = SoleaspayVirtualCard::where('user_id',auth()->user()->id)->get();
        $totalCards = SoleaspayVirtualCard::where('user_id',auth()->user()->id)->count();
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->first();
        $cardReloadCharge = TransactionSetting::where('slug','reload_card')->where('status',1)->first();
        $transactions = Transaction::auth()->virtualCard()->latest()->take(10)->get();
        $cardApi = $this->api;
        $user = auth()->user();
        //dump(FacadesRoute::currentRouteName());
        return view('user.sections.virtual-card-soleaspay.index',compact('page_title','myCards','transactions','cardCharge','cardApi','totalCards','cardReloadCharge', 'user'));
        
    }
    public function cardDetails($card_id)
    {
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $page_title = __("Card Details");
        $myCard = SoleaspayVirtualCard::where('card_id',$card_id)->first();
        $cardApi = $this->api;
        return view('user.sections.virtual-card-soleaspay.details',compact('page_title','myCard','cardApi'));
    }

    public function cardBuy(Request $request)
    {
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $user = auth()->user();
        if($user->soleaspay_customer == null){
            $request->validate([
                'card_amount'       => 'required|numeric|gt:0|min:1',
                'first_name'        => ['required', 'string', 'regex:/^[^0-9\W]+$/'],
                'last_name'         => ['required', 'string', 'regex:/^[^0-9\W]+$/'],
                'email'    => 'required|string',
                'dob' => 'required|string',
                'id_number' => 'required|numeric|max:9',
            ], [
                'first_name.regex'  => 'The First Name field should only contain letters and cannot start with a number or special character.',
                'last_name.regex'   => 'The Last Name field should only contain letters and cannot start with a number or special character.',
            ]);
        }else {
            $request->validate([
                'card_amount' => 'required|numeric|gt:0|min:1',
            ]);
        }
        
        $amount = $request->card_amount;
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if(!$wallet){
            return back()->with(['error' => [__('User wallet not found')]]);
        }
        $cardCharge = TransactionSetting::where('slug','virtual_card')->where('status',1)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            return back()->with(['error' => [__('Default Currency Not Setup Yet')]]);
        }
        $minLimit =  $cardCharge->min_limit *  $rate;
        $maxLimit =  $cardCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            return back()->with(['error' => [__('Please follow the transaction limit')]]);
        }
        //charge calculations
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        if($payable > $wallet->balance ){
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $currency =$baseCurrency->code;
        $tempId = 'tempId-'. $user->id . time() . rand(6, 100);
        $trx = 'SVC-' . time() . rand(6, 100);

        // $callBack = route('user.soleaspay.virtual.card.callBack').'?c_user_id='.$user->id.'&c_amount='.  $amount.'&c_temp_id='.$tempId.'&c_trx='.$trx;
        // Get Token
        $curl = curl_init();
       // dump($this->api->config->soleaspay_public_key);

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://soleaspay.com/api/action/auth',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "public_apikey" : "'. $this->api->config->soleaspay_public_key .'",
            "private_secretkey" : "'.$this->api->config->soleaspay_secret_key.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
        )
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!isset($response) || !array_key_exists('access_token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['access_token'];

        curl_close($curl);
        // End 
        if ($user->soleaspay_customer == null) {
            
            // Create User
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->soleaspay_url.'user',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "first_name" : "'.$request->first_name.'",
                "last_name": "'.$request->last_name.'",
                "email": "'.$request->email.'",
                "country_code": "237",
                "contact" : "683442025",
                "dob" : "'.\DateTime::createFromFormat('Y-m-d', $request->dob)->format("Y-m-d\TH:i:sP").'",
                "is_business" : false,
                "business_name" : "",
                "billing_address" : "2055 Limestone Road",
                "billing_city" : "Wilmington",
                "billing_country" : "US",
                "billing_state" : "Delaware",
                "billing_postal_code" : "19808",
                "id_number" : '.$request->id_number.'
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$token
            )
            ));

            $response = json_decode(curl_exec($curl), true);
            
            if(isset($response) && key_exists('success', $response) && $response['success']){
                $ref = $response['data']['reference'];
                $user->soleaspay_customer = $ref;
                $userRepo = User::where('id', $user->id)->first();
                $userRepo->soleaspay_customer = $ref;
                $userRepo->save();
            }else{
                return redirect()->back()->with(['error' => [__("Something Went Wrong! Please Try Again")]]);
            }
            curl_close($curl);
        }else{
            $ref = $user->soleaspay_customer;
        }

        // $cardId = $response['data']['id'];
        $cardId = '';
        $trx_id =  'CB'.getTrxNum();
        $sender = $this->insertCadrBuy( $trx_id,$user,$wallet,$amount, $cardId ,$payable);
        $this->insertBuyCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender, $cardId);
        
        // Create a card
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->soleaspay_url.$ref,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
              "balance" : "'.$request->card_amount.'",
              "currency": "USD",
              "card_type": "'.$request->card_type.'",
              "category": "'.$request->card_category.'",
              "grade" : "BASIC"
          }',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: Bearer ".$token
            )
          ));
          
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        if (isset($response) && key_exists('success', $response) && $response['success'] ) {
            $cardId = $response['data']['id'];
        } else {
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        sleep(5);
        // Get card detail
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->soleaspay_url.$cardId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: Bearer ".$token
            )
          ));
        $result = json_decode(curl_exec($curl));
        curl_close($curl);

        if (isset($result)){
            if ( key_exists('success', $response) && $response['success'] &&  isset($result->data) ) {
                $card=$result->data->card;
                $cardUser = $result->data->card->virtual_card_user;
                //Save Card
                $v_card = new SoleaspayVirtualCard();
                $v_card->user_id = $user->id;
                $v_card->card_id = $card->id;
                $v_card->grade = $card->grade;
                $v_card->category = $card->category;
                $v_card->pin = $card->pin;
                $v_card->account_id = $card->ref;
                // $v_card->card_hash = $result->card_hash;
                
                $v_card->card_pan = $card->card_pan;
                $v_card->masked_card = $card->masked_pan;
                $v_card->cvv = $card->cvv;
                $v_card->expiration = $card->expired_at;
                $v_card->card_type = $card->card_type;
                $v_card->name_on_card = $cardUser->last_name .' '.$cardUser->first_name;
                // $v_card->callback = $result->callback_url;
                $v_card->ref_id = $card->card_id;
                //$v_card->secret = $trx;
                $v_card->bg = "#0E0D2F";
                $v_card->city = $cardUser->billing_city;
                $v_card->state = $cardUser->billing_state;
                $v_card->zip_code = $cardUser->billing_postal_code;
                $v_card->address = $cardUser->billing_address;
                $v_card->amount =  $card->balance;
                $v_card->currency = $card->currency;
                // $v_card->charge =  $total_charge;
                if ($card->active) {
                    $v_card->is_active = 1;
                } else {
                    $v_card->is_active = 0;
                }
                if ($card->disabled) {
                    $v_card->is_disabled = 1;
                } else {
                    $v_card->is_disabled = 0;
                }
                $v_card->save();
                return redirect()->route("user.soleaspay.virtual.card.index")->with(['success' => [__('Buy Card Successfully')]]);
            }else {
                return redirect()->back()->with(['error' => [@$result['message']??__("Something Went Wrong! Please Try Again")]]);
            }
        }

    }
    public function cardFundConfirm(Request $request){
        $request->validate([
            'id' => 'required|integer',
            'fund_amount' => 'required|numeric|gt:0',
        ]);
        $user = auth()->user();
        $myCard =  SoleaspayVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();

        if(!$myCard){
            return back()->with(['error' => [__('Something Is Wrong In Your Card')]]);
        }

        $amount = $request->fund_amount;
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if(!$wallet){
            return back()->with(['error' => [__('User wallet not found')]]);
        }
        $cardCharge = TransactionSetting::where('slug','reload_card')->where('status',1)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            return back()->with(['error' => [__('Default Currency Not Setup Yet')]]);
        }
        $minLimit =  $cardCharge->min_limit *  $rate;
        $maxLimit =  $cardCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            return back()->with(['error' => [__('Please follow the transaction limit')]]);
        }
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        if($payable > $wallet->balance ){
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $currency =$baseCurrency->code;
        $tempId = 'tempId-'. $user->id . time() . rand(6, 100);
        $trx = 'VC-' . time() . rand(6, 100);
        // Get Token
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://soleaspay.com/api/action/auth',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "public_apikey" : "'. $this->api->config->soleaspay_public_key .'",
            "private_secretkey" : "'.$this->api->config->soleaspay_secret_key.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
        )
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!isset($response) || !array_key_exists('access_token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }

        $token = $response['access_token'];
        //Optimistics update
        $trx_id = 'CF'.getTrxNum();
        $sender = $this->insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
        $this->insertFundCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL =>   $this->api->config->soleaspay_url."transaction/".$myCard->card_id."?action=topup&amount=".$amount,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Authorization: Bearer " .$token
        )
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);
        
        if( isset($result) && array_key_exists('success', $result) && $result['success'] == true){
            //added fund amount to card
            $myCard->amount += $amount;
            $myCard->save();
            //
            return redirect()->back()->with(['success' => [__('Card Funded Successfully')]]);

        }else{
            return redirect()->back()->with(['error' => [@$result['message']??__("Something Went Wrong! Please Try Again")]]);
        }

    }

    public function cardBlockUnBlock(Request $request) {
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $validator = Validator::make($request->all(),[
            'status'                    => 'required|boolean',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->safe()->all();
        if($request->status == 1 ){
            $card = SoleaspayVirtualCard::where('id',$request->data_target)->first();
            $status = 'block';
            if(!$card){
                $error = ['error' => [__('Something Is Wrong In Your Card')]];
                return Response::error($error,null,404);
            }
             // Get Token
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://soleaspay.com/api/action/auth',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "public_apikey" : "'. $this->api->config->soleaspay_public_key .'",
            "private_secretkey" : "'.$this->api->config->soleaspay_secret_key.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
        )
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!array_key_exists('access_token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['access_token'];

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->soleaspay_url."transaction/".$card->card_id."?action=disabled",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: Bearer ".$token,
            )
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);
        
            if (isset($result)) {
                if ($result['success'] == true) {
                    $card->is_active = 0;
                    $card->save();
                    $success = ['success' => [__('Card block successfully!')]];
                    return Response::success($success,null,200);
                }  else {
                    $error = ['error' => [$result->message]];
                    return Response::error($error, null, 404);
                }
            }


        }else{
            $card = SoleaspayVirtualCard::where('id',$request->data_target)->first();
        $status = 'unblock';
        if(!$card){
            $error = ['error' => [__('Something Is Wrong In Your Card')]];
            return Response::error($error,null,404);
        }
        // Get Token
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://soleaspay.com/api/action/auth',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "public_apikey" : "'. $this->api->config->soleaspay_public_key .'",
            "private_secretkey" : "'.$this->api->config->soleaspay_secret_key.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
        )
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!isset($response) || !array_key_exists('access_token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['access_token'];

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->soleaspay_url."transaction/".$card->card_id."?action=enabled",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: Bearer ".$token
            )
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);
        
        if (isset($result)) {
            if ( $result['success'] == true ) {
                $card->is_active = 1;
                $card->save();
                $success = ['success' => [__('Card unblock successfully!')]];
                return Response::success($success,null,200);
            } else{
                $error = ['error' => [$result->message]];
                return Response::error($error, null, 404);
            }
        }
        }
    }
    public function cardTransaction($card_id) {
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $user = auth()->user();
        $card = SoleaspayVirtualCard::where('user_id',$user->id)->where('card_id', $card_id)->first();
        $page_title = __("Virtual Card Transaction");
        $id = $card->card_id;
        $emptyMessage  = 'No Transaction Found!';
        $start_date = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-12 month" ) );
        $end_date = date('Y-m-d');
        // Get Token
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://soleaspay.com/api/action/auth',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "public_apikey" : "'. $this->api->config->soleaspay_public_key .'",
            "private_secretkey" : "'.$this->api->config->soleaspay_secret_key.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
        )
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!isset($response) || !array_key_exists('access_token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['access_token'];

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->soleaspay_url.$id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: Bearer ".$token
            )
        ));

        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        if(!isset($response) || !array_key_exists('data', $response)) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $card_truns = array("data"=>$response['data']['transactions']);
        return view('user.sections.virtual-card.trx',compact('page_title','card','card_truns'));


    }
    public function makeDefaultOrRemove(Request $request) {
        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $user = auth()->user();
        $targetCard =  SoleaspayVirtualCard::where('id',$validated['target'])->where('user_id',$user->id)->first();
        $withOutTargetCards =  SoleaspayVirtualCard::where('id','!=',$validated['target'])->where('user_id',$user->id)->get();
        try{
            $targetCard->update([
                'is_default'  => $targetCard->is_default ? 0 : 1,
            ]);
            if(isset(  $withOutTargetCards)){
                foreach(  $withOutTargetCards as $card){
                    $card->is_default = false;
                    $card->save();
                }
            }

        }catch(Exception $e) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        return back()->with(['success' => [__('Status Updated Successfully')]]);
    }
    //card buy helper
    public function insertCadrBuy( $trx_id,$user,$wallet,$amount, $v_card ,$payable) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $payable);
        $details =[
            'card_info' =>   $v_card??''
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $payable,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::CARDBUY," ")),
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::RECEIVED,
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something Went Wrong! Please Try Again"));
        }
        return $id;
    }

    public function insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$id,$masked_card) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      =>$fixedCharge,
                'total_charge'      =>$total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
             $notification_content = [
                'title'         =>"Buy Card",
                'message'       => 'Buy card successful '.$masked_card,
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_BUY,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something Went Wrong! Please Try Again"));
        }
    }
    //card fund helper
    public function insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $payable);
        $details =[
            'card_info' =>   $myCard??''
        ];
        DB::beginTransaction();
        try{
            $id = DB::table("transactions")->insertGetId([
                'user_id'                       => $user->id,
                'user_wallet_id'                => $authWallet->id,
                'payment_gateway_currency_id'   => null,
                'type'                          => PaymentGatewayConst::VIRTUALCARD,
                'trx_id'                        => $trx_id,
                'request_amount'                => $amount,
                'payable'                       => $payable,
                'available_balance'             => $afterCharge,
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::CARDFUND," ")),
                'details'                       => json_encode($details),
                'attribute'                      =>PaymentGatewayConst::RECEIVED,
                'status'                        => true,
                'created_at'                    => now(),
            ]);
            $this->updateSenderWalletBalance($authWallet,$afterCharge);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something Went Wrong! Please Try Again"));
        }
        return $id;
    }
    public function insertFundCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$id,$masked_card,$amount) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      =>$fixedCharge,
                'total_charge'      =>$total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>"Card Fund",
                'message'       => __("Card fund successful card:")." ".$masked_card.' '.getAmount($amount,2).' '.get_default_currency_code(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_FUND,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something Went Wrong! Please Try Again"));
        }
    }
    //update user balance
    public function updateSenderWalletBalance($authWalle,$afterCharge) {
        $authWalle->update([
            'balance'   => $afterCharge,
        ]);
    }

    public function cardCallBack(Request $request){
        $body = @file_get_contents("php://input");
        $signature = (isset($_SERVER['HTTP_VERIF_HASH']) ? $_SERVER['HTTP_VERIF_HASH'] : '');
        if (!$signature) {
            exit();
        }
        $local_signature = env('SECRET_HASH');
        if ($signature !== $local_signature) {
            exit();
        }
        http_response_code(200);
        $response = json_decode($body);
        $trx = 'VC-' . str_random(6);
        if ($response->status == 'successful') {
            $card = SoleaspayVirtualCard::where('card_id', $response->CardId)->first();
            if ($card) {
                $card->amount = $response->balance;
                $card->save();

                //Transactions
                // $vt = new Virtualtransactions();
                // $vt->user_id = $card->user_id;
                // $vt->virtual_card_id = $card->id;
                // $vt->card_id = $card->card_id;
                // $vt->amount = $response->amount;
                // $vt->description = $response->description;
                // $vt->trx = $trx;
                // $vt->status = $response->status;
                // $vt->save();


                return true;
            }
            return true;
        }
        return false;
    }

}

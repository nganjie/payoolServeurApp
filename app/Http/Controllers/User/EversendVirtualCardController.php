<?php

namespace App\Http\Controllers\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\Response;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\Admin\TransactionSetting;
use App\Models\EversendVirtualCard;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Models\User;
use App\Models\VirtualCardApi;
use Barryvdh\Debugbar\Twig\Extension\Dump;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EversendVirtualCardController extends Controller

{
    protected $api;
    protected $card_limit;
    public function __construct()
    {
        $cardApi = VirtualCardApi::where('name',Auth::check()?auth()->user()->name_api:Admin::first()->name_api)->first();
        $this->api =  $cardApi;
        $this->card_limit =  $cardApi->card_limit;
    }
    public function index()
    {
        // Update card details
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $myCards = EversendVirtualCard::where('user_id',auth()->user()->id)->get();
        if( count($myCards) >0){
            // Get Token
            $public_key=$this->api->config->eversend_public_key;
            $secret_key=$this->api->config->eversend_secret_key;
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.eversend.co/v1/auth/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "clientId:$public_key",
                    "clientSecret:$secret_key"
                  ],
                ));

            $response = json_decode(curl_exec($curl), true);
            if(isset($response) && key_exists('status', $response) && $response['status']==400){
                return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
            }
            //dd($response);
            $token = $response['token'];

            curl_close($curl);
            
            
           foreach ($myCards as $myCard) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $this->api->config->eversend_url.$myCard->card_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER =>[
                "accept: application/json",
                "authorization: Bearer $token",
                "content-type: application/json"
              ],
                ));

                $response = json_decode(curl_exec($curl), true);
                curl_close($curl);
                dd($response);
                if ( isset($response) && key_exists('success', $response) && $response['success'] == true ) {
                    //$myCard=new EversendVirtualCard();
                    //dd($response);
                    $myCard->user_id=auth()->user()->id;

                    $card = $response['data']['card'];
                    $myCard->security_code = $card['securityCode'];
                    $myCard->expiration = $card['expiration'];
                    $myCard->currency = $card['currency'];
                     $myCard->status = $card['status'];
                    $myCard->is_Physical = $card['isPhysical'];
                    $myCard->title = $card['title'];
                    $myCard->color = $card['color'];
                    $myCard->name = $card['name'];
                    $myCard->amount = $card['balance'];
                    $myCard->brand = $card['brand'];
                    $myCard->mask = $card['mask'];
                    $myCard->number = $card['number'];
                    $myCard->owner_id = $card['ownerId'];
                    $myCard->is_non_subscription = $card['isNonSubscription'];
                    $myCard->last_used_on = $card['lastUsedOn'];
                    $myCard->billing_address = $card['billingAddress'];
                    /*$myCard->grade = $card['grade'];
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
                    $myCard->currency = $card['currency'];*/
                    // $mycard->charge =  $total_charge;
                    if ($card['isPhysical']) {
                        $myCard->is_Physical = 1;
                    } else {
                        $myCard->is_Physical = 0;
                    }
                    if ($card['isNonSubscription']) {
                        $myCard->is_non_subscription = 1;
                    } else {
                        $myCard->is_non_subscription = 0;
                    }
                    $myCard->save();
    
                }
           }
        }
        $page_title = __("Virtual Card");
        $myCards = EversendVirtualCard::where('user_id',auth()->user()->id)->get();
        $totalCards = EversendVirtualCard::where('user_id',auth()->user()->id)->count();
        $cardCharge = TransactionSetting::where('slug','virtual_card_'.auth()->user()->name_api)->where('status',1)->first();
        $cardReloadCharge = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();
        $transactions = Transaction::auth()->virtualCard()->latest()->take(10)->get();
        $cardApi = $this->api;
        $user = auth()->user();
        //dd($myCards);
        //dump(FacadesRoute::currentRouteName());
        return view('user.sections.virtual-card-eversend.index',compact('page_title','myCards','transactions','cardCharge','cardApi','totalCards','cardReloadCharge', 'user'));
    }
    public function cardDetails($card_id)
    {
        $page_title = __("Card Details");
        $myCard = EversendVirtualCard::where('card_id',$card_id)->first();
        $cardApi = $this->api;
        return view('user.sections.virtual-card-eversend.details',compact('page_title','myCard','cardApi'));
    }

    public function cardBuy(Request $request)
    {
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        if (!$this->api->is_created_card) {
            return back()->with(['error' => [__('the card purchase is temporary deactivate for this type of card')]]);
        }
        $user = auth()->user();
        if($user->eversend_customer == null){
            $request->validate([
                'card_amount'       => 'required|numeric|gt:0|min:1',
                'first_name'        => ['required', 'string', 'regex:/^[^0-9\W]+$/'],
                'last_name'         => ['required', 'string', 'regex:/^[^0-9\W]+$/'],
                'email'    => 'required|string',
                'dob' => 'required|string',
                'id_number' => 'required|numeric|max:9',
                'isNonSubscription' => 'required|string',
            ], [
                'first_name.regex'  => 'The First Name field should only contain letters and cannot start with a number or special character.',
                'last_name.regex'   => 'The Last Name field should only contain letters and cannot start with a number or special character.',
            ]);
        }else {
            $request->validate([
                'card_amount' => 'required|numeric|gt:0|min:1',
            ]);
        }
        //dd($request);
        $amount = $request->card_amount;
        $wallet = UserWallet::where('user_id',$user->id)->first();

        if(!$wallet){
            return back()->with(['error' => [__('User wallet not found')]]);
        }
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $cardCharge = TransactionSetting::where('slug','virtual_card_'.auth()->user()->name_api)->where('status',1)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        //dd($cardCharge);
        if(!$baseCurrency){
            return back()->with(['error' => [__('Default Currency Not Setup Yet')]]);
        }
        $minLimit =  $cardCharge->min_limit *  $rate;
        $maxLimit =  $cardCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            return back()->with(['error' => [__('Please follow the transaction limit')]]);
        }
        //charge calculations
        $eversendCharge=0;
        if($request->isNonSubscription=="final"){
            $eversendCharge=$cardCharge->fixed_final_charge;
        }else{
            $eversendCharge=$cardCharge->fixed_month_charge;
        }
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount+$eversendCharge;
        //dd($payable);
        if($payable > $wallet->balance ){
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $currency =$baseCurrency->code;
        $tempId = 'tempId-'. $user->id . time() . rand(6, 100);
        $trx = 'SVC-' . time() . rand(6, 100);

        // $callBack = route('user.eversend.virtual.card.callBack').'?c_user_id='.$user->id.'&c_amount='.  $amount.'&c_temp_id='.$tempId.'&c_trx='.$trx;
        // Get Token
        $curl = curl_init();
        //dd($this->api);
        $public_key=$this->api->config->eversend_public_key;
        $secret_key=$this->api->config->eversend_secret_key;


        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.eversend.co/v1/auth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "clientId:$public_key",
            "clientSecret:$secret_key"
          ],
        ));

        

        $response = json_decode(curl_exec($curl), true);
        /*dump($response);
        dump($curl);
        dump($this->api->config->eversend_public_key);
        dump($this->api->config->eversend_secret_key);
        dump($this->api->config);*/
        
        //dd($response);
        if(!isset($response) || !array_key_exists('token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['token'];

        curl_close($curl);
        // End 
        //dd($user->eversend_customer);
        if ($user->eversend_customer == null) {
            
            // Create User
            //dump($user->full_mobile);
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->eversend_url.'user',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode([
                'firstName' => $request->first_name,
                'lastName' => $request->last_name,
                'email' => $request->email,
                'phone' => '+12068489567',
                'country' => 'US',
                'state' => 'NY',
                'city' => 'New York',
                'address' => '447 Broadway, 2nd Floor',
                'zipCode' => '10013',
                'idType' => 'Driving_License',
                'idNumber' => $request->id_number.'1290282882'
              ]),
            CURLOPT_HTTPHEADER =>  [
                "accept: application/json",
                "authorization: Bearer $token",
                "content-type: application/json"
              ],
            ));

            $response = json_decode(curl_exec($curl), true);
            //dd($response);
            $ref='';
            
            if(isset($response) && key_exists('success', $response) && $response['success']){
                $ref = $response['data']['data']['userId'];
                $user->eversend_customer = $ref;
                $userRepo = User::where('id', $user->id)->first();
                $userRepo->eversend_customer = $ref;
                $userRepo->save();
                //dump($response);
               // dd($userRepo);
                //echo 'on ici maintenant';
            }else{
                //dump($response);
                return redirect()->back()->with(['error' => [__("Something Went Wrong! Please Try Again")]]);
            }
            //dump($request->first_name);
            curl_close($curl);
        }else{
            $ref = $user->eversend_customer;
        }
        //dd($user->eversend_customer);

        // $cardId = $response['data']['id'];
        //dd($user);
        $cardId = '';
       
        
        // Create a card
        //dd($request);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->eversend_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>
            json_encode([
                'title' => 'GCP Services',
                'color' => 'blue',
                'amount' => $request->card_amount,
                'userId' =>'AUI1838',
                'currency' => 'USD',
                'brand' => strtolower($request->card_type),
                'isNonSubscription' => $request->isNonSubscription=='final'?true:false
            ]),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Bearer $token",
                "content-type: application/json"
              ]
          ));
          
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        //dump($this->api->config->eversend_url.'cards');
        
        if (isset($response) && key_exists('success', $response) && $response['success'] ) {
            $cardId = $response['data']['card']['id'];
            $trx_id =  'CB'.getTrxNum();
            $sender = $this->insertCadrBuy($trx_id,$user,$wallet,$amount, $cardId ,$payable,true);
            $this->insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$sender, $cardId);
        } else {
            $trx_id =  'CB'.getTrxNum();
            $sender = $this->insertCadrBuy($trx_id,$user,$wallet,$amount, $cardId ,$payable,false);
            $this->insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$sender, $cardId);
           // dd($response);
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        sleep(5);
        // Get card detail
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->eversend_url.$cardId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authorization: Bearer $token",
                "content-type: application/json"
              ]
          ));
        $result = json_decode(curl_exec($curl));
        curl_close($curl);
        

        if (isset($result)){
            if ( key_exists('success', $response) && $response['success'] &&  isset($result->data) ) {
                $card=$result->data->data->card;
                //$cardUser = $result->data->data->card->virtual_card_user;
                //Save Card
                $v_card = new eversendVirtualCard();
                $card = $response['data']['card'];
                    $v_card->security_code = $card['securityCode'];
                    $v_card->expiration = $card['expiration'];
                    $v_card->currency = $card['currency'];
                     $v_card->status = $card['status'];
                    $v_card->is_Physical = $card['isPhysical'];
                    $v_card->title = $card['title'];
                    $v_card->color = $card['color'];
                    $v_card->name = $card['name'];
                    $v_card->amount = $card['balance'];
                    $v_card->brand = $card['brand'];
                    $v_card->mask = $card['mask'];
                    $v_card->number = $card['number'];
                    $v_card->owner_id = $card['ownerId'];
                    $v_card->isNonSubscription = $card['isNonSubscription'];
                    $v_card->lastUsedOn = $card['lastUsedOn'];
                    $v_card->billingAddress = $card['billingAddress'];
             
                // $v_card->charge =  $total_charge;
                if ($card->is_Physical) {
                    $v_card->is_Physical = 1;
                } else {
                    $v_card->isPhysical = 0;
                }
                if ($card->isNonSubscription) {
                    $v_card->isNonSubscription = 1;
                } else {
                    $v_card->isNonSubscription = 0;
                }
                $v_card->save();
                return redirect()->route("user.eversend.virtual.card.index")->with(['success' => [__('Buy Card Successfully')]]);
                
            }else {
                dd($response);
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
        $myCard =  EversendVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();

        if(!$myCard){
            return back()->with(['error' => [__('Something Is Wrong In Your Card')]]);
        }

        $amount = $request->fund_amount;
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if(!$wallet){
            return back()->with(['error' => [__('User wallet not found')]]);
        }
        //dd($request);
        $cardCharge = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();

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
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $currency =$baseCurrency->code;
        $tempId = 'tempId-'. $user->id . time() . rand(6, 100);
        $trx = 'VC-' . time() . rand(6, 100);
        // Get Token
        $public_key=$this->api->config->eversend_public_key;
        $secret_key=$this->api->config->eversend_secret_key;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.eversend.co/v1/auth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "clientId:$public_key",
                "clientSecret:$secret_key"
              ],
            ));

        $response = json_decode(curl_exec($curl), true);
        if(!isset($response) || !array_key_exists('token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }

        $token = $response['token'];
        //Optimistics update
        $trx_id = 'CF'.getTrxNum();
        $sender = $this->insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
        $this->insertFundCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL =>   $this->api->config->eversend_url."fund",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>json_encode([
            "amount"=>$amount,
           "currency"=>"USD",
           "cardId"=>$myCard->card_id
          ]),
          CURLOPT_HTTPHEADER =>  [
            "accept: application/json",
            "authorization: Bearer $token",
            "content-type: application/json"
          ],
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
            $card = eversendVirtualCard::where('id',$request->data_target)->first();
            $status = 'block';
            if(!$card){
                $error = ['error' => [__('Something Is Wrong In Your Card')]];
                return Response::error($error,null,404);
            }
             // Get Token
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.eversend.co/v1/auth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS =>'{
            "clientId" : "'. $this->api->config->eversend_public_key .'",
            "clientSecret" : "'.$this->api->config->eversend_secret_key.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
        )
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!array_key_exists('token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['token'];

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->eversend_url."freeze",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "cardId":"'.$card->card_id.'"
                }',
            CURLOPT_HTTPHEADER => array(
              "accept: application/json",
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
            $card = eversendVirtualCard::where('id',$request->data_target)->first();
        $status = 'unblock';
        if(!$card){
            $error = ['error' => [__('Something Is Wrong In Your Card')]];
            return Response::error($error,null,404);
        }
        // Get Token
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.eversend.co/v1/auth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS =>'{
            "clientId" : "'. $this->api->config->eversend_public_key .'",
            "clientSecret" : "'.$this->api->config->eversend_secret_key.'"
        }',
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
        )
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!isset($response) || !array_key_exists('token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['token'];

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->eversend_url."unfreeze",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                "cardId":"'.$card->card_id.'"
                }',
            CURLOPT_HTTPHEADER => array(
              "accept: application/json",
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
        $card = EversendVirtualCard::where('user_id',$user->id)->where('card_id', $card_id)->first();
        $page_title = __("Virtual Card Transaction");
        $id = $card->card_id;
        $emptyMessage  = 'No Transaction Found!';
        $start_date = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-12 month" ) );
        $end_date = date('Y-m-d');
        // Get Token
        $curl = curl_init();
        $public_key=$this->api->config->eversend_public_key;
        $secret_key=$this->api->config->eversend_secret_key;


        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.eversend.co/v1/auth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "clientId:$public_key",
            "clientSecret:$secret_key"
          ],
        ));

        $response = json_decode(curl_exec($curl), true);
        if(!isset($response) || !array_key_exists('token', $response)){
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        $token = $response['token'];

        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->eversend_url.'/transactions'.'/'.$id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER =>[
                "accept: application/json",
                "authorization: Bearer $token",
                "content-type: application/json"
              ],
        ));

        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        //dd($response);
        if(!isset($response) || !array_key_exists('data', $response)) {
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $card_truns = array("data"=>$response['data']['transactions']);
        //dd($card_truns);
        return view('user.sections.virtual-card-eversend.trx',compact('page_title','card','card_truns'));


    }
    public function makeDefaultOrRemove(Request $request) {
        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $user = auth()->user();
        $targetCard =  eversendVirtualCard::where('id',$validated['target'])->where('user_id',$user->id)->first();
        $withOutTargetCards =  eversendVirtualCard::where('id','!=',$validated['target'])->where('user_id',$user->id)->get();
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
    public function insertCadrBuy( $trx_id,$user,$wallet,$amount, $v_card ,$payable,$status=false) {
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
                'status'                        => $status,
                'created_at'                    => now(),
            ]);
            if($status)
            {
                $this->updateSenderWalletBalance($authWallet,$afterCharge);
            }
            

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
            $card = eversendVirtualCard::where('card_id', $response->CardId)->first();
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
?>
<?php

namespace App\Http\Controllers\User;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Controllers\Controller;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\Response;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\Admin\TransactionSetting;
use App\Models\MapleradVirtualCard;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Models\User;
use App\Models\VirtualCardApi;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\VirtualCard\CreateMail;
use App\Notifications\User\VirtualCard\Fund;
use App\Notifications\User\VirtualCard\PayPenalityMail;
use App\Notifications\User\Withdraw\WithdrawMail;
use App\Providers\Admin\BasicSettingsProvider;
use Barryvdh\Debugbar\Twig\Extension\Dump;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MapleradVirtualCardController extends Controller

{
    protected $api;
    protected $card_limit;
    protected $basic_settings;
    public function __construct()
    {
        $cardApi = VirtualCardApi::where('name',Auth::check()?auth()->user()->name_api:Admin::first()->name_api)->first();
        $this->api =  $cardApi;
        $this->card_limit =  $cardApi->card_limit;
        $this->basic_settings = BasicSettingsProvider::get();
    }
    public function index()
    {
        // Update card details
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $myCards = MapleradVirtualCard::where('user_id',auth()->user()->id)->get();
        if( count($myCards) >0){
            // Get Token
            $public_key=$this->api->config->maplerad_public_key;
            $secret_key=$this->api->config->maplerad_secret_key;
            
           foreach ($myCards as $myCard) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $this->api->config->maplerad_url.'issuing/'.$myCard->card_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER =>[
                    "Authorization: Bearer ".$secret_key,
                    "accept: application/json",
              ],
                ));

                $response = json_decode(curl_exec($curl), true);
                curl_close($curl);
                //dd($response);
                if ( isset($response) && key_exists('status', $response) && $response['status'] == true ) {
                    //$myCard=new MapleradVirtualCard();
                    //dd($response);
                    try{
                        $myCard->user_id=auth()->user()->id;

                        $card = $response['data'];
                        $myCard->card_id = $card['id'];
                        $myCard->expiry = $card['expiry'];
                        $myCard->cvv = $card['cvv'];
                        $myCard->currency = $card['currency'];
                         $myCard->status = $card['status'];
                        $myCard->type = $card['type'];
                        $myCard->masked_pan = $card['masked_pan'];
                        $myCard->issuer = $card['issuer'];
                        $myCard->name = $card['name'];
                        $myCard->balance = $card['balance']/100;
                        $myCard->auto_approve = $card['auto_approve'];
                        $myCard->card_number = $card['card_number'];
                        $myCard->address = $card['address'];
                 
                    // $v_card->charge =  $total_charge;
                    
                    $myCard->save();
                        //$myCard->save();
                    }catch(Exception $e){
                        dump($card);
                        dd($e);
                    }
                  
    
                }
           }
        }
        $page_title = __("Virtual Card");
        $myCards = MapleradVirtualCard::where('user_id',auth()->user()->id)->where('is_deleted',false)->get();
        $totalCards = MapleradVirtualCard::where('user_id',auth()->user()->id)->where('is_deleted',false)->count();
        $cardCharge = TransactionSetting::where('slug','virtual_card_'.auth()->user()->name_api)->where('status',1)->first();
        $cardReloadCharge = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();
        $cardWithdrawCharge = TransactionSetting::where('slug','withdraw_card_'.auth()->user()->name_api)->where('status',1)->first();
        $transactions = Transaction::auth()->virtualCard()->latest()->take(10)->get();
        $cardApi = $this->api;
        $user = auth()->user();
        //dd($myCards);
        //dump(FacadesRoute::currentRouteName());
        return view('user.sections.virtual-card-maplerad.index',compact('page_title','myCards','transactions','cardCharge','cardApi','totalCards','cardReloadCharge','cardWithdrawCharge', 'user'));
    }
    
    public function cardDetails($card_id)
    {
        $page_title = __("Card Details");
        $myCard = MapleradVirtualCard::where('card_id',$card_id)->first();
        $cardApi = $this->api;
        //dd($myCard->user());
        $cardWithdrawCharge = TransactionSetting::where('slug','withdraw_card_'.auth()->user()->name_api)->where('status',1)->first();
        return view('user.sections.virtual-card-maplerad.details',compact('page_title','myCard','cardApi','cardWithdrawCharge'));
    }
    public function deleteCard(Request $request){
        $myCard = MapleradVirtualCard::where('id',$request->card_id)->first();
        if(!$myCard){
            return back()->with(['error' => [__('Something Is Wrong In Your Card')]]);
        }
        $myCard->is_deleted=true;
        $myCard->save();
        return back()->with(['success' => [__('your card has been successfully deleted')]]);
    }

    public function cardBuy(Request $request)
    {
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        if (!$this->api->is_created_card) {
            return back()->with(['error' => [__('the card purchase is temporary deactivate for this type of card')]]);
        }
        $user = auth()->user();
       // dd($request->id_number);
       
        if($user->maplerad_customer == null){
            $request->validate([
                'card_amount'       => 'required|numeric|min:0',
                'first_name'        => ['required', 'string', 'regex:/^[^0-9\W]+$/'],
                'last_name'         => ['required', 'string', 'regex:/^[^0-9\W]+$/'],
                'email'    => 'required|string',
                'id_number' => 'required|numeric',
                'dob' => 'required|string',
                'phone_code' => 'required|string',
                'phone' => 'required|string',
                'type_card'=>'required|string'
            ], [
                'first_name.regex'  => 'The First Name field should only contain letters and cannot start with a number or special character.',
                'last_name.regex'   => 'The Last Name field should only contain letters and cannot start with a number or special character.',
            ]);
        }else {
            $request->validate([
                'card_amount' => 'required|numeric',
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
        $mapleradCharge=0;
        if($request->type_card=="business"){
            $mapleradCharge=$cardCharge->fixed_final_charge;
        }else{
            $mapleradCharge=0;
        }
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount+$mapleradCharge;
        //dd($payable);
        if($payable > $wallet->balance ){
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $currency =$baseCurrency->code;
        //dd($currency);

        // $callBack = route('user.maplerad.virtual.card.callBack').'?c_user_id='.$user->id.'&c_amount='.  $amount.'&c_temp_id='.$tempId.'&c_trx='.$trx;
        // Get Token
        $public_key=$this->api->config->maplerad_public_key;
        $secret_key=$this->api->config->maplerad_secret_key;


        //dd($request->phone_code);
        
        if ($user->maplerad_customer == null) {
            
            // Create User
            //dump($user->full_mobile);
           // dd([$request->first_name,$request->last_name]);
           //dd($request->dob);
           //dd();
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.'customers',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'country' => 'US',
              ]),
            CURLOPT_HTTPHEADER =>  [
                "Authorization: Bearer ".$secret_key,
                "accept: application/json",
              ],
            ));

            $response = json_decode(curl_exec($curl), true);
            //dd($response);
            $ref='';
            
            if(isset($response) && key_exists('status', $response) && $response['status']){
                $ref = $response['data']['id'];
                $user->maplerad_customer = $ref;
                $userRepo = User::where('id', $user->id)->first();
                $userRepo->maplerad_customer = $ref;
                
                $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.'customers/upgrade/tier1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_POSTFIELDS =>json_encode([
                'customer_id' => $ref,
                'dob' => Carbon::createFromFormat('Y-m-d', $request->input('dob'))->format('d-m-Y'),
                'phone' => [
                    'phone_country_code'=>'+1',
                    'phone_number'=>'2068489567',
                ],
                'address' => [
                    'street'=>'2055 Limestone Road',
                    'city'=>'Wilmington',
                    'state'=>'Delaware',
                    'country'=>'US',
                    'postal_code'=>'19808'
                ],
                'identification_number'=>$request->id_number.'1290282882'
              ]),
            CURLOPT_HTTPHEADER =>  [
                "Authorization: Bearer ".$secret_key,
                "accept: application/json",
              ],
            ));

            $response = json_decode(curl_exec($curl), true);
            if(isset($response)&&$response['status']){
                $userRepo->save();
            }else{
                dd($response);
                return redirect()->back()->with(['error' => [__("Something Went Wrong! Please Try Again")]]);
            }
                //dump($response);
               // dd($userRepo);
                //echo 'on ici maintenant';
            }else{
                dd($response);
                return redirect()->back()->with(['error' => [__("Something Went Wrong! Please Try Again")]]);
            }
            //dump($request->first_name);
            curl_close($curl);
        }else{
            $ref = $user->maplerad_customer;
        }
        //dd($user->maplerad_customer);

        // $cardId = $response['data']['id'];
        //dd($user);
        $cardId ='';
        $data=[];
        $addUrl="";
       if($request->type_card=="basic"){
        $data=[
            'customer_id' => $ref,
            'type' => 'VIRTUAL',
            'currency' => $currency,
            'auto_approve'=>true,
            'brand' => $request->card_type,
            'amount' => (int)$request->card_amount*100,
            'card_pin' => '12345678'
        ];
        $addUrl='issuing';
       }else{
        $data=[
            'name' => $request->card_name,
            'type' => 'VIRTUAL',
            'currency' => $currency,
            'auto_approve'=>true,
            'brand' => $request->card_type,
            'amount' => (int)$request->card_amount*100,
        ];
        $addUrl='issuing/business';
       }
        
        // Create a card
        //dd((int)$request->card_amount);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.$addUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>
            json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer ".$secret_key,
                "accept: application/json",
              ]
          ));
          
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        //dump($this->api->config->maplerad_url.'cards');
        
        if (isset($response) && key_exists('status', $response) && $response['status'] ) {
            $cardId = $response['data']['reference'];
            $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.'issuing/'.$cardId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer ".$secret_key,
                "accept: application/json",
              ]
          ));
          
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        if (isset($response) && key_exists('status', $response) && $response['status'] ){
            $trx_id =  'CB'.getTrxNum();
            $sender = $this->insertCadrBuy($trx_id,$user,$wallet,$amount, $cardId ,$payable,true);
            $this->insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$sender, $cardId);
            $v_card = new MapleradVirtualCard();
                
            $card = $response['data'];
            $v_card->user_id=$user->id;
                $v_card->card_id = $card['id'];
                $v_card->expiry = $card['expiry'];
                $v_card->cvv = $card['cvv'];
                $v_card->currency = $card['currency'];
                 $v_card->status = $card['status'];
                $v_card->type = $card['type'];
                $v_card->masked_pan = $card['masked_pan'];
                $v_card->issuer = $card['issuer'];
                $v_card->name = $card['name'];
                $v_card->balance = $card['balance'];
                $v_card->auto_approve = $card['auto_approve'];
                $v_card->card_number = $card['card_number'];
                $v_card->address = $card['address'];
         
            // $v_card->charge =  $total_charge;
            
            $v_card->save();
            $trx_id =  'CB'.getTrxNum();
            try{
                $sender = $this->insertCadrBuy( $trx_id,$user,$wallet,$amount, $v_card ,$payable);
                $this->insertBuyCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$v_card->masked_pan);
                if( $this->basic_settings->email_notification == true){
                    $notifyDataSender = [
                        'trx_id'  => $trx_id,
                        'title'  => __("Virtual Card (Buy Card)"),
                        'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                        'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                        'charges'   => getAmount( $total_charge, 2).' ' .get_default_currency_code(),
                        'card_amount'  => getAmount( $v_card->balance, 2).' ' .get_default_currency_code(),
                        'card_pan'  => $v_card->masked_Pan,
                        'status'  => __("Success"),
                      ];
                      try{
                          $user->notify(new CreateMail($user,(object)$notifyDataSender));
                      }catch(Exception $e){
                        dd($e);
                      }
                }
                //admin notification
                $this->adminNotification($trx_id,$total_charge,$amount,$payable,$user,$v_card);
                return redirect()->route("user.maplerad.virtual.card.index")->with(['success' => [__('Card Buy Successfully')]]);
            }catch(Exception $e){
                dd($e);
                return back()->with(['error' => [__("Something Went Wrong! Please Try Again")]]);
            }
        }else{
            dd($response);
        }
        
        } else {
            $trx_id =  'CB'.getTrxNum();
           // $sender = $this->insertCadrBuy($trx_id,$user,$wallet,$amount, $cardId ,$payable,false);
           // $this->insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$sender, $cardId);
            dd($response);
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
        

      

    }
    public function payPenality(Request $request){
        $request->validate([
            'card_id' => 'required|integer',
        ]);
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $user = auth()->user();
        $myCard =  MapleradVirtualCard::where('user_id',$user->id)->where('id',$request->card_id)->first();
        //dd($myCard);
        $amount=$this->api->penality_price;
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if($amount >$wallet->balance) {
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            return back()->with(['error' => [__('Default Currency Not Setup Yet')]]);
        }
        $trx_id = 'CF'.getTrxNum();
        $sender = $this->insertCardPenality( $trx_id,$user,$wallet,$amount, $myCard ,$amount);
        $this->insertPenalityCardCharge($user,$sender,$myCard->masked_card,$amount);
        $myCard->is_penalize=false;
        $myCard->save();
        $public_key=$this->api->config->maplerad_public_key;
        $secret_key=$this->api->config->maplerad_secret_key;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.'issuing/'.$myCard->card_id."/unfreeze",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
               CURLOPT_HTTPHEADER =>  [
                "Authorization: Bearer ".$secret_key,
                 "accept: application/json",
               ],
        ));

        $result = json_decode(curl_exec($curl), true);
       // return $result;
        curl_close($curl);
        //return $result;
        //return $result;
        
        if (isset($result)&&isset($result['status'])) {
            if ( $result['status'] == true ) {
                $myCard->status='ACTIVE';
                $myCard->save();
                if($this->basic_settings->email_notification == true){
                    $notifyDataSender = [
                        'trx_id'  => $trx_id,
                        'title'  => __("Virtual Card (Card Unbloking)"),
                        'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                        'payable'   =>  getAmount($amount,4).' ' .get_default_currency_code(),
                        'charges'   => getAmount( $amount,2).' ' .get_default_currency_code(),
                        'card_amount'  => getAmount($myCard->balance,2).' ' .get_default_currency_code(),
                        'card_pan'  =>    $myCard->masked_card,
                        'status'  => __("Success"),
                    ];
                    try{
                        $user->notify(new PayPenalityMail($user,(object)$notifyDataSender));
                    }catch(Exception $e){
                        dd($e);
                    }
                $success = ['success' => [__('Card unblock successfully!')]];
                //return Response::success($success,null,200);
            }
        }
        return redirect()->back()->with(['success' => [__('The penalty on your virtual card has been successfully paid.')]]);
        

        }else{
            dd($result);
            return redirect()->back()->with(['error' => [@$response['message']??__($response['message'])]]);
        }
       

    }
    public function cardWithdraw(Request $request){
        $request->validate([
            'id' => 'required|integer',
            'fund_amount' => 'required|numeric|gt:0',
        ]);
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        if (!$this->api->is_withdraw) {
            return back()->with(['error' => [__('withdrawal of money from the card is temporarily disabled for this type of card')]]);
        }
        $user = auth()->user();
        $myCard =  MapleradVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();

        if(!$myCard){
            return back()->with(['error' => [__('Something Is Wrong In Your Card')]]);
        }

        $amount = $request->fund_amount;
        $cardCharge = TransactionSetting::where('slug','withdraw_card_'.auth()->user()->name_api)->where('status',1)->first();
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $wallet = UserWallet::where('user_id',$user->id)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            return back()->with(['error' => [__('Default Currency Not Setup Yet')]]);
        }
       /* $minLimit =  $cardCharge->min_limit *  $rate;
        $maxLimit =  $cardCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            return back()->with(['error' => [__('Please follow the transaction limit')]]);
        }*/
        $fixedCharge = $cardCharge->fixed_charge;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        //dump($cardCharge);
       // dump($total_charge);
        //dd($payable);
        if($payable > $myCard->balance ){
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $public_key=$this->api->config->maplerad_public_key;
        $secret_key=$this->api->config->maplerad_secret_key;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL =>   $this->api->config->maplerad_url.'issuing/'.$myCard->card_id."/withdraw",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>json_encode([
            "amount"=>$payable*100,
          ]),
          CURLOPT_HTTPHEADER =>  [
            "Authorization: Bearer ".$secret_key,
            "accept: application/json",
          ],
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);
        //dd($result);
        $account_amount=$myCard->balance-$amount;
        $authWalle=UserWallet::where('user_id',$user->id)->first();
        if( isset($result) && array_key_exists('status', $result) && $result['status'] == true){
                    //Optimistics update
        $trx_id = 'CF'.getTrxNum();
        //$sender = $this->insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
       // $this->insertFundCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);
            //added fund amount to card
            $myCard->balance -= $payable;
            $myCard->save();
            //
            $sender = $this->insertCardWithdraw( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
            $this->insertWithdrawCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);
            //dd();
            
            if($this->basic_settings->email_notification == true){
                $notifyDataSender = [
                    'trx_id'  => $trx_id,
                    'title'  => __("Virtual Card (Withdraw Amount)"),
                    'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                    'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                    'charges'   => getAmount( $total_charge,2).' ' .get_default_currency_code(),
                    'card_amount'  => getAmount($account_amount,2).' ' .get_default_currency_code(),
                    'account_amount'  => getAmount($authWalle->balance,2).' ' .get_default_currency_code(),
                    'card_pan'  =>    $myCard->masked_card,
                    'card_name'=>'Prenium',
                    'status'  => __("Success"),
                ];
                try{
                    $user->notify(new WithdrawMail($user,(object)$notifyDataSender));
                }catch(Exception $e){
                    dd($e);
                }
            }
            //admin notification
            $this->adminNotificationWithdraw($trx_id,$total_charge,$amount,$payable,$user,$myCard);
            return redirect()->back()->with(['success' => [__('Withdrawed Money Successfully of the card')]]);

        }else{
            //dump($account_amount);
            dd($result);
            if($this->basic_settings->email_notification == true){
                $trx_id = 'CF'.getTrxNum();
                $notifyDataSender = [
                    'trx_id'  => $trx_id,
                    'title'  => __("Virtual Card (Withdraw Amount)"),
                    'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                    'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                    'charges'   => getAmount( $total_charge,2).' ' .get_default_currency_code(),
                    'card_amount'  => getAmount($account_amount,2).' ' .get_default_currency_code(),
                    'account_amount'  => getAmount($authWalle->balance,2).' ' .get_default_currency_code(),
                    'card_pan'  =>    $myCard->masked_card,
                    'card_name'=>'Prenium',
                    'status'  => __("failed"),
                ];
                try{
                    $user->notify(new WithdrawMail($user,(object)$notifyDataSender));
                }catch(Exception $e){
                    dd($e);
                }
        }
            dd($result);
            
            return redirect()->back()->with(['error' => [@$result['responseMessage']??__("Something Went Wrong! Please Try Again")]]);
        }
        //dd($trx_id);
        //dd($sender);

    }
    public function cardFundConfirm(Request $request){
        $request->validate([
            'id' => 'required|integer',
            'fund_amount' => 'required|numeric|gt:0',
        ]);
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        if (!$this->api->is_rechargeable) {
            return back()->with(['error' => [__('card top-up is temporarily disabled for this card type')]]);
        }
        //dd($request);
        $user = auth()->user();
        $myCard =  MapleradVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();

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
        // Get Token
        $public_key=$this->api->config->maplerad_public_key;
        $secret_key=$this->api->config->maplerad_secret_key;

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL =>   $this->api->config->maplerad_url."issuing/".$myCard->card_id.'/fund',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>json_encode([
            "amount"=>((int)$amount)*100,
          ]),
          CURLOPT_HTTPHEADER =>  [
            "Authorization: Bearer ".$secret_key,
            "accept: application/json",
          ],
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);
        
        if( isset($result) && array_key_exists('status', $result) && $result['status'] == true){
            //added fund amount to card
            $myCard->balance += $amount;
            $myCard->save();
            $trx_id = 'CF'.getTrxNum();
            $sender = $this->insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
            $this->insertFundCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);
            if($this->basic_settings->email_notification == true){
                $notifyDataSender = [
                    'trx_id'  => $trx_id,
                    'title'  => __("Virtual Card (Fund Amount)"),
                    'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                    'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                    'charges'   => getAmount( $total_charge,2).' ' .get_default_currency_code(),
                    'card_amount'  => getAmount($myCard->balance,2).' ' .get_default_currency_code(),
                    'card_pan'  =>    $myCard->masked_card,
                    'status'  => __("Success"),
                ];
                try{
                    $user->notify(new Fund($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }
            //admin notification
            $this->adminNotificationFund($trx_id,$total_charge,$amount,$payable,$user,$myCard);
            return redirect()->back()->with(['success' => [__('Card Funded Successfully')]]);

        }else{
            dd($result);
            return redirect()->back()->with(['error' => [@$result['message']??__("Something Went Wrong! Please Try Again")]]);
        }

    }

    public function cardBlockUnBlock(Request $request) {
       // dd($request);
        $validator = Validator::make($request->all(),[
            'status'                    => 'required|boolean',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
       // return $this->api->config;
        $public_key=$this->api->config->maplerad_public_key;
        $secret_key=$this->api->config->maplerad_secret_key;
        $validated = $validator->safe()->all();
        //return $validated;
        if($request->status == 1 ){
            $card = MapleradVirtualCard::where('id',$request->data_target)->first();
            $status = 'block';
            if(!$card){
                $error = ['error' => [__('Something Is Wrong In Your Card')]];
                return Response::error($error,null,404);
            }
             // Get Token
       // return $token;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.'issuing/'.$card->card_id."/freeze",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
              CURLOPT_HTTPHEADER =>  [
                "Authorization: Bearer ".$secret_key,
                "accept: application/json",
              ],
        ));

        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);
        //return $result;
        
            if (isset($result)) {
                if ($result['status'] == true) {
                    $card->status = 'DISABLED';
                    $card->save();
                    $success = ['success' => [__('Card block successfully!')]];
                    return Response::success($success,null,200);
                }  else {
                    $error = ['error' => $result["message"]];
                    return Response::error($error, null, 404);
                }
            }


        }else{
            $card = MapleradVirtualCard::where('id',$request->data_target)->first();
        $status = 'unblock';
        if(!$card){
            $error = ['error' => [__('Something Is Wrong In Your Card')]];
            return Response::error($error,null,404);
        }
        // Get Token
       


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.'issuing/'.$card->card_id."/unfreeze",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PATCH',
               CURLOPT_HTTPHEADER =>  [
                "Authorization: Bearer ".$secret_key,
                 "accept: application/json",
               ],
        ));

        $result = json_decode(curl_exec($curl), true);
        //return $result;
        curl_close($curl);
        //return $result;
        
        if (isset($result)&&isset($result['status'])) {
            if ( $result['status'] == true ) {
                $card->status='ACTIVE';
                $card->save();
                $success = ['success' => [__('Card unblock successfully!')]];
                return Response::success($success,null,200);
            } else{
               // return $result;
                $error = ['error' => [$result['message']]];
                return Response::error($error, null, 404);
            }
        }
        }
    }
    public function cardTransaction($card_id) {
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        $user = auth()->user();
        $card = MapleradVirtualCard::where('user_id',$user->id)->where('card_id', $card_id)->first();
        $page_title = __("Virtual Card Transaction");
        $id = $card->card_id;
        $emptyMessage  = 'No Transaction Found!';
        $start_date = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-12 month" ) );
        $end_date = date('Y-m-d');
        // Get Token
        $curl = curl_init();
        $public_key=$this->api->config->maplerad_public_key;
        $secret_key=$this->api->config->maplerad_secret_key;


        curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api->config->maplerad_url.'issuing/'.$id.'/transactions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER =>[
                "Authorization: Bearer ".$secret_key,
                "accept: application/json",
              ],
        ));

        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        //dd($response);
        if(!isset($response) || !array_key_exists('data', $response)) {
            dd($response);
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        //dd($response);
        $card_truns = array("data"=>$response['data']);
        //dd($card_truns);
        return view('user.sections.virtual-card-maplerad.trx',compact('page_title','card','card_truns'));


    }
    public function makeDefaultOrRemove(Request $request) {
        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $user = auth()->user();
        $targetCard =  MapleradVirtualCard::where('id',$validated['target'])->where('user_id',$user->id)->first();
        $withOutTargetCards =  MapleradVirtualCard::where('id','!=',$validated['target'])->where('user_id',$user->id)->get();
        //dd($targetCard);
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
    public function insertCardWithdraw( $trx_id,$user,$wallet,$amount, $myCard ,$payable) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance + $amount);
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
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::CARDWITHDRAW," ")),
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
    public function insertWithdrawCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$id,$masked_card,$amount) {
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
                'title'         =>"Card Withdraw",
                'message'       => __("Withdraw successful Money of card:")." ".$masked_card.' '.getAmount($amount,2).' '.get_default_currency_code(),
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
    public function insertPenalityCardCharge($user,$id,$masked_card,$amount) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => 0,
                'fixed_charge'      =>0,
                'total_charge'      =>0,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>"Card Unbloking",
                'message'       => __("Unlocking your card successful Card")." : ".$masked_card.' '.getAmount($amount,2).' '.get_default_currency_code(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::PAYPENALITY,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something Went Wrong! Please Try Again"));
        }
    }
    public function insertCardPenality( $trx_id,$user,$wallet,$amount, $myCard ,$payable) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $amount);
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
                'remark'                        => ucwords(remove_speacial_char(PaymentGatewayConst::CARD_PAY_PENALITY," ")),
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
            $card = MapleradVirtualCard::where('card_id', $response->CardId)->first();
            if ($card) {
                $card->balance = $response->balance;
                $card->save();

                //Transactions
                // $vt = new Virtualtransactions();
                // $vt->user_id = $card->user_id;
                // $vt->virtual_card_id = $card->id;
                // $vt->card_id = $card->card_id;
                // $vt->balance = $response->balance;
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
         //admin notification
         public function adminNotification($trx_id,$total_charge,$amount,$payable,$user,$v_card){
            $notification_content = [
                //email notification
                'subject' => __("Virtual Card (Buy Card)"),
                'greeting' => __("Virtual Card Information"),
                'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())."<br>".__("Fees & Charges")." : ".get_amount($total_charge,get_default_currency_code())."<br>".__("Total Payable Amount")." : ".get_amount($payable,get_default_currency_code())."<br>".__("card Masked")." : ".@$v_card->maskedPan."<br>".__("Status")." : ".__("Success"),
    
                //push notification
                'push_title' => __("Virtual Card (Buy Card)")." (".userGuard()['type'].")",
                'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())." ".__("card Masked")." : ".$v_card->maskedPan??"",
    
                //admin db notification
                'notification_type' =>  NotificationConst::CARD_BUY,
                'admin_db_title' => "Virtual Card Buy"." (".userGuard()['type'].")",
                'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,get_default_currency_code()).","."Card Masked"." : ".@$v_card->maskedPan." (".$user->email.")",
            ];
    
            try{
                //notification
                (new NotificationHelper())->admin(['admin.virtual.card.logs'])
                                        ->mail(ActivityNotification::class, [
                                            'subject'   => $notification_content['subject'],
                                            'greeting'  => $notification_content['greeting'],
                                            'content'   => $notification_content['email_content'],
                                        ])
                                        ->push([
                                            'user_type' => "admin",
                                            'title' => $notification_content['push_title'],
                                            'desc'  => $notification_content['push_content'],
                                        ])
                                        ->adminDbContent([
                                            'type' => $notification_content['notification_type'],
                                            'title' => $notification_content['admin_db_title'],
                                            'message'  => $notification_content['admin_db_message'],
                                        ])
                                        ->send();
    
    
            }catch(Exception $e) {}
    
        }
        public function adminNotificationFund($trx_id,$total_charge,$amount,$payable,$user,$myCard){
            $notification_content = [
                //email notification
                'subject' => __("Virtual Card (Fund Amount)"),
                'greeting' => __("Virtual Card Information"),
                'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())."<br>".__("Fees & Charges")." : ".get_amount($total_charge,get_default_currency_code())."<br>".__("Total Payable Amount")." : ".get_amount($payable,get_default_currency_code())."<br>".__("Card Masked")." : ".$myCard->masked_card??""."<br>".__("Status")." : ".__("Success"),
    
                //push notification
                'push_title' => __("Virtual Card (Fund Amount)")." (".userGuard()['type'].")",
                'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())." ".__("Card Masked")." : ".$myCard->masked_card??"",
    
                //admin db notification
                'notification_type' =>  NotificationConst::CARD_FUND,
                'admin_db_title' => "Virtual Card Funded"." (".userGuard()['type'].")",
                'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,get_default_currency_code()).","."Card Masked"." : ".$myCard->masked_card." (".$user->email.")",
            ];
    
            try{
                //notification
                (new NotificationHelper())->admin(['admin.virtual.card.logs'])
                                        ->mail(ActivityNotification::class, [
                                            'subject'   => $notification_content['subject'],
                                            'greeting'  => $notification_content['greeting'],
                                            'content'   => $notification_content['email_content'],
                                        ])
                                        ->push([
                                            'user_type' => "admin",
                                            'title' => $notification_content['push_title'],
                                            'desc'  => $notification_content['push_content'],
                                        ])
                                        ->adminDbContent([
                                            'type' => $notification_content['notification_type'],
                                            'title' => $notification_content['admin_db_title'],
                                            'message'  => $notification_content['admin_db_message'],
                                        ])
                                        ->send();
    
    
            }catch(Exception $e) {}
    
        }
        public function adminNotificationWithdraw($trx_id,$total_charge,$amount,$payable,$user,$myCard){
            $notification_content = [
                //email notification
                'subject' => __("Virtual Card (Withdraw Amount)"),
                'greeting' => __("Virtual Card Information"),
                'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())."<br>".__("Fees & Charges")." : ".get_amount($total_charge,get_default_currency_code())."<br>".__("Total Payable Amount")." : ".get_amount($payable,get_default_currency_code())."<br>".__("Card Masked")." : ".$myCard->masked_card??""."<br>".__("Status")." : ".__("Success"),
    
                //push notification
                'push_title' => __("Virtual Card (Withdraw Amount)")." (".userGuard()['type'].")",
                'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())." ".__("Card Masked")." : ".$myCard->masked_card??"",
    
                //admin db notification
                'notification_type' =>  NotificationConst::CARD_WITHDRAW,
                'admin_db_title' => "Virtual Card Withdrawed"." (".userGuard()['type'].")",
                'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,get_default_currency_code()).","."Card Masked"." : ".$myCard->masked_card." (".$user->email.")",
                
            ];
    
            try{
                //notification
                (new NotificationHelper())->admin(['admin.virtual.card.logs'])
                                        ->mail(ActivityNotification::class, [
                                            'subject'   => $notification_content['subject'],
                                            'greeting'  => $notification_content['greeting'],
                                            'content'   => $notification_content['email_content'],
                                        ])
                                        ->push([
                                            'user_type' => "admin",
                                            'title' => $notification_content['push_title'],
                                            'desc'  => $notification_content['push_content'],
                                        ])
                                        ->adminDbContent([
                                            'type' => $notification_content['notification_type'],
                                            'title' => $notification_content['admin_db_title'],
                                            'message'  => $notification_content['admin_db_message'],
                                        ])
                                        ->send();
    
    
            }catch(Exception $e) {}
    
        }

}
?>
<?php

namespace App\Http\Controllers\User;

use App\Constants\GlobalConst;
use Exception;
use App\Models\UserWallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Models\Admin\Currency;
use App\Models\VirtualCardApi;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\BasicSettings;
use App\Constants\NotificationConst;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\StrowalletVirtualCard;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\NotificationHelper;
use App\Http\Helpers\PushNotificationHelper;
use App\Models\Admin\Admin;
use App\Models\Admin\TransactionSetting;
use App\Models\StrowalletCustomerKyc;
use App\Models\User;
use App\Notifications\Admin\ActivityNotification;
use App\Notifications\User\VirtualCard\CreateMail;
use App\Notifications\User\VirtualCard\Fund;
use App\Notifications\User\Withdraw\WithdrawMail;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;


class StrowalletVirtualController extends Controller
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
        $page_title     = __("Virtual Card");
        $this->api=VirtualCardApi::where('name',Auth::check()?auth()->user()->name_api:Admin::first()->name_api)->first();
        $myCards        = StrowalletVirtualCard::where('user_id', auth()->user()->id)->latest()->limit($this->card_limit)->get();
        $user           = auth()->user();
        $customer_card = '';//$user->strowallet_customer->customerEmail??false;

        /*if($customer_email === false){
            $customer_card  = 0;
        }else{
            $customer_card  = StrowalletVirtualCard::where('customer_email',$customer_email)->count();
        }*/
        if(count($myCards)>0){
            foreach($myCards as $myCard){
                $card_details   = card_details($myCard->card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);
                $myCard->card_status               = $card_details['data']['card_detail']['card_status'];
            $myCard->card_name               = $card_details['data']['card_detail']['card_name'];
            $myCard->card_number               = $card_details['data']['card_detail']['card_number'];
            $myCard->last4                     = $card_details['data']['card_detail']['last4'];
            $myCard->cvv                       = $card_details['data']['card_detail']['cvv'];
            $myCard->expiry                    = $card_details['data']['card_detail']['expiry'];
            $myCard->balance                    = $card_details['data']['card_detail']['balance'];
            $myCard->save();
            }
        }
        $myCards        = StrowalletVirtualCard::where('user_id', auth()->user()->id)->where('is_deleted',false)->latest()->limit($this->card_limit)->get();
        $cardCharge       = TransactionSetting::where('slug','virtual_card_'.auth()->user()->name_api)->where('status',1)->first();
        $cardReloadCharge = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();
        $transactions     = Transaction::auth()->virtualCard()->latest()->take(5)->get();
        $cardWithdrawCharge = TransactionSetting::where('slug','withdraw_card_'.auth()->user()->name_api)->where('status',1)->first();
        $cardApi = $this->api;
        //dd($myCards);
        return view('user.sections.virtual-card-strowallet.index',compact(
            'page_title',
            'cardApi',
            'myCards',
            'transactions',
            'cardCharge',
            'customer_card',
            'cardReloadCharge',
            'cardWithdrawCharge'
        ));
    }
    /**
     * Method for card details
     * @param $card_id
     * @param \Illuminate\Http\Request $request
     */
    public function cardDetails($card_id){
        $page_title = __("Card Details");
        $myCard = StrowalletVirtualCard::where('card_id',$card_id)->first();
        if(!$myCard) return back()->with(['error' => [__("Something is wrong in your card")]]);

       // if($myCard->card_status == 'pending'){
            $card_details   = card_details($card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);

            if($card_details['status'] == false){
                return back()->with(['error' => [__("Your Card Is Pending! Please Contact With Admin")]]);
            }

            $myCard->user_id                   = Auth::user()->id;
            $myCard->card_status               = $card_details['data']['card_detail']['card_status'];
            $myCard->card_name               = $card_details['data']['card_detail']['card_name'];
            $myCard->card_number               = $card_details['data']['card_detail']['card_number'];
            $myCard->last4                     = $card_details['data']['card_detail']['last4'];
            $myCard->cvv                       = $card_details['data']['card_detail']['cvv'];
            $myCard->expiry                    = $card_details['data']['card_detail']['expiry'];
            $myCard->balance                    = $card_details['data']['card_detail']['balance'];
            $myCard->save();
       // }

        $cardApi = $this->api;
        $cardWithdrawCharge = TransactionSetting::where('slug','withdraw_card_'.auth()->user()->name_api)->where('status',1)->first();
        return view('user.sections.virtual-card-strowallet.details',compact(
            'page_title',
            'myCard',
            'cardApi',
            'cardWithdrawCharge'
        ));
    }
    public function deleteCard(Request $request){
        $myCard = StrowalletVirtualCard::where('id',$request->card_id)->first();
        if(!$myCard){
            return back()->with(['error' => [__('Something Is Wrong In Your Card')]]);
        }
        $myCard->is_deleted=true;
        $myCard->save();
        return back()->with(['success' => [__('your card has been successfully deleted')]]);
    }

    /**
     * Method for strowallet card buy page
     */
    public function createPage(){
        $page_title = __("Create Virtual Card");
        $user       = userGuard()['user'];
        $user = User::where('id',$user->id)->first();
        $cardCharge     = TransactionSetting::where('slug','virtual_card_'.auth()->user()->name_api)->where('status',1)->first();
        /*if($user->strowallet_customer != null){
            //get customer api response
            $customer = $user->strowallet_customer;
            if (!isset($customer->customerId) || empty($customer->customerId)) {
                $customer   = (array) $customer;
                $customer['status'] = GlobalConst::CARD_HIGH_KYC_STATUS;
                $user->strowallet_customer = (object) $customer;
                $user->save();

            }else{
                $getCustomerInfo = get_customer($this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$customer->customerId);
                if( $getCustomerInfo['status'] == false){
                    return back()->with(['error' => [$getCustomerInfo['message'] ?? __("Something went wrong! Please try again.")]]);
                }
                $customer               = (array) $customer;
                $customer_status_info   =  $getCustomerInfo['data'];

                foreach ($customer_status_info as $key => $value) {
                    $customer[$key] = $value;
                }
                $user->strowallet_customer = (object) $customer;
                $user->save();
            }
        }*/

        return view('user.sections.virtual-card-strowallet.create',compact('page_title','user','cardCharge'));
    }
    /**
     * Method for strowallet create customer
     */
    public function createCustomer(Request $request){

        $validated = Validator::make($request->all(),[
            'first_name'        => ['required', 'string', 'regex:/^[^0-9\W]+$/'], // First name validation
            'last_name'         => ['required', 'string', 'regex:/^[^0-9\W]+$/'],  // Last name validation
            'customer_email'    => 'required|email',
            'date_of_birth'     => 'required|string',
            'house_number'      => 'required|string',
            'address'           => 'required|string',
            'zip_code'          => 'required|string',
            'id_image_font'     => "required|image|mimes:jpg,png,svg,webp",
            'user_image'        => "required|image|mimes:jpg,png,svg,webp",
        ], [
            'first_name.regex'  => __('The First Name field should only contain letters and cannot start with a number or special character.'),
            'last_name.regex'   => __('The Last Name field should only contain letters and cannot start with a number or special character.'),
        ])->validate();
        $user       = userGuard()['user'];
        $user = User::where('id',$user->id)->first();
        $validated['phone'] = $user->full_mobile;

        try{
            if($user->strowallet_customer == null){
                if($request->hasFile("id_image_font")) {
                    $image = upload_file($validated['id_image_font'],'card-kyc-images');
                    $upload_image = upload_files_from_path_dynamic([$image['dev_path']],'card-kyc-images');
                    $validated['id_image_font']     = $upload_image;
                }

                //user image
                if($request->hasFile("user_image")) {
                    $image = upload_file($validated['user_image'],'card-kyc-images');
                    $upload_image = upload_files_from_path_dynamic([$image['dev_path']],'card-kyc-images');
                    $validated['user_image']     = $upload_image;
                }
                $exist_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
                if($exist_kyc){
                    $exist_kyc->update([
                        'user_id'         =>  $user->id,
                        'face_image'      =>  $validated['user_image'],
                        'id_image'        =>  $validated['id_image_font']
                    ]);
                    $kyc_info = StrowalletCustomerKyc::where('user_id',$user->id)->first();
                }else{
                    //store kyc images
                    $kyc_info = StrowalletCustomerKyc::create([
                        'user_id'         =>  $user->id,
                        'face_image'      =>  $validated['user_image'],
                        'id_image'        =>  $validated['id_image_font']
                    ]);
                }
                $idImage = $kyc_info->idImageData;
                $userPhoto = $kyc_info->faceImageData;

                $validated = Arr::except($validated,['id_image_font','user_image']);
                $createCustomer     = stro_wallet_create_user($validated,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$idImage,$userPhoto);
                if( $createCustomer['status'] == false){
                    $kyc_info->delete();
                    return $this->apiErrorHandle($createCustomer["message"]);

                }
                $user->strowallet_customer =   (object)$createCustomer['data'];
                $user->save();
            }
            return redirect()->route("user.strowallet.virtual.card.create")->with(['success' => [__('Customer has been created successfully.')]]);

        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

    }
    /**
     * Method for strowallet edit customer
     */
    public function editCustomer(){
        $user = userGuard()['user'];
        $user = User::where('id',$user->id)->first();
        if($user->strowallet_customer == null){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }
        $page_title = __("Update Customer Kyc");
        $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
        return view('user.sections.virtual-card-strowallet.edit',compact('page_title','user','customer_kyc'));
    }
    /**
     * Method for strowallet update customer
     */
    public function updateCustomer(Request $request){

        $validated = Validator::make($request->all(),[
            'first_name'        => ['required', 'string', 'regex:/^[^0-9\W]+$/'], // First name validation
            'last_name'         => ['required', 'string', 'regex:/^[^0-9\W]+$/'],  // Last name validation
            'id_image_font'     => "nullable|image|mimes:jpg,png,svg,webp",
            'user_image'        => "nullable|image|mimes:jpg,png,svg,webp",
        ], [
            'first_name.regex'  => __('The First Name field should only contain letters and cannot start with a number or special character.'),
            'last_name.regex'   => __('The Last Name field should only contain letters and cannot start with a number or special character.'),
        ])->validate();
        $user       = userGuard()['user'];
        $user = User::where('id',$user->id)->first();

        try{
            if($user->strowallet_customer != null){
                $customer_kyc = StrowalletCustomerKyc::where('user_id',$user->id)->first();
                if($request->hasFile("id_image_font")) {
                    $id_image = upload_file($validated['id_image_font'],'card-kyc-images',);
                    $upload_image = upload_files_from_path_dynamic([$id_image['dev_path']],'card-kyc-images',$customer_kyc->id_image??null);
                    // delete_file($id_image['dev_path']);
                    $validated['id_image_font']     = $upload_image;
                }

                //user image
                if($request->hasFile("user_image")) {
                    $user_image = upload_file($validated['user_image'],'card-kyc-images',$customer_kyc->face_image??null);
                    $upload_image = upload_files_from_path_dynamic([$user_image['dev_path']],'card-kyc-images');
                    // delete_file($user_image['dev_path']);
                    $validated['user_image']     = $upload_image;
                }

                //store kyc images
                if($customer_kyc){
                    $customer_kyc->update([
                        'user_id'         =>  $user->id,
                        'id_image'        =>  $validated['id_image_font'] ?? $customer_kyc->id_image,
                        'face_image'      =>  $validated['user_image'] ??$customer_kyc->face_image
                    ]);
                }else{
                    $customer_kyc = StrowalletCustomerKyc::create([
                        'user_id'         =>  $user->id,
                        'id_image'        =>  $validated['id_image_font'],
                        'face_image'      =>  $validated['user_image']
                    ]);
                }

                $idImage = $customer_kyc->idImageData;
                $userPhoto = $customer_kyc->faceImageData;

                $validated = Arr::except($validated,['id_image_font','user_image']);
                $updateCustomer     = update_customer($validated,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$idImage,$userPhoto,$user->strowallet_customer);
                if( $updateCustomer['status'] == false){
                    $customer_kyc->delete();
                    return $this->apiErrorHandle($updateCustomer["message"]);
                }

                 //get customer api response
                $customer = $user->strowallet_customer;
                $getCustomerInfo = get_customer($this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$updateCustomer['data']['customerId']);
                if($getCustomerInfo['status'] == false){
                    $customer_kyc->delete();
                    return back()->with(['error' => [$getCustomerInfo['message'] ?? __("Something went wrong! Please try again.")]]);
                }
                $customer               = (array) $customer;
                $customer_status_info   =  $getCustomerInfo['data'];

                foreach ($customer_status_info as $key => $value) {
                    $customer[$key] = $value;
                }
                $user->strowallet_customer = (object) $customer;
                $user->save();

            }else{
                return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
            }
            return redirect()->back()->with(['success' => [__('Customer has been updated successfully.')]]);

        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

    }

    /**
     * Method for strowallet card buy
     */
    public function cardBuy(Request $request){
        $this->api=VirtualCardApi::where('name',auth()->user()->name_api)->first();
        if (!$this->api->is_created_card) {
            return back()->with(['error' => [__('the card purchase is temporary deactivate for this type of card')]]);
        }
        $user = auth()->user();
        $user = User::where('id',$user->id)->first();
        $request->validate([
            'card_amount'       => 'required|numeric|gt:0',
            'name_on_card'      => 'required|string|min:4|max:50',
        ]);

        $formData   = $request->all();

        $amount = $request->card_amount;
        $basic_setting = BasicSettings::first();
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if(!$wallet){
            return back()->with(['error' => [__('User wallet not found')]]);
        }
        $cardCharge = TransactionSetting::where('slug','virtual_card_'.auth()->user()->name_api)->where('status',1)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            return back()->with(['error' => [__('Default currency not found')]]);
        }
        $minLimit =  $cardCharge->min_limit *  $rate;
        $maxLimit =  $cardCharge->max_limit *  $rate;
        if($amount < $minLimit || $amount > $maxLimit) {
            return back()->with(['error' => [__("Please follow the transaction limit")]]);
        }
        //charge calculations
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        if($payable > $wallet->balance ){
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }
        $customer = $user->strowallet_customer;
        if(!$customer){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }

        $customer_email =$this->api->config->strowallet_email; //$user->strowallet_customer->customerEmail??false;
        //dd($customer_email);
        if($customer_email === false){
            $customer_card  = 0;
        }else{
            $customer_card  = StrowalletVirtualCard::where('customer_email',$customer_email)->count();
        }

        if($customer_card >= $this->card_limit){
            return back()->with(['error' => [__("Sorry! You can not create more than")." ".$this->card_limit ." ".__("card using the same email address.")]]);
        }

        // for live code
        $created_card = create_strowallet_virtual_card($user,$request->card_amount,$customer_email,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url,$formData);
        if($created_card['status'] == false){
            dd($created_card);
            return back()->with(['error' => [$created_card['message'] .' ,'.__('Please Contact With Administration.')]]);
        }
        
       


        $strowallet_card                            = new StrowalletVirtualCard();
        $strowallet_card->user_id                   = $user->id;
        $strowallet_card->name_on_card              = $created_card['data']['name_on_card'];
        $strowallet_card->card_id                   = $created_card['data']['card_id'];
        $strowallet_card->card_created_date         = $created_card['data']['card_created_date'];
        $strowallet_card->card_type                 = $created_card['data']['card_type'];
        $strowallet_card->card_brand                = "visa";
        $strowallet_card->card_user_id              = $created_card['data']['card_user_id'];
        $strowallet_card->reference                 = $created_card['data']['reference'];
        $strowallet_card->card_status               = $created_card['data']['card_status'];
        $strowallet_card->customer_id               = $created_card['data']['customer_id'];
        $strowallet_card->customer_email            = $customer->customerEmail;
        $strowallet_card->save();
        $card_details   = card_details($strowallet_card->card_id,$this->api->config->strowallet_public_key,$this->api->config->strowallet_url);
        $strowallet_card->card_status               = $card_details['data']['card_detail']['card_status'];
        $strowallet_card->card_name               = $card_details['data']['card_detail']['card_name'];
        $strowallet_card->card_number               = $card_details['data']['card_detail']['card_number'];
        $strowallet_card->last4                     = $card_details['data']['card_detail']['last4'];
        $strowallet_card->cvv                       = $card_details['data']['card_detail']['cvv'];
        $strowallet_card->expiry                    = $card_details['data']['card_detail']['expiry'];
        $strowallet_card->balance                   = $amount;
        $strowallet_card->save();

        $trx_id =  'CB'.getTrxNum();
        try{
            $sender = $this->insertCardBuy( $trx_id,$user,$wallet,$amount, $strowallet_card ,$payable);
            $this->insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$sender,$strowallet_card->card_number);
            if($basic_setting->email_notification == true){
                $notifyDataSender = [
                    'trx_id'  => $trx_id,
                    'title'  => "Virtual Card (Buy Card)",
                    'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                    'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                    'charges'   => getAmount( $total_charge, 2).' ' .get_default_currency_code(),
                    'card_amount'  => getAmount($amount, 2).' ' .get_default_currency_code(),
                    'card_pan'  =>  "---- ----- ---- ----",
                    'status'  =>  $strowallet_card->card_status??"",
                    ];
                try{
                    $user->notify(new CreateMail($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }
            //admin notification
            $this->adminNotification($trx_id,$total_charge,$amount,$payable,$user,$strowallet_card);
            return redirect()->route("user.strowallet.virtual.card.index")->with(['success' => [__('Virtual Card Buy Successfully')]]);
        }catch(Exception $e){
            return back()->with(['error' => [__("Something went wrong! Please try again.")]]);
        }


    }
    public function insertCardBuy( $trx_id,$user,$wallet,$amount, $strowallet_card ,$payable) {
        $trx_id = $trx_id;
        $authWallet = $wallet;
        $afterCharge = ($authWallet->balance - $payable);
        $details =[
            'card_info' =>   $strowallet_card??''
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
            throw new Exception(__("Something went wrong! Please try again."));
        }
        return $id;
    }
    public function insertBuyCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$id,$card_number) {
        DB::beginTransaction();
        try{
            DB::table('transaction_charges')->insert([
                'transaction_id'    => $id,
                'percent_charge'    => $percent_charge,
                'fixed_charge'      => $fixedCharge,
                'total_charge'      => $total_charge,
                'created_at'        => now(),
            ]);
            DB::commit();

            //notification
            $notification_content = [
                'title'         =>'Buy Card',
                'message'       => 'Buy card successful'." ".$card_number,
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_BUY,
                'user_id'   => $user->id,
                'message'   => $notification_content,
            ]);
            //Push Notifications
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    //update user balance
    public function updateSenderWalletBalance($authWallet,$afterCharge) {
        $authWallet->update([
            'balance'   => $afterCharge,
        ]);
    }
    /**
     * card freeze unfreeze
     */

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
        //dump($validated);
        if($request->status == 1){

            $card   = StrowalletVirtualCard::where('id',$request->data_target)->where('is_active',1)->first();
            $client = new \GuzzleHttp\Client();
            $public_key     = $this->api->config->strowallet_public_key;
            $base_url       = $this->api->config->strowallet_url;

            $response = $client->request('POST', $base_url.'action/status/?action=freeze&card_id='.$card->card_id.'&public_key='.$public_key, [
            'headers' => [
                'accept' => 'application/json',
            ],
            ]);

            $result = $response->getBody();
            $data  = json_decode($result, true);

            if( isset($data['status']) ){
                $card->is_active = 0;
                $card->card_status="frozen";
                $card->save();
                $success = ['success' => [__('Card Freeze successfully')]];
                return Response::success($success,null,200);
            }else{
                $error = ['error' =>  [$data,$validated]];
                return Response::error($error,null,400);
            }
        }else{

            $card   = StrowalletVirtualCard::where('id',$request->data_target)->first();
            $client = new \GuzzleHttp\Client();
            $public_key     = $this->api->config->strowallet_public_key;
            $base_url       = $this->api->config->strowallet_url;

            $response = $client->request('POST', $base_url.'action/status/?action=unfreeze&card_id='.$card->card_id.'&public_key='.$public_key, [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);
            $result = $response->getBody();
            $data  = json_decode($result, true);
            if(isset($data['status'])){
                $card->is_active = 1;
                $card->card_status="active";
                $card->save();
                $success = ['success' => [__('Card UnFreeze successfully')]];
                return Response::success($success,null,200);
            }else{
                $error = ['error' =>  [$data,$validated]];
                return Response::error($error,null,400);
            }
        }

    }
    public function makeDefaultOrRemove(Request $request) {
        $validated = Validator::make($request->all(),[
            'target'        => "required|numeric",
        ])->validate();
        $user = auth()->user();
        $targetCard =  StrowalletVirtualCard::where('id',$validated['target'])->where('user_id',$user->id)->first();
        $withOutTargetCards =  StrowalletVirtualCard::where('id','!=',$validated['target'])->where('user_id',$user->id)->get();

        try{
            $targetCard->update([
                'is_default'         => $targetCard->is_default ? 0 : 1,
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
    /**
     * Card Fund
     */
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
        $myCard =  StrowalletVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();

        if(!$myCard){
            return back()->with(['error' => [__('Something Is Wrong In Your Card')]]);
        }

        $amount = $request->fund_amount;
        $cardCharge = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();
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
            $public_key     = $this->api->config->strowallet_public_key;
        $base_url       = $this->api->config->strowallet_url;
        $mode           = $this->api->config->strowallet_mode??GlobalConst::SANDBOX;
        $form_params    = [
            'card_id'       => $myCard->card_id,
            'amount'        => $amount,
            'public_key'    => $public_key
        ];
        //dd($myCard->card_id);

        $client = new \GuzzleHttp\Client();

        $response               = $client->request('POST', $base_url.'card_withdraw/?&card_id='.$myCard->card_id.'&amount='.$amount.'&public_key='.$public_key, [
            'headers'           => [
                'accept'        => 'application/json',
            ],
        ]);

        $result         = $response->getBody();
        $decodedResult  = json_decode($result, true);
            
            if(!empty($decodedResult['success'])  && $decodedResult['success'] === true){
                //added fund amount to card
                $myCard->balance -= $payable;
                $myCard->save();
                $trx_id = 'CF'.getTrxNum();
                //
                $sender = $this->insertCardWithdraw( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
                $this->insertWithdrawCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->masked_card,$amount);
                $authWalle=UserWallet::where('user_id',$user->id);
            
                if($this->basic_settings->email_notification == true){
                    $notifyDataSender = [
                        'trx_id'  => $trx_id,
                        'title'  => __("Virtual Card (Withdraw Amount)"),
                        'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                        'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                        'charges'   => getAmount( $total_charge,2).' ' .get_default_currency_code(),
                        'card_amount'  => getAmount($myCard->balance-$amount,2).' ' .get_default_currency_code(),
                        'account_amount'  => getAmount($authWalle->balance,2).' ' .get_default_currency_code(),
                        'card_pan'  =>    $myCard->masked_card,
                        'status'  => __("Success"),
                    ];
                    try{
                        $user->notify(new WithdrawMail($user,(object)$notifyDataSender));
                    }catch(Exception $e){}
                }
                //admin notification
                //$this->adminNotificationWithdraw($trx_id,$total_charge,$amount,$payable,$user,$myCard);
                return redirect()->back()->with(['success' => [__('Card Funded Successfully')]]);
    
            }else{
                dd($decodedResult);
                return redirect()->back()->with(['error' => [@$result['message']??__("Something Went Wrong! Please Try Again")]]);
            }
        }
        

    public function cardFundConfirm(Request $request){
        $request->validate([
            'id' => 'required|integer',
            'fund_amount' => 'required|numeric|gt:0',
        ]);
        $user = User::where('id',auth()->user()->id)->first();
        $myCard =  StrowalletVirtualCard::where('user_id',$user->id)->where('id',$request->id)->first();
        if(!$myCard){
            return back()->with(['error' => [__("Something is wrong in your card")]]);
        }

        $amount = $request->fund_amount;
        $wallet = UserWallet::where('user_id',$user->id)->first();
        if(!$wallet){
            return back()->with(['error' => [__('User wallet not found')]]);
        }
        $cardCharge = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();
        $baseCurrency = Currency::default();
        $rate = $baseCurrency->rate;
        if(!$baseCurrency){
            return back()->with(['error' => [__('Default currency not found')]]);
        }
        $fixedCharge = $cardCharge->fixed_charge *  $rate;
        $percent_charge = ($amount / 100) * $cardCharge->percent_charge;
        $total_charge = $fixedCharge + $percent_charge;
        $payable = $total_charge + $amount;
        if($payable > $wallet->balance ){
            return back()->with(['error' => [__('Sorry, insufficient balance')]]);
        }

        $public_key     = $this->api->config->strowallet_public_key;
        $base_url       = $this->api->config->strowallet_url;
        $mode           = $this->api->config->strowallet_mode??GlobalConst::SANDBOX;
        $form_params    = [
            'card_id'       => $myCard->card_id,
            'amount'        => $amount,
            'public_key'    => $public_key
        ];
        if ($mode === GlobalConst::SANDBOX) {
            $form_params['mode'] = "sandbox";
        }

        $client = new \GuzzleHttp\Client();

        $response               = $client->request('POST', $base_url.'fund-card/', [
            'headers'           => [
                'accept'        => 'application/json',
            ],
            'form_params'       => $form_params,
        ]);

        $result         = $response->getBody();
        $decodedResult  = json_decode($result, true);

        if(!empty($decodedResult['success'])  && $decodedResult['success'] === true){

            //added fund amount to card
            $myCard->balance += $amount;
            $myCard->save();
            $trx_id = 'CF'.getTrxNum();
            $sender = $this->insertCardFund( $trx_id,$user,$wallet,$amount, $myCard ,$payable);
            $this->insertFundCardCharge( $fixedCharge,$percent_charge, $total_charge,$user,$sender,$myCard->card_number,$amount);
            if($this->basic_settings->email_notification == true){
                $notifyDataSender = [
                    'trx_id'  => $trx_id,
                    'title'  => "Virtual Card (Fund Amount)",
                    'request_amount'  => getAmount($amount,4).' '.get_default_currency_code(),
                    'payable'   =>  getAmount($payable,4).' ' .get_default_currency_code(),
                    'charges'   => getAmount( $total_charge,2).' ' .get_default_currency_code(),
                    'card_amount'  => getAmount($myCard->balance,2).' ' .get_default_currency_code(),
                    'card_pan'  =>    $myCard->card_number??"---- ----- ---- ----",
                    'status'  => "Success",
                ];
                try{
                    $user->notify(new Fund($user,(object)$notifyDataSender));
                }catch(Exception $e){}
            }
            //admin notification
            $this->adminNotificationFund($trx_id,$total_charge,$amount,$payable,$user,$myCard);
            return redirect()->back()->with(['success' => [__('Card Funded Successfully')]]);

        }else{
            //dd($decodedResult);
            return redirect()->back()->with(['error' => [@$decodedResult['message'].' ,'.__('Please Contact With Administration.')]]);
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
            throw new Exception(__("Something went wrong! Please try again."));
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
    public function insertFundCardCharge($fixedCharge,$percent_charge, $total_charge,$user,$id,$card_number,$amount) {
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
                'message'       => __("Card fund successful card")." : ".$card_number.' '.getAmount($amount,2).' '.get_default_currency_code(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::CARD_FUND,
                'user_id'  => $user->id,
                'message'   => $notification_content,
            ]);
           //Push Notifications
            if( $this->basic_settings->push_notification == true){
                try{
                    (new PushNotificationHelper())->prepare([$user->id],[
                        'title' => $notification_content['title'],
                        'desc'  => $notification_content['message'],
                        'user_type' => 'user',
                    ])->send();
                }catch(Exception $e) {}
            }
        }catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__("Something went wrong! Please try again."));
        }
    }
    /**
     * Transactions
     */
    public function cardTransaction($card_id) {
        $user = auth()->user();
        $card = StrowalletVirtualCard::where('user_id',$user->id)->where('card_id', $card_id)->first();
        $page_title = __("Virtual Card Transaction");
        $id = $card->card_id;
        $emptyMessage  = 'No Transaction Found!';
        $start_date = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-12 month" ) );
        $end_date = date('Y-m-d');
        $curl = curl_init();
        $public_key     = $this->api->config->strowallet_public_key;
        $base_url       = $this->api->config->strowallet_url;

        curl_setopt_array($curl, [
        CURLOPT_URL => $base_url . "card-transactions/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            'public_key' => $public_key,
            'card_id' => $card->card_id,
        ]),
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "content-type: application/json"
        ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        $result  = json_decode($response, true);

        if( isset($result['success']) && $result['success'] == true ){
            $data =[
                'status'        => true,
                'message'       => "Card Details Retrieved Successfully.",
                'data'          => $result['response'],
            ];
        }else{
            $data =[
                'status'        => false,
                'message'       => $result['message'] ?? 'Something is wrong! Contact With Admin',
                'data'          => null,
            ];
        }

        return view('user.sections.virtual-card-strowallet.trx',compact('page_title','card','data'));
    }
    //admin notification
    public function adminNotification($trx_id,$total_charge,$amount,$payable,$user,$v_card){
        $notification_content = [
            //email notification
            'subject' => __("Virtual Card (Buy Card)"),
            'greeting' => __("Virtual Card Information"),
            'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())."<br>".__("Fees & Charges")." : ".get_amount($total_charge,get_default_currency_code())."<br>".__("Total Payable Amount")." : ".get_amount($payable,get_default_currency_code())."<br>".__("card Masked")." : ".$v_card->card_number??"---- ----- ---- ----"."<br>".__("Status")." : ".__("Success"),

            //push notification
            'push_title' => __("Virtual Card (Buy Card)")." (".userGuard()['type'].")",
            'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())." ".__("card Masked")." : ".$v_card->card_number??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::CARD_BUY,
            'admin_db_title' => "Virtual Card Buy"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,get_default_currency_code()).","."Card Masked"." : ".$v_card->card_number??"---- ----- ---- ----"." (".$user->email.")",
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
            'email_content' =>__("TRX ID")." : ".$trx_id."<br>".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())."<br>".__("Fees & Charges")." : ".get_amount($total_charge,get_default_currency_code())."<br>".__("Total Payable Amount")." : ".get_amount($payable,get_default_currency_code())."<br>".__("card Masked")." : ".$myCard->masked_card??"---- ----- ---- ----"."<br>".__("Status")." : ".__("Success"),

            //push notification
            'push_title' => __("Virtual Card (Fund Amount)")." (".userGuard()['type'].")",
            'push_content' => __('TRX ID')." : ".$trx_id." ".__("Request Amount")." : ".get_amount($amount,get_default_currency_code())." ".__("card Masked")." : ".$myCard->masked_card??"---- ----- ---- ----",

            //admin db notification
            'notification_type' =>  NotificationConst::CARD_FUND,
            'admin_db_title' => "Virtual Card Funded"." (".userGuard()['type'].")",
            'admin_db_message' => "Transaction ID"." : ".$trx_id.",".__("Request Amount")." : ".get_amount($amount,get_default_currency_code()).","."Card Masked"." : ".$myCard->card_number??"---- ----- ---- ----"." (".$user->email.")",
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

    public function apiErrorHandle($apiErrors){
        $error = ['error' => []];
        if (isset($apiErrors)) {
            if (is_array($apiErrors)) {
                foreach ($apiErrors as $field => $messages) {
                    if (is_array($messages)) {
                        foreach ($messages as $message) {
                            $error['error'][] = $message;
                        }
                    } else {
                        $error['error'][] = $messages;
                    }
                }
            } else {
                $error['error'][] = $apiErrors;
            }
        }
        $errorMessages = array_map(function($message) {
            return rtrim($message, '.');
        }, $error['error']);

        $errorString = implode(', ', $errorMessages);
        $errorString .= '.';
        return back()->with(['error' => [$errorString ?? __("Something went wrong! Please try again.")]]);

    }


}

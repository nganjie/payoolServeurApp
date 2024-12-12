<?php

namespace App\Http\Controllers\Admin;

use App\Constants\GlobalConst;
use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Events\User\NotificationEvent;
use Exception;
use App\Models\User;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserMailLog;
use App\Notifications\User\SendMail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\Response;
use App\Models\Contact;
use App\Models\EversendVirtualCard;
use App\Models\SoleaspayVirtualCard;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\UserWallet;
use App\Models\VirtualCardApi;
use App\Notifications\User\Kyc\Approved;
use App\Notifications\User\Kyc\Rejected;
use App\Providers\Admin\BasicSettingsProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

class UserCareController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $basic_settings;
    public function __construct()
    {
        $this->basic_settings = BasicSettingsProvider::get();
    }

    public function index()
    {
        $page_title = __("All Users");
        $users = User::orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }

    /**
     * Display Active Users
     * @return view
     */
    public function active()
    {
        $page_title = __("Active Users");
        $users = User::active()->orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }


    /**
     * Display Banned Users
     * @return view
     */
    public function banned()
    {
        $page_title = __("Banned Users");
        $users = User::banned()->orderBy('id', 'desc')->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users',
        ));
    }

    /**
     * Display Email Unverified Users
     * @return view
     */
    public function emailUnverified()
    {
        $page_title = __("Email Unverified Users");
        $users = User::active()->orderBy('id', 'desc')->emailUnverified()->paginate(12);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }

    /**
     * Display SMS Unverified Users
     * @return view
     */
    public function SmsUnverified()
    {
        $page_title = __("SMS Unverified Users");
        return view('admin.sections.user-care.index', compact(
            'page_title',
        ));
    }

    /**
     * Display KYC Unverified Users
     * @return view
     */
    public function KycUnverified()
    {
        $page_title =__( "KYC Unverified Users");
        $users = User::kycUnverified()->orderBy('id', 'desc')->paginate(8);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }
    public function KycPending()
    {
        $page_title =__( "KYC Pending Users");
        $users = User::KycPending()->orderBy('id', 'desc')->paginate(8);
        //dd($users);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }
    public function KycVerified()
    {
        $page_title =__( "KYC Verified Users");
        $users = User::kycVerified()->orderBy('id', 'desc')->paginate(8);
        return view('admin.sections.user-care.index', compact(
            'page_title',
            'users'
        ));
    }

    /**
     * Display Send Email to All Users View
     * @return view
     */
    public function emailAllUsers()
    {
        $page_title = __("Email To Users");
        return view('admin.sections.user-care.email-to-users', compact(
            'page_title',
        ));
    }
    public function showAddCardUser()
    {
        $users=User::all();
        $apis=VirtualCardApi::where('is_active',true)->get();
        $page_title = __("Add Card To User");
        return view('admin.sections.user-care.add-card-to-user', compact(
            'page_title',
            'users',
            'apis'
        ));
    }
    public function showCopyEmailUser(){
        $page_title = __("Copy Mail Users");
        return view('admin.sections.user-care.copy-email-users', compact(
            'page_title',
        ));
    }

    /**
     * Display Specific User Information
     * @return view
     */
    public function userDetails($username)
    {
        $page_title = __("User Details");
        $user = User::where('username', $username)->first();
        if(!$user) return back()->with(['error' => [__('Opps! User not exists')]]);

        $balance = UserWallet::where('user_id', $user->id)->first()->balance ?? 0;
        $add_money_amount = Transaction::toBase()->where('user_id', $user->id)->where('type', PaymentGatewayConst::TYPEADDMONEY)->where('status', 1)->sum('request_amount');
        $total_transaction = Transaction::toBase()->where('user_id', $user->id)->where('status', 1)->sum('request_amount');

        $data = [
            'balance'              => $balance,
            'total_transaction'    => $total_transaction,
            'add_money_amount'    => $add_money_amount,
        ];
        return view('admin.sections.user-care.details', compact(
            'page_title',
            'user',
            'data',
        ));
    }
    public function copyEmailContact(){
        $contacts =Contact::select(['name','email'])->get();
        $csvExporter = new \Laracsv\Export();
        $csvExporter->build($contacts, ['name','email'])->download();

    }
    public function sendCopyMailUsers(Request $request){
        //dd($request);
        $request->validate([
            'user_type'     => "required|string|max:30",
        ]);
        
        $users = [];
        switch($request->user_type) {
            case "active";
                $users = User::active()->get();
                break;
            case "all";
                $users = User::select(['username','email'])->get();
                break;
            case "email_verified";
                $users = User::emailVerified()->select(['username','email'])->get();
                break;
                case "kyc_verified";
                $users = User::kycVerified()->get();
                break;
            case "kyc_unverified";
                $users = User::kycUnerified()->get();
                break;
            case "kyc_pending";
                $users = User::KycPending()->get();
                break;
            case "banned";
                $users = User::banned()->select(['username','email'])->get();
                break;
        }

        try{
            //dd($users);
            $csvExporter = new \Laracsv\Export();
           $csvExporter->build($users, ['username','email'])->download();

        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

       // return back()->with(['success' => [__('Email successfully sended')]]);

    }

    public function sendMailUsers(Request $request) {
       // dd($request);
        $request->validate([
            'user_type'     => "required|string|max:30",
            'subject'       => "required|string|max:250",
            'message'       => "required|string|max:2000",
        ]);

        $users = [];
        switch($request->user_type) {
            case "active";
                $users = User::active()->get();
                break;
            case "all";
                $users = User::get();
                break;
            case "email_verified";
                $users = User::emailVerified()->get();
                break;
            case "kyc_verified";
                $users = User::kycVerified()->get();
                break;
            case "kyc_unverified";
                $users = User::kycUnerified()->get();
                break;
            case "kyc_pending";
                $users = User::KycPending()->get();
                break;
            case "banned";
                $users = User::banned()->get();
                break;
        }

        try{
            //dd($users);
            Notification::send($users,new SendMail((object) $request->all()));
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('Email successfully sended')]]);

    }
    public function addCardUser(Request $request) {
        $request->validate([
            'user'     => "required|integer",
            'api_type'       => "required|integer",
            'card_code'       => "required|string|max:2000",
        ]);

        //dd($request);
        $api=VirtualCardApi::find($request->api_type);
        
       $user=User::find($request->user);
       //dd($user);
        try{
            if($api->name=='soleaspay')
            {
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
                    "public_apikey" : "'. $api->config->soleaspay_public_key .'",
                    "private_secretkey" : "'.$api->config->soleaspay_secret_key.'"
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
                //dd($token);
                $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $api->config->soleaspay_url.$request->card_code,
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
                        $myCard = new SoleaspayVirtualCard();
                        $myCard->grade = $card['grade'];
                        $myCard->category = $card['category'];
                        $myCard->pin = $card['pin'];
                        $myCard->account_id = $card['ref'];
                        $myCard->card_pan = $card['card_pan'];
                        $myCard->masked_card = $card['masked_pan'];
                        $myCard->cvv = $card['cvv'];
                        $myCard->card_type = $card['card_type'];
                        $myCard->ref_id = $card['card_id'];
                        $myCard->expiration = $card['expired_at'];
                        $myCard->amount =  $card['balance'];
                        $myCard->currency = $card['currency'];
                        $myCard->card_id=$user->id;
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
                
            }else if($api->name=='eversend'){
                $public_key=$api->config->eversend_public_key;
                $secret_key=$api->config->eversend_secret_key;
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
                //dd($this->api);
                $token = $response['token'];
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $api->config->eversend_url.$request->card_code,
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
                if ( isset($response) && key_exists('success', $response) && $response['success'] == true ) {
                    $myCard=new EversendVirtualCard();
                    //dd($response);
                    $myCard->user_id=$user->id;
                    

                    $card = $response['data']['card'];
                    $myCard->card_id=$card['id'];
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
                    if(isset($card['lastUsedOn']))
                    $myCard->last_used_on = $card['lastUsedOn'];
                    $myCard->billing_address = $card['billingAddress'];
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
                    //dd($myCard);
                    $myCard->save();
    
                }
           
            }
            //dd($api);
    
            //Notification::send($users,new SendMail((object) $request->all()));
        }catch(Exception $e) {
            dd($e);
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('Card Added successfully To User')]]);

    }

    public function sendMail(Request $request, $username)
    {
        $request->merge(['username' => $username]);
        $validator = Validator::make($request->all(),[
            'subject'       => 'required|string|max:200',
            'message'       => 'required|string|max:2000',
            'username'      => 'required|string|exists:users,username',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with("modal","email-send");
        }
        $validated = $validator->validate();
        $user = User::where("username",$username)->first();
        $validated['user_id'] = $user->id;
        $validated = Arr::except($validated,['username']);
        $validated['method']   = "SMTP";
        try{
            UserMailLog::create($validated);
            $user->notify(new SendMail((object) $validated));
        }catch(Exception $e) {
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('Mail successfully sended')]]);
    }

    public function userDetailsUpdate(Request $request, $username)
    {
        $request->merge(['username' => $username]);
        //dump($request);
        $validator = Validator::make($request->all(),[
            'username'              => "nullable|exists:users,username",
            'firstname'             => "required|string|max:60",
            'lastname'              => "required|string|max:60",
            'mobile_code'           => "required|string|max:10",
            'mobile'                => "required|string|max:20",
            'address'               => "nullable|string|max:250",
            'country'               => "nullable|string|max:50",
            'state'                 => "nullable|string|max:50",
            'city'                  => "nullable|string|max:50",
            'zip_code'              => "nullable|numeric|max_digits:8",
            'email_verified'        => 'required|boolean',
            'two_factor_verified'   => 'nullable|boolean',
            'kyc_verified'          => 'nullable|boolean',
            'status'                => 'required|boolean',
        ]);
        $validated = $validator->validate();
        $validated['address']  = [
            'country'       => $validated['country'] ?? "",
            'state'         => $validated['state'] ?? "",
            'city'          => $validated['city'] ?? "",
            'zip'           => $validated['zip_code'] ?? "",
            'address'       => $validated['address'] ?? "",
        ];
        $validated['mobile_code']       = remove_speacial_char($validated['mobile_code']);
        $validated['mobile']            = remove_speacial_char($validated['mobile']);
        $validated['full_mobile']       = $validated['mobile_code'] . $validated['mobile'];

        $user = User::where('username', $username)->first();
        if(!$user) return back()->with(['error' => [__('Opps! User not exists')]]);

        try {
            $user->update($validated);
        } catch (Exception $e) {

            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('Profile Information Updated Successfully!')]]);
    }

    public function loginLogs($username)
    {
        $page_title = __("Login Logs");
        $user = User::where("username",$username)->first();
        if(!$user) return back()->with(['error' => [__("Opps! User doesn't exists")]]);
        $logs = UserLoginLog::where('user_id',$user->id)->paginate(12);
        return view('admin.sections.user-care.login-logs', compact(
            'logs',
            'page_title',
        ));
    }

    public function mailLogs($username) {
        $page_title = __( "User Email Logs");
        $user = User::where("username",$username)->first();
        if(!$user) return back()->with(['error' => [__("Opps! User doesn't exists")]]);
        $logs = UserMailLog::where("user_id",$user->id)->paginate(12);
        return view('admin.sections.user-care.mail-logs',compact(
            'page_title',
            'logs',
        ));
    }

    public function loginAsMember(Request $request,$username) {
        $request->merge(['username' => $username]);
        $request->validate([
            'target'            => 'required|string|exists:users,username',
            'username'          => 'required_without:target|string|exists:users',
        ]);

        try{
            $user = User::where("username",$request->username)->first();
            Auth::guard("web")->login($user);
        }catch(Exception $e) {
            return back()->with(['error' => [$e->getMessage()]]);
        }
        return redirect()->intended(route('user.dashboard'));
    }

    public function kycDetails($username) {
        $user = User::where("username",$username)->first();
        if(!$user) return back()->with(['error' => [__("Opps! User doesn't exists")]]);

        $page_title =__("KYC Profile");
        return view('admin.sections.user-care.kyc-details',compact("page_title","user"));
    }

    public function kycApprove(Request $request, $username) {
        $request->merge(['username' => $username]);
        $request->validate([
            'target'        => "required|exists:users,username",
            'username'      => "required_without:target|exists:users,username",
        ]);
        $user = User::where('username',$request->target)->orWhere('username',$request->username)->first();
        if($user->kyc_verified == GlobalConst::VERIFIED) return back()->with(['warning' => ['User already KYC verified']]);
        if($user->kyc == null) return back()->with(['error' => ['User KYC information not found']]);

        try{
            $user->update([
                'kyc_verified'  => GlobalConst::APPROVED,
            ]);
            try{
                if( $this->basic_settings->email_notification == true){
                    $user->notify(new Approved($user));
                }
            }catch(Exception $e){}
        }catch(Exception $e) {
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }
        return back()->with(['success' => [__('User KYC successfully approved')]]);
    }

    public function kycReject(Request $request, $username) {
        $request->validate([
            'target'        => "required|exists:users,username",
            'reason'        => "required|string|max:500"
        ]);
        $user = User::where("username",$request->target)->first();
        if(!$user) return back()->with(['error' => [__("User doesn't exists")]]);
        if($user->kyc == null) return back()->with(['error' => [__('User KYC information not found')]]);

        try{
            $user->update([
                'kyc_verified'  => GlobalConst::REJECTED,
            ]);
            $user->kyc->update([
                'reject_reason' => $request->reason,
            ]);
        }catch(Exception $e) {
            $user->update([
                'kyc_verified'  => GlobalConst::PENDING,
            ]);
            $user->kyc->update([
                'reject_reason' => null,
            ]);
            try{
                if( $this->basic_settings->email_notification == true){
                    $user->notify(new Rejected($user,$request->reason));
                }
            }catch(Exception $e){}
            return back()->with(['error' => [__('Something went wrong! Please try again')]]);
        }

        return back()->with(['success' => [__('User KYC information is rejected')]]);
    }


    public function search(Request $request) {
        $validator = Validator::make($request->all(),[
            'text'  => 'required|string',
        ]);

        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }

        $validated = $validator->validate();
        $users = User::search($validated['text'])->limit(10)->get();
        return view('admin.components.search.user-search',compact(
            'users',
        ));
    }
    public function walletBalanceUpdate(Request $request,$username) {
        $validator = Validator::make($request->all(),[
            'type'      => "required|string|in:add,subtract",
            'wallet'    => "required|numeric|exists:user_wallets,id",
            'amount'    => "required|numeric",
            'remark'    => "required|string|max:200",
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','wallet-balance-update-modal');
        }

        $validated = $validator->validate();
        $user_wallet = UserWallet::whereHas('user',function($q) use ($username){
            $q->where('username',$username);
        })->find($validated['wallet']);
        if(!$user_wallet) return back()->with(['error' => [__('User wallet not found!')]]);

        DB::beginTransaction();
        try{

            $user_wallet_balance = 0;

            switch($validated['type']){
                case "add":
                    $user_wallet_balance = $user_wallet->balance + $validated['amount'];
                    $user_wallet->balance += $validated['amount'];
                    break;

                case "subtract":
                    if($user_wallet->balance >= $validated['amount']) {
                        $user_wallet_balance = $user_wallet->balance - $validated['amount'];
                        $user_wallet->balance -= $validated['amount'];
                    }else {
                        return back()->with(['error' => [__('User do not have sufficient balance')]]);
                    }
                    break;
            }

            $inserted_id = DB::table("transactions")->insertGetId([
                'admin_id'          => auth()->user()->id,
                'user_id'           => $user_wallet->user->id,
                'user_wallet_id'    => $user_wallet->id,
                'type'              => PaymentGatewayConst::TYPEADDSUBTRACTBALANCE,
                'attribute'         => PaymentGatewayConst::RECEIVED,
                'trx_id'            => generate_unique_string("transactions","trx_id",16),
                'request_amount'    => $validated['amount'],
                'payable'           => $validated['amount'],
                'available_balance' => $user_wallet_balance,
                'remark'            => $validated['remark'],
                'created_at'        => now(),
                'status'            => GlobalConst::SUCCESS,
            ]);


            DB::table('transaction_charges')->insert([
                'transaction_id'    => $inserted_id,
                'percent_charge'    => 0,
                'fixed_charge'      => 0,
                'total_charge'      => 0,
                'created_at'        => now(),
            ]);


            $client_ip = request()->ip() ?? false;
            $location = geoip()->getLocation($client_ip);
            $agent = new Agent();

            $mac = "";

            DB::table("transaction_devices")->insert([
                'transaction_id'=> $inserted_id,
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

            $user_wallet->save();

            $notification_content = [
                'title'         => "Update Balance",
                'message'       => "Your Wallet (".$user_wallet->currency->code.") balance has been update",
                'time'          => Carbon::now()->diffForHumans(),
                'image'         => files_asset_path('profile-default'),
            ];

            UserNotification::create([
                'type'      => NotificationConst::BALANCE_UPDATE,
                'user_id'  => $user_wallet->user->id,
                'message'   => $notification_content,
            ]);

            DB::commit();
        }catch(Exception $e) {
            DB::rollBack();
            return back()->with(['error' => [__('Transaction failed!')]]);
        }
        return back()->with(['success' => [__('Transaction success')]]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use App\Models\Transaction;
use App\Models\VirtualCardApi;
use App\Models\ApiApp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VirtualCardController extends Controller
{
    public function cardApi()
    {
        $page_title = __("Setup Virtual Card Api");
        //$this->createApiAppElemet();
        $admin =Admin::where('id',auth()->user()->id)->first();
        $api = VirtualCardApi::where('name',$admin->name_api)->first();
        $existApi=$api;
        if($api)
        {
            $existApi=false;
        }else{
            $app=$api = VirtualCardApi::first();
            $api->name=$admin->name_api;
            //dump($api);
            $api->card_details='';
        }
        //dd(URL::to('/'));
        $url=URL::to('/').'/'.$api->name.'/'.'webhook';
        $urlWebhook=$url;
        //$apiApp=ApiApp::all();
        //dump($api);
        //$adim =Admin::where('id',auth()->user()->id)->first();
        //dump($existApi);
        //dump(get_default_language_code());
       // dd(VirtualCardApi::where('name',$admin->name_api)->first());
        return view('admin.sections.virtual-card.api',compact(
            'page_title',
            'api',
            'existApi',
            'urlWebhook'
        ));
    }
    public function cardApiChange(Request $request){
        $validator = Validator::make($request->all(),[
            'api_method_app'=> 'required|in:flutterwave,sudo,stripe,strowallet,soleaspay,eversend,maplerad'
        ]);
        $admin =Admin::where('id',auth()->user()->id)->first();
        
        
        if($validator->fails()) {
            //dump($validator);
            return back()->withErrors($validator)->withInput();
        }
        $admin->name_api=$request->api_method_app;
        $apiMeth=ApiApp::where('status',true)->first();
        $apiMeth->status=false;
        $apiMeth->update();
        $apiMethode=ApiApp::where('name',$request->api_method_app)->first();
        if(!$apiMethode){
            $newapi=new ApiApp();
            $newapi->name=$request->api_method_app;
            $newapi->status=true;
            $newapi->save();

        }else{
            $apiMethode->status=true;
        $apiMethode->update();
        
        }
        $admin->save();
        
        return redirect()->back();
    }
    public function cardApiUpdate(Request $request){
        //dd($request);
        $validator = Validator::make($request->all(), [
            'api_method'                => 'required|in:flutterwave,sudo,stripe,strowallet,soleaspay,eversend,maplerad',
            'flutterwave_secret_key'    => 'required_if:api_method,flutterwave',
            'flutterwave_secret_hash'   => 'required_if:api_method,flutterwave',
            'flutterwave_url'           => 'required_if:api_method,flutterwave',
            'sudo_api_key'              => 'required_if:api_method,sudo',
            'sudo_vault_id'             => 'required_if:api_method,sudo',
            'sudo_url'                  => 'required_if:api_method,sudo',
            'sudo_mode'                 => 'required_if:api_method,sudo',
            'card_details'              => 'required|string',
            'stripe_public_key'         => 'required_if:api_method,stripe',
            'stripe_secret_key'             => 'required_if:api_method,stripe',
            'stripe_url'                => 'required_if:api_method,stripe',
            'stripe_mode'                 => 'required_if:api_method,stripe',
            'strowallet_public_key'     => 'required_if:api_method,strowallet',
            'strowallet_secret_key'     => 'required_if:api_method,strowallet',
            'strowallet_url'            => 'required_if:api_method,strowallet',
            'strowallet_mode'                 => 'required_if:api_method,strowallet',
            'soleaspay_public_key'     => 'required_if:api_method,soleaspay',
            'soleaspay_secret_key'     => 'required_if:api_method,soleaspay',
            'soleaspay_url'            => 'required_if:api_method,soleaspay',
            'soleaspay_mode'                 => 'required_if:api_method,soleaspay',
            'eversend_public_key'     => 'required_if:api_method,eversend',
            'eversend_secret_key'     => 'required_if:api_method,eversend',
            'eversend_url'            => 'required_if:api_method,eversend',
            'eversend_mode'                 => 'required_if:api_method,eversend',
            'maplerad_public_key'     => 'required_if:api_method,maplerad',
            'maplerad_secret_key'     => 'required_if:api_method,maplerad',
            'maplerad_url'            => 'required_if:api_method,maplerad',
            'maplerad_mode'                 => 'required_if:api_method,maplerad',
            'image'                     => "nullable|mimes:png,jpg,jpeg,webp,svg",
            'nb_trx_failled'=>'required|integer',
            'penality_price'=>'required|numeric|required_if:is_activate_penality,1',
            'is_created_card'=>'nullable|boolean',
            'is_active'=>'nullable|boolean',
            'is_rechargeable'=>'nullable|boolean',
            'is_withdraw'=>'nullable|boolean',
            'is_activate_penality'=>'nullable|boolean',
            'strowallet_email'=>'nullable|string'
            /*'card_limit' => [
                'required',
                'numeric',
                Rule::in([1, 2, 3]),
            ],*/
        ]);
        
        //dd($request);
        if($validator->fails()) {
            //dd($validator);
            return back()->withErrors($validator)->withInput();
        }
        //dd($request);
        $admin =Admin::where('id',auth()->user()->id)->first();
        $existApi=VirtualCardApi::where('name',$admin->name_api)->first();
        
        $request->merge(['name'=>$request->api_method]);
        $data = array_filter($request->except('_token','api_method','_method','card_details','image','card_limit'));
        //dump($currentApi->name);
        if($existApi){
            $api = VirtualCardApi::where('name',$admin->name_api)->first();
        }else{
            $api=new VirtualCardApi();
            $api->admin_id=auth()->user()->id;
        }
        
        //dump($api);
        $api->card_details = $request->card_details;
        $api->nb_trx_failled=$request->nb_trx_failled;
        $api->nb_trx_failled=$request->nb_trx_failled;
        if(isset($request['penality_price']))
        $api->substitute_name=$request->substitute_name;
        $api->card_limit = 10000;//$request->card_limit;
        $api->config = $data;
        $api->name=$data['name'];
        if(isset($data['is_active'])){
            $api->is_active=$data['is_active'];
        }else{
            $api->is_active=false;
        }
        
        if(isset($data['is_created_card'])){
            $api->is_created_card=$data['is_created_card'];
        }else{
            $api->is_created_card=false;
        }
        if(isset($data['is_rechargeable'])){
            $api->is_rechargeable=$data['is_rechargeable'];
        }else{
            $api->is_rechargeable=false;
        }
        if(isset($data['is_withdraw'])){
            $api->is_withdraw=$data['is_withdraw'];
        }else{
            $api->is_withdraw=false;
        }
        if(isset($data['is_activate_penality'])){
            $api->is_activate_penality=$data['is_activate_penality'];
        }else{
            $api->is_activate_penality=false;
        }
        
       //$this->createApiAppElemet();

        if ($request->hasFile("image")) {
            try {
                $image = get_files_from_fileholder($request, "image");
                $upload_file = upload_files_from_path_dynamic($image, "card-api");
                $api->image = $upload_file;
            } catch (Exception $e) {
                return back()->with(['error' => [__('Ops! Failed To Upload Image.')]]);
            }
        }
        $api->save();

        return back()->with(['success' => [__('Card API Has Been Updated.')]]);
    }
    public function createApiAppElemet(){
        ApiApp::create([
            'name'=>'stripe',
            'status'=>false,
        ]);
        ApiApp::create([
            'name'=>'strowallet',
            'status'=>true,
        ]);
        ApiApp::create([
            'name'=>'sudo',
            'status'=>false,
        ]);
        ApiApp::create([
            'name'=>'flutterwave',
            'status'=>false,
        ]);
    }

    public function transactionLogs()
    {
        $page_title = __("Virtual Card Logs");
        $transactions = Transaction::with(
          'user:id,firstname,lastname,email,username,full_mobile',
            'currency:id,name',
        )->where('type', 'VIRTUAL-CARD')->latest()->paginate(20);

        return view('admin.sections.virtual-card.logs', compact(
            'page_title',
            'transactions'
        ));
    }
}

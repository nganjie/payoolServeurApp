<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use App\Models\Transaction;
use App\Models\VirtualCardApi;
use App\Models\ApiApp;
use Exception;
use Illuminate\Http\Request;
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
        //$apiApp=ApiApp::all();
        //dump($api);
        //$adim =Admin::where('id',auth()->user()->id)->first();
        //dump($existApi);
        //dump(get_default_language_code());
        return view('admin.sections.virtual-card.api',compact(
            'page_title',
            'api',
            'existApi'
        ));
    }
    public function cardApiChange(Request $request){
        $validator = Validator::make($request->all(),[
            'api_method_app'=> 'required|in:flutterwave,sudo,stripe,strowallet,soleaspay,eversend'
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
        //dump($request);
        $validator = Validator::make($request->all(), [
            'api_method'                => 'required|in:flutterwave,sudo,stripe,strowallet,soleaspay,eversend',
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
            'strowallet_public_key'     => 'required_if:api_method,strowallet',
            'strowallet_secret_key'     => 'required_if:api_method,strowallet',
            'strowallet_url'            => 'required_if:api_method,strowallet',
            'soleaspay_public_key'     => 'required_if:api_method,soleaspay',
            'soleaspay_secret_key'     => 'required_if:api_method,soleaspay',
            'soleaspay_url'            => 'required_if:api_method,soleaspay',
            'eversend_public_key'     => 'required_if:api_method,eversend',
            'eversend_secret_key'     => 'required_if:api_method,eversend',
            'eversend_url'            => 'required_if:api_method,eversend',
            'image'                     => "nullable|mimes:png,jpg,jpeg,webp,svg",
            /*'card_limit' => [
                'required',
                'numeric',
                Rule::in([1, 2, 3]),
            ],*/
        ]);
        
        if($validator->fails()) {
            //dump($validator);
            return back()->withErrors($validator)->withInput();
        }
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
        $api->card_limit = 10000;//$request->card_limit;
        $api->config = $data;
        $api->name=$data['name'];
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

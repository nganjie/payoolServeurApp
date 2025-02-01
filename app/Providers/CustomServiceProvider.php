<?php

namespace App\Providers;

use Exception;
use App\Models\User;
use App\Models\Admin\Currency;
use App\Models\Admin\Language;
use App\Models\VirtualCardApi;
use App\Models\Admin\Extension;
use App\Models\UserSupportTicket;
use App\Models\Admin\BasicSettings;
use App\Models\Admin\ModuleSetting;
use App\Models\Admin\SystemMaintenance;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Models\Admin\TransactionSetting;
use App\Models\ApiApp;
use App\Providers\Admin\CurrencyProvider;
use App\Providers\Admin\BasicSettingsProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class CustomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //dd("un monde de fou");
        //dd(Session::get('user_id'));
        
        $this->startingPoint();
        view()->composer('*', function ($view) 
    {
       // $cart = Cart::where('user_id', Auth::user()->id);
       if(Auth::check())
       {
        $view_card=[];
        $view_card['card_details']= VirtualCardApi::where('name',Auth::user()->name_api)->first();
        $view_card['card_limit'] = VirtualCardApi::where('name',Auth::user()->name_api)->first()->card_limit;
        $view_card['card_api'] = VirtualCardApi::where('name',Auth::user()->name_api)->first();
        $view_card['cardCharge'] = TransactionSetting::where('slug','virtual_card_'.Auth::user()->name_api)->where('status',1)->first();
        $view_card['cardReloadCharge']             = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();
        //...with this variable
        $view->with($view_card);   
       }
         
    });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        try{
            //where('name',$name_api)->
            $user_id=Session::get('user_id');
            $name_api='';
            //dd(Request::ip());
            if($user_id){
                $user= User::where('id',$user_id)->first();
                $name_api=$user->name_api;
            }else{
                $name_api=Auth::check()?auth()->user()->name_api:ApiApp::where('status',true)->first()->name;
            }
            
            //dd('virtual_card'.$name_api);
            //dd(TransactionSetting::where('slug','virtual_card_'.$name_api)->where('status',1)->first());
            $view_share = [];
           $view_share['basic_settings']               = BasicSettings::first();
            //$view_share['card_details']                 = VirtualCardApi::where('name',$name_api)->first();
            $view_share['default_currency']             = Currency::default();
            $view_share['__languages']                  = Language::get();
            $view_share['all_user_count']               = User::count();
            $view_share['email_verified_user_count']    = User::where('email_verified', 1)->count();
            $view_share['kyc_verified_user_count']      = User::where('kyc_verified', 1)->count();
            $view_share['default_currency']             = Currency::default();
            $view_share['__extensions']                 = Extension::get();
            $view_share['pending_ticket_count']         = UserSupportTicket::pending()->get()->count();
            //$view_share['cardCharge']                   = TransactionSetting::where('slug','virtual_card_'.$name_api)->where('status',1)->first();
            //$view_share['cardReloadCharge']             = TransactionSetting::where('slug','reload_card_'.auth()->user()->name_api)->where('status',1)->first();
            //$view_share['card_limit']                   = VirtualCardApi::where('name',$name_api)->first()->card_limit;
            //$view_share['card_api']                     = VirtualCardApi::where('name',$name_api)->first();
            $view_share['module']                       = ModuleSetting::get();
            $view_share['system_maintenance']           = SystemMaintenance::first();
            //$view_share['basic_settings']               = BasicSettings::first();

            view()->share($view_share);
            //dump($view_share);

            $this->app->bind(BasicSettingsProvider::class, function () use ($view_share) {
                return new BasicSettingsProvider($view_share['basic_settings']);
            });
            $this->app->bind(CurrencyProvider::class, function () use ($view_share) {
                return new CurrencyProvider($view_share['default_currency']);
            });
        }catch(Exception $e) {
            //
        }
    }

    public function startingPoint() {
        if(env('PURCHASE_CODE','') == null) {
            Config::set('starting-point.status',true);
            Config::set('starting-point.point','/project/install/welcome');
        }
    }
}

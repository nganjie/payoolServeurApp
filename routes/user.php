<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Providers\Admin\BasicSettingsProvider;
use Pusher\PushNotifications\PushNotifications;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\AddMoneyController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\TransactionController;
use App\Http\Controllers\User\AuthorizationController;
use App\Http\Controllers\User\EversendVirtualCardController;
use App\Http\Controllers\User\GiftCardController;
use App\Http\Controllers\User\MapleradVirtualCardController;
use App\Http\Controllers\User\SecurityController;
use App\Http\Controllers\User\StripeVirtualController;
use App\Http\Controllers\User\StrowalletVirtualController;
use App\Http\Controllers\User\SudoVirtualCardController;
use App\Http\Controllers\User\SupportTicketController;
use App\Http\Controllers\User\TransferMoneyController;
use App\Http\Controllers\User\VirtualcardController;
use App\Http\Controllers\User\WithdrawController;
use App\Http\Controllers\User\SoleaspayVirtualCardController;
use App\Http\Controllers\UserNoticeController;

Route::prefix("user")->name("user.")->group(function(){
    Route::controller(DashboardController::class)->group(function(){
        Route::get('dashboard','index')->name('dashboard');
        Route::post('logout','logout')->name('logout');
        Route::post('change/api','changeApi')->name('change.api');
        Route::delete('delete/account','deleteAccount')->name('delete.account')->middleware('app.mode');
    });
    Route::controller(ProfileController::class)->prefix("profile")->name("profile.")->group(function(){
        Route::get('/','index')->name('index');
        Route::put('update','update')->name('update')->middleware('app.mode');
        Route::get('change/password','changePassword')->name('change.password')->middleware('app.mode');
        Route::put('password/update','passwordUpdate')->name('password.update')->middleware('app.mode');
    });
    Route::controller(UserNoticeController::class)->prefix("notice")->middleware('kyc.verification.guard')->name("notice.")->group(function(){
        Route::get('/','index')->name('index');
        Route::get('create','showCreate')->name('create');
        Route::get('update/{user_notice}','showUpdate')->name('update-notice');
        Route::post('create','store')->name('store');
        Route::put('update','update')->name('update')->middleware('app.mode');
        Route::delete('delete','delete')->name('delete');
    });
    //Transfer  Money
    Route::middleware('module:transfer-money')->group(function(){
        Route::controller(TransferMoneyController::class)->prefix('transfer-money')->name('transfer.money.')->middleware('kyc.verification.guard')->group(function(){
            Route::get('/','index')->name('index');
            Route::post('confirmed','confirmed')->name('confirmed');
            Route::post('user/exist','checkUser')->name('check.exist');
        });
    });
    //add money
    Route::middleware('module:add-money')->group(function(){
        Route::controller(AddMoneyController::class)->prefix("add-money")->name("add.money.")->group(function(){
            Route::get('/','index')->name("index");
            Route::post('submit','submit')->name('submit');
            //paypal
            Route::get('success/response/paypal/{gateway}','success')->name('payment.success');
            Route::get("cancel/response/paypal/{gateway}",'cancel')->name('payment.cancel');
            Route::post("callback/response/{gateway}",'callback')->name('payment.callback')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']);
            //stripe
            Route::get('stripe/payment/success/{trx}','stripePaymentSuccess')->name('stripe.payment.success');
            //manual gateway
            Route::get('manual/payment','manualPayment')->name('manual.payment');
            Route::post('manual/payment/confirmed','manualPaymentConfirmed')->name('manual.payment.confirmed');
            //flutterwave
            Route::get('flutterwave/callback', 'flutterwaveCallback')->name('flutterwave.callback');
            //soleaspay
            Route::get('soleaspay/success', 'soleaspaySuccess')->name('soleaspay.success');
            Route::get('soleaspay/fails', 'soleaspayFails')->name('soleaspay.fails');
            //PAIEMENTPRO
            Route::get('paiementpro/success', 'paiementproSuccess')->name('paiementpro.success');
            Route::get('paiementpro/fails', 'paiementproFails')->name('paiementpro.fails');
            //QRPay
            Route::get('qrpay/success', 'qrPaySuccess')->name('qrpay.success');
            Route::get('qrpay/cancel/{trx}', 'qrPayCancel')->name('qrpay.cancel');
            //coingate
            Route::match(['get','post'],'coingate/success/response/{gateway}','coinGateSuccess')->name('coingate.payment.success');
            Route::match(['get','post'],"coingate/cancel/response/{gateway}",'coinGateCancel')->name('coingate.payment.cancel');
            //Tatum
            Route::prefix('payment')->name('payment.')->group(function() {
                Route::get('crypto/address/{trx_id}','cryptoPaymentAddress')->name('crypto.address');
                Route::post('crypto/confirm/{trx_id}','cryptoPaymentConfirm')->name('crypto.confirm');
            });
            // Perfect Money
            Route::get('redirect/form/{gateway}', 'redirectUsingHTMLForm')->name('payment.redirect.form')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);
            Route::get('perfect.success/response/{gateway}','perfectSuccess')->name('perfect.payment.success');
            Route::get("perfect.cancel/response/{gateway}",'perfectCancel')->name('perfect.payment.cancel');

            //redirect with Btn Pay
            Route::get('redirect/btn/checkout/{gateway}', 'redirectBtnPay')->name('payment.btn.pay')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);

            Route::get('success/response/{gateway}','successGlobal')->name('payment.global.success');
            Route::get("cancel/response/{gateway}",'cancelGlobal')->name('payment.global.cancel');

            // POST Route For Unauthenticated Request
            Route::post('success/response/{gateway}', 'postSuccess')->name('payment.global.success')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);
            Route::post('cancel/response/{gateway}', 'postCancel')->name('payment.global.cancel')->withoutMiddleware(['auth','verification.guard','user.google.two.factor']);
            //pagadito
            Route::get('success/{gateway}','successPagadito')->name('payment.success.pagadito')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor','auth:api','api.user.verification.guard']);

            //paystack
            Route::get('paystack/callback/response','paystackCallback')->name('payment.callback.response');

        });
    });
    //withdraw money
    Route::middleware('module:withdraw-money')->group(function(){
        Route::controller(WithdrawController::class)->prefix('withdraw')->name('withdraw.')->middleware('kyc.verification.guard')->group(function(){
            Route::get('/','index')->name('index');
            Route::post('insert','paymentInsert')->name('submit');
            Route::get('preview','preview')->name('preview');
            Route::post('confirm','confirmMoneyOut')->name('confirm');

        });
    });
    //virtual card stripe
    Route::middleware('module:virtual-card')->group(function(){
        Route::middleware('virtual_card_method:stripe')->group(function(){
            Route::controller(StripeVirtualController::class)->prefix('stripe-virtual-card')->middleware('kyc.verification.guard')->name('stripe.virtual.card.')->group(function(){
                Route::get('/','index')->name('index');
                Route::post('create','cardBuy')->name('create');
                Route::get('details/{card_id}','cardDetails')->name('details');
                Route::get('transaction/{card_id}','cardTransaction')->name('transaction');
                Route::put('change/status','cardBlockUnBlock')->name('change.status');
                Route::post('get/sensitive/data','getSensitiveData')->name('sensitive.data');
                Route::post('make/default/remove/default','makeDefaultOrRemove')->name('make.default.or.remove');
            });
        });
        //virtual card sudo
        Route::middleware('virtual_card_method:sudo')->group(function(){
            Route::controller(SudoVirtualCardController::class)->prefix('sudo-virtual-card')->middleware('kyc.verification.guard')->name('sudo.virtual.card.')->group(function(){
                Route::get('/','index')->name('index');
                Route::post('create','cardBuy')->name('create');
                Route::post('delete','deleteCard')->name('delete');
                Route::post('make/default/remove/default','makeDefaultOrRemove')->name('make.default.or.remove');
                Route::get('details/{card_id}','cardDetails')->name('details');
                Route::get('transaction/{card_id}','cardTransaction')->name('transaction');
                Route::post('fund','cardFundConfirm')->name('fund.confirm');
                Route::put('change/status','cardBlockUnBlock')->name('change.status');
            });
        });
        //virtual card flutterwave
        Route::middleware('virtual_card_method:flutterwave')->group(function(){
            Route::controller(VirtualcardController::class)->prefix('my-card')->middleware('kyc.verification.guard')->name('virtual.card.')->group(function(){
                Route::get('/','index')->name('index');
                Route::post('create','cardBuy')->name('create');
                Route::post('fund','cardFundConfirm')->name('fund');
                Route::get('details/{card_id}','cardDetails')->name('details');
                Route::get('transaction/{card_id}','cardTransaction')->name('transaction');
                Route::put('change/status','cardBlockUnBlock')->name('change.status');
                Route::post('make/default/remove/default','makeDefaultOrRemove')->name('make.default.or.remove');
                Route::post('flutter-wave-card-callback','cardCallBack')->name('flutterWave.callBack');
            });
        });
        //virtual card soleaspay
    Route::middleware('virtual_card_method:soleaspay')->group(function(){
        Route::controller(SoleaspayVirtualCardController::class)->prefix('soleaspay-virtual-card')->middleware('kyc.verification.guard')->name('soleaspay.virtual.card.')->group(function(){
            Route::get('/','index')->name('index');
            Route::post('create','cardBuy')->name('create');
            Route::post('delete','deleteCard')->name('delete');
            Route::post('pay/penality','payPenality')->name('pay.penality');
            Route::post('fund','cardFundConfirm')->name('fund');
            Route::post('withdraw','cardWithdraw')->name('withdraw');
            Route::get('details/{card_id}','cardDetails')->name('details');
            Route::get('transaction/{card_id}','cardTransaction')->name('transaction');
            Route::put('change/status','cardBlockUnBlock')->name('change.status');
            Route::post('make/default/remove/default','makeDefaultOrRemove')->name('make.default.or.remove');
            Route::post('soleaspay-card-callback','cardCallBack')->name('callBack');
        });
    });
    Route::middleware('virtual_card_method:eversend')->group(function(){
        Route::controller(EversendVirtualCardController::class)->prefix('eversend-virtual-card')->middleware('kyc.verification.guard')->name('eversend.virtual.card.')->group(function(){
            Route::get('/','index')->name('index');
            Route::post('create','cardBuy')->name('create');
            Route::post('delete','deleteCard')->name('delete');
            Route::post('pay/penality','payPenality')->name('pay.penality');
            Route::post('fund','cardFundConfirm')->name('fund');
            Route::post('withdraw','cardWithdraw')->name('withdraw');
            Route::get('details/{card_id}','cardDetails')->name('details');
            Route::get('transaction/{card_id}','cardTransaction')->name('transaction');
            Route::put('change/status','cardBlockUnBlock')->name('change.status');
            Route::post('make/default/remove/default','makeDefaultOrRemove')->name('make.default.or.remove');
            Route::post('eversend-card-callback','cardCallBack')->name('callBack');
        });
    });
    Route::middleware('virtual_card_method:maplerad')->group(function(){
        Route::controller(MapleradVirtualCardController::class)->prefix('maplerad-virtual-card')->middleware('kyc.verification.guard')->name('maplerad.virtual.card.')->group(function(){
            Route::get('/','index')->name('index');
            Route::post('create','cardBuy')->name('create');
            Route::post('delete','deleteCard')->name('delete');
            Route::post('pay/penality','payPenality')->name('pay.penality');
            Route::post('fund','cardFundConfirm')->name('fund');
            Route::post('withdraw','cardWithdraw')->name('withdraw');
            Route::get('details/{card_id}','cardDetails')->name('details');
            Route::get('transaction/{card_id}','cardTransaction')->name('transaction');
            Route::put('change/status','cardBlockUnBlock')->name('change.status');
            Route::post('make/default/remove/default','makeDefaultOrRemove')->name('make.default.or.remove');
            Route::post('maplerad-card-callback','cardCallBack')->name('callBack');
        });
    });
        //virtual card strowallet
        Route::middleware('virtual_card_method:strowallet')->group(function(){
            Route::controller(StrowalletVirtualController::class)->prefix('strowallet-virtual-card')->middleware('kyc.verification.guard')->name('strowallet.virtual.card.')->group(function(){
                Route::get('/','index')->name('index');
                Route::get('create','createPage')->name('create');
                Route::post('delete','deleteCard')->name('delete');
                Route::post('pay/penality','payPenality')->name('pay.penality');
                Route::post('create/customer','createCustomer')->name('create.customer');
                Route::post('withdraw','cardWithdraw')->name('withdraw');
                Route::get('edit/customer','editCustomer')->name('edit.customer');
                Route::put('update/customer','updateCustomer')->name('update.customer');
                Route::post('create','cardBuy')->name('create');
                Route::post('fund','cardFundConfirm')->name('fund');
                Route::get('details/{card_id}','cardDetails')->name('details');
                Route::get('transaction/{card_id}','cardTransaction')->name('transaction');
                Route::put('change/status','cardBlockUnBlock')->name('change.status');
                Route::post('make/default/remove/default','makeDefaultOrRemove')->name('make.default.or.remove');
            });
        });
    });
    Route::middleware('module:gift-cards')->group(function(){
        Route::controller(GiftCardController::class)->prefix('gift-card')->name('gift.card.')->group(function(){
            Route::get('/', 'index')->name('index');
            Route::get('/list', 'giftCards')->name('list');
            Route::get('details/{product_id}', 'details')->name('details');
            Route::post('order', 'giftCardOrder')->name('order')->middleware('kyc.verification.guard');
            Route::get('search', 'giftSearch')->name('search');
            Route::post('webhook', 'webhookInfo')->name('webhook')->withoutMiddleware(['web','auth','verification.guard','user.google.two.factor']);
        });
    });
    //transactions
    Route::controller(TransactionController::class)->prefix("transactions")->name("transactions.")->group(function(){
        Route::get('/{slug?}','index')->name('index')->whereIn('slug',['add-money','money-out','virtual-card','transfer-money','withdraw-money']);
        Route::post('search','search')->name('search');
    });
    //supports
    Route::controller(SupportTicketController::class)->prefix("support/ticket")->name("support.ticket.")->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('conversation/{encrypt_id}','conversation')->name('conversation');
        Route::post('message/send','messageSend')->name('messaage.send');
    });
    //kyc
    Route::controller(AuthorizationController::class)->prefix("authorize")->name('authorize.')->group(function(){
        Route::get('kyc','showKycFrom')->name('kyc');
        Route::get('kyc/again','showKycFromAgain')->name('kyc.again');
        Route::post('kyc/submit','kycSubmit')->name('kyc.submit');
        Route::post('kyc/again/submit','kycSubmitAgain')->name('kyc.again.submit');
    });
    //google-2fa
    Route::controller(SecurityController::class)->prefix("security")->name('security.')->group(function(){
        Route::get('google/2fa','google2FA')->name('google.2fa');
        Route::post('google/2fa/status/update','google2FAStatusUpdate')->name('google.2fa.status.update')->middleware('app.mode');
    });

});
Route::get('user/pusher/beams-auth', function (Request $request) {
    if(Auth::check() == false) {
        return response(['Inconsistent request'], 401);
    }
    $userID = Auth::user()->id;

    $basic_settings = BasicSettingsProvider::get();
    if(!$basic_settings) {
        return response('Basic setting not found!', 404);
    }

    $notification_config = $basic_settings->push_notification_config;

    if(!$notification_config) {
        return response('Notification configuration not found!', 404);
    }

    $instance_id    = $notification_config->instance_id ?? null;
    $primary_key    = $notification_config->primary_key ?? null;
    if($instance_id == null || $primary_key == null) {
        return response('Sorry! You have to configure first to send push notification.', 404);
    }
    $beamsClient = new PushNotifications(
        array(
            "instanceId" => $notification_config->instance_id,
            "secretKey" => $notification_config->primary_key,
        )
    );
    $publisherUserId = make_user_id_for_pusher("user", $userID);
    try{
        $beamsToken = $beamsClient->generateToken($publisherUserId);
    }catch(Exception $e) {
        return response(['Server Error. Failed to generate beams token.'], 500);
    }

    return response()->json($beamsToken);
})->name('user.pusher.beams.auth');


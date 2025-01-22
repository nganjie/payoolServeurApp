<?php

namespace App\Listeners\Strowallet;

use App\Events\Strowallet\CardPayementFailedEvent;
use App\Models\StrowalletVirtualCard;
use App\Models\User;
use App\Models\VirtualCardApi;
use App\Notifications\Webhook\Strowallet\CardBlockMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Webhook\Strowallet\CardPayementFailedMail;
use Exception;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CardPayemenFailedtListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Strowallet\CardPayementFailedEvent  $event
     * @return void
     */
    public function handle(CardPayementFailedEvent $event)
    {
        $data= $event->data;
        Log::build([
         'driver' => 'single',
         'path' => storage_path('logs/strowallet.log'),
       ])->info("payement failled");
       //print_r($data);
       $card_id=$data['cardId'];
         $card = StrowalletVirtualCard::where('card_id',$card_id)->first();
         $api =VirtualCardApi::where('name','strowallet')->first();
         if($card){
             //$activated=$data['activated'];
             $user=User::find($card->user_id);
             $nbtrx=$card->nb_trx_failed+1;
             $card->nb_trx_failed+=1;
             $card->save();
             $not=[];
             $not['nbtrx_max']=$api->nb_trx_failled;
             $not['nbtrx']=$nbtrx;
             $not['amande']=$api->penality_price;
             $not['card']=$card;
             $not['data']=$data;
             Log::build([
              'driver' => 'single',
              'path' => storage_path('logs/strowallet.log'),
            ])->info(json_encode($api));

             if($user){
                if($nbtrx<$api->nb_trx_failled){
                 
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/strowallet.log'),
                  ])->info("first method");
                  try{
                    $user->notify(new CardPayementFailedMail($user,$not));
                  }catch(Exception $e){
                    Log::build([
                      'driver' => 'single',
                      'path' => storage_path('logs/strowallet.log'),
                    ])->info($e);
                  }
                 
                }else if($nbtrx>=$api->nb_trx_failled){
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/strowallet.log'),
                  ])->info("second method");
                    $card->is_penalize=true;
                    $card->save();
                    
                    try{
                      $user->notify(new CardBlockMail($user,$not));
                    }catch(Exception $e){
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/strowallet.log'),
                      ])->info($e);
                    } 
                    try{
                     // $public_key=$api->config->Strowallet_public_key;
                   // $secret_key=$api->config->Strowallet_secret_key;
                    $client = new \GuzzleHttp\Client();
            $public_key     = $api->config->strowallet_public_key;
            $base_url       = $api->config->strowallet_url;

            $response = $client->request('POST', $base_url.'action/status/?action=freeze&card_id='.$card->card_id.'&public_key='.$public_key, [
            'headers' => [
                'accept' => 'application/json',
            ],
            ]);

            $result = $response->getBody();
            $datat  = json_decode($result, true);

            if( isset($datat['status']) ){
                $card->is_active = 0;
                $card->save();
                $success = ['success' => [__('Card block successfully!')]];
                            Log::build([
                                'driver' => 'single',
                                'path' => storage_path('logs/strowallet.log'),
                              ])->info(json_encode($success));
                //return Response::success($success,null,200);
            }else{
                $error = ['error' =>  [$datat['message']]];
                Log::build([
                  'driver' => 'single',
                  'path' => storage_path('logs/strowallet.log'),
                ])->info(json_encode(['api'=>'strowallet','erro_token_block_card'=>$card->card_id,$error]));
                //return Response::error($error,null,400);
            }
                    }catch(Exception $e){
                      
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/strowallet.log'),
                      ])->info($e);
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/strowallet.log'),
                      ])->info($response);
                    }
                    
                    
                   
                }
                 Log::build([
                     'driver' => 'single',
                     'path' => storage_path('logs/strowallet.log'),
                   ])->info($data);
                 
             }
         }
    }
}

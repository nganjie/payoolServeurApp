<?php

namespace App\Listeners\Maplerad;

use App\Events\Maplerad\CardPayementFailedEvent;
use App\Models\MapleradVirtualCard;
use App\Models\User;
use App\Models\VirtualCardApi;
use App\Notifications\Webhook\Maplerad\CardBlockMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Webhook\Maplerad\CardPayementFailedMail;
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
     * @param  \App\Events\Maplerad\CardPayementFailedEvent  $event
     * @return void
     */
    public function handle(CardPayementFailedEvent $event)
    {
        $data= $event->data;
        Log::build([
         'driver' => 'single',
         'path' => storage_path('logs/maplerad.log'),
       ])->info("payement failled");
       //print_r($data);
       $card_id=$data['card_id'];
         $card = MapleradVirtualCard::where('card_id',$card_id)->first();
         $api =VirtualCardApi::where('name','maplerad')->first();
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
              'path' => storage_path('logs/maplerad.log'),
            ])->info(json_encode($api));

             if($user){
                if($nbtrx<$api->nb_trx_failled){
                 
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/maplerad.log'),
                  ])->info("first method");
                  try{
                    $user->notify(new CardPayementFailedMail($user,$not));
                  }catch(Exception $e){
                    Log::build([
                      'driver' => 'single',
                      'path' => storage_path('logs/maplerad.log'),
                    ])->info($e);
                  }
                 
                }else if($nbtrx>=$api->nb_trx_failled){
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/maplerad.log'),
                  ])->info("second method");
                    $card->is_penalize=true;
                    $card->save();
                    
                    try{
                      $user->notify(new CardBlockMail($user,$not));
                    }catch(Exception $e){
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/maplerad.log'),
                      ])->info($e);
                    } 
                    try{
                    $secret_key=$api->config->Maplerad_secret_key;
            $public_key     = $api->config->Maplerad_public_key;
            $base_url       = $api->config->Maplerad_url;

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $api->config->maplerad_url.'issuing/'.$card->card_id."/freeze",
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
            
                    if (isset($result)&&isset($result['status'])) {
                        if ($result['status'] == true) {
                            $card->status = 'DISABLED';
                            $card->save();
                            $success = ['status' => [__('Card block successfully!')]];
                            Log::build([
                                'driver' => 'single',
                                'path' => storage_path('logs/maplerad.log'),
                              ])->info(json_encode($success));
                        }  else {
                          $card->status = 'DISABLED';
                            $card->save();
                            $error = ['error' => [$result->message]];
                            Log::build([
                                'driver' => 'single',
                                'path' => storage_path('logs/maplerad.log'),
                              ])->info(json_encode($error));
                        }
                    }
                    }catch(Exception $e){
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/maplerad.log'),
                      ])->info($result);
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/maplerad.log'),
                      ])->info($e);
                    }
                    
                    
                   
                }
                 Log::build([
                     'driver' => 'single',
                     'path' => storage_path('logs/maplerad.log'),
                   ])->info($data);
                 
             }
         }
    }
}

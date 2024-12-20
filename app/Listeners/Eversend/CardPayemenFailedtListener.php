<?php

namespace App\Listeners\Eversend;

use App\Events\Eversend\CardPayementFailedEvent;
use App\Models\EversendVirtualCard;
use App\Models\User;
use App\Models\VirtualCardApi;
use App\Notifications\Webhook\CardBlockMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Webhook\CardPayementFailedMail;
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
     * @param  \App\Events\Eversend\CardPayementFailedEvent  $event
     * @return void
     */
    public function handle(CardPayementFailedEvent $event)
    {
        $data= $event->data;
        Log::build([
         'driver' => 'single',
         'path' => storage_path('logs/eversend.log'),
       ])->info("payement failled");
       //print_r($data);
       $card_id=$data['cardId'];
         $card = EversendVirtualCard::where('card_id',$card_id)->first();
         $api =VirtualCardApi::where('name','eversend')->first();
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
              'path' => storage_path('logs/eversend.log'),
            ])->info(json_encode($api));

             if($user){
                if($nbtrx<$api->nb_trx_failled){
                 
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/eversend.log'),
                  ])->info("first method");
                  try{
                    $user->notify(new CardPayementFailedMail($user,$not));
                  }catch(Exception $e){
                    Log::build([
                      'driver' => 'single',
                      'path' => storage_path('logs/eversend.log'),
                    ])->info($e);
                  }
                 
                }else if($nbtrx>=$api->nb_trx_failled){
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/eversend.log'),
                  ])->info("second method");
                    $card->is_penalize=true;
                    $card->save();
                    
                    try{
                      $user->notify(new CardBlockMail($user,$not));
                    }catch(Exception $e){
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/eversend.log'),
                      ])->info($e);
                    } 
                    try{
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
                    if(!array_key_exists('token', $response)){
                        Log::build([
                            'driver' => 'single',
                            'path' => storage_path('logs/eversend.log'),
                          ])->info(json_encode(['api'=>'eversend','erro_token_block_card'=>$card->card_id]));
                        return;
                    }
                    $token = $response['token'];
                    curl_close($curl);
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $api->config->eversend_url."freeze",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>json_encode([
                           "cardId"=>$card->card_id
                          ]),
                          CURLOPT_HTTPHEADER =>  [
                            "accept: application/json",
                            "authorization: Bearer $token",
                            "content-type: application/json"
                          ],
                    ));
            
                    $result = json_decode(curl_exec($curl), true);
                    curl_close($curl);
                    if (isset($result)&&isset($result['success'])) {
                        if ($result['success'] == true) {
                            $card->status = 'frozen';
                            $card->save();
                            $success = ['success' => [__('Card block successfully!')]];
                            Log::build([
                                'driver' => 'single',
                                'path' => storage_path('logs/eversend.log'),
                              ])->info(json_encode($success));
                        }  else {
                          $card->status = 'frozen';
                            $card->save();
                            $error = ['error' => [$result->message]];
                            Log::build([
                                'driver' => 'single',
                                'path' => storage_path('logs/eversend.log'),
                              ])->info(json_encode($error));
                        }
                    }
                    }catch(Exception $e){
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/eversend.log'),
                      ])->info($result);
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/eversend.log'),
                      ])->info($e);
                    }
                    
                    
                   
                }
                 Log::build([
                     'driver' => 'single',
                     'path' => storage_path('logs/eversend.log'),
                   ])->info($data);
                 
             }
         }
    }
}

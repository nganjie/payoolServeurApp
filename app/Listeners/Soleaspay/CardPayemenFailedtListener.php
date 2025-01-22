<?php

namespace App\Listeners\Soleaspay;

use App\Events\Soleaspay\CardPayementFailedEvent;
use App\Models\SoleaspayVirtualCard;
use App\Models\User;
use App\Models\VirtualCardApi;
use App\Notifications\Webhook\Soleaspay\CardBlockMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Webhook\Soleaspay\CardPayementFailedMail;
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
     * @param  \App\Events\Soleaspay\CardPayementFailedEvent  $event
     * @return void
     */
    public function handle(CardPayementFailedEvent $event)
    {
        $data= $event->data;
        session()->put('local', 'fr');
        Log::build([
         'driver' => 'single',
         'path' => storage_path('logs/soleaspay.log'),
       ])->info("payement failled");
       //print_r($data);
       $card_id=$data['data']['reference'];
         $card = SoleaspayVirtualCard::where('account_id',$card_id)->first();
         $api =VirtualCardApi::where('name','soleaspay')->first();
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

             if($user){
                if($nbtrx<$api->nb_trx_failled){
                 
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/soleaspay.log'),
                  ])->info("first method");
                  try{
                    $user->notify(new CardPayementFailedMail($user,$not));
                  }catch(Exception $e){
                    Log::build([
                      'driver' => 'single',
                      'path' => storage_path('logs/soleaspay.log'),
                    ])->info($e);
                  }
                 
                }else if($nbtrx>=$api->nb_trx_failled){
                  Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/soleaspay.log'),
                  ])->info("second method");
                    $card->is_penalize=true;
                    $card->save();
                    
                    try{
                      $user->notify(new CardBlockMail($user,$not));
                    }catch(Exception $e){
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/soleaspay.log'),
                      ])->info($e);
                    } 
                    try{
                    $secret_key=$api->config->soleaspay_secret_key;
            $public_key     = $api->config->soleaspay_public_key;
            $base_url       = $api->config->soleaspay_url;
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
                "public_apikey" : "'. $public_key .'",
                "private_secretkey" : "'.$secret_key.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            )
            ));
    
            $response = json_decode(curl_exec($curl), true);
            
            if(!array_key_exists('access_token', $response)){
              Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/soleaspay.log'),
              ])->info($response);
            }
            $token = $response['access_token'];
            curl_close($curl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api->config->soleaspay_url."transaction/".$card->card_id."?action=disabled",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
              "Content-Type: application/json",
              "Authorization: Bearer ".$token,
            )
        ));
        $result = json_decode(curl_exec($curl), true);
        curl_close($curl);

        if (isset($result)) {
          if ($result['success'] == true) {
              $card->is_active = 0;
              $card->save();        
          }  else {
              $error = ['error' => [$result->message]];
              Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/soleaspay.log'),
              ])->info(json_encode($error));
              
          }
      }

            
                    }catch(Exception $e){
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/soleaspay.log'),
                      ])->info($result);
                      Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/soleaspay.log'),
                      ])->info($e);
                    }
                    
                    
                   
                }
                 Log::build([
                     'driver' => 'single',
                     'path' => storage_path('logs/soleaspay.log'),
                   ])->info($data);
                 
             }
         }
    }
}

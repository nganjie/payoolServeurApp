<?php

namespace App\Handler;

use App\Events\Soleaspay\CardAdjustedEvent;
use App\Events\Soleaspay\CardMaintenanceEvent;
use App\Events\Soleaspay\CardPayementEvent;
use App\Events\Soleaspay\CardPayementFailedEvent;
use App\Events\Soleaspay\CardTerminatedEvent;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

//The class extends "ProcessWebhookJob" class as that is the class 
//that will handle the job of processing our webhook before we have 
//access to it.

class SoleaspayWebhook extends ProcessWebhookJob
{
    public function handle()
    {
        $dat = json_decode($this->webhookCall, true);
        session()->put('local', 'fr');
       // Log::info($dat);
        //http_response_code(200);
        //$data = $dat['payload'];
        //Log::useDailyFiles(storage_path().'/logs/Soleaspay.log');
        $data = $dat['payload'];
        Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/soleaspay.log'),
        ])->info($data);
        Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/soleaspay.log'),
        ])->info("card DATA");
        Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/soleaspay.log'),
        ])->info($data['data']);
        Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/soleaspay.log'),
        ])->info("card AMOUNT");
        Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/soleaspay.log'),
        ])->info($data['data']['amount']);
        
    
        if($data['data']['operation'] == 'CARDTRANSACTION'){
          //$this->cardPayement($data);
        }else if($data['data']['operation'] == 'CARDDECLINE'){
          Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/soleaspay.log'),
          ])->info("card DECLINE");
          $this->cardPayementFailed($data);
        }/*else if($data['operation'] == 'card.subscriptionRenewal'){
          $this->cardMaintenance($data);
        }*/

        //Acknowledge you received the response
        http_response_code(200);
    }

  public function cardTerminated($data){
    event(new CardTerminatedEvent($data));
  }
  public function cardPayement($data){
    event(new CardPayementEvent($data));
  }
  public function cardPayementFailed($data){
    event(new CardPayementFailedEvent($data));
  }
}
<?php

namespace App\Handler;

use App\Events\Eversend\CardAdjustedEvent;
use App\Events\Eversend\CardMaintenanceEvent;
use App\Events\Eversend\CardPayementEvent;
use App\Events\Eversend\CardPayementFailedEvent;
use App\Events\Eversend\CardTerminatedEvent;
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
       // Log::info($dat);
        //http_response_code(200);
        //$data = $dat['payload'];
        //Log::useDailyFiles(storage_path().'/logs/eversend.log');
        /*Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/eversend.log'),
        ])->info($dat);*/
        $data = $dat['payload'];
        
    
       /* if ($data['eventType'] == 'card.adjusted') {
          $this->cardAjusted($data);
        }else if($data['eventType'] == 'card.terminated'){
          $this->cardTerminated($data);
        }else if($data['eventType'] == 'card.authorization'){
          $this->cardPayement($data);
        }else if($data['eventType'] == 'card.authDeclined'){
          $this->cardPayementFailed($data);
        }else if($data['eventType'] == 'card.subscriptionRenewal'){
          $this->cardMaintenance($data);
        }*/

        //Acknowledge you received the response
        http_response_code(200);
    }

  public function cardAjusted($data){
    event(new CardAdjustedEvent($data));
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
  public function cardMaintenance($data){
    event(new CardMaintenanceEvent($data));
  }
}
<?php

namespace App\Handler;

use App\Events\Strowallet\CardPayementEvent;
use App\Events\Strowallet\CardPayementFailedEvent;
use App\Events\Strowallet\CardTerminatedEvent;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

//The class extends "ProcessWebhookJob" class as that is the class 
//that will handle the job of processing our webhook before we have 
//access to it.

class MapleradWebhook extends ProcessWebhookJob
{
    public function handle()
    {
        $dat = json_decode($this->webhookCall, true);
       // Log::info($dat);
        //http_response_code(200);
        //$data = $dat['payload'];
        //Log::useDailyFiles(storage_path().'/logs/eversend.log');
        Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/maplerad.log'),
        ])->info($dat);
        $data = $dat['payload'];
        Log::build([
          'driver' => 'single',
          'path' => storage_path('logs/maplerad.log'),
        ])->info($data);
        
    
       if($data['event'] == 'issuing.transaction'){
        if($data['type']=='DECLINE'){
          $this->cardPayementFailed($data);
        }elseif($data['type']=='AUTHORIZATION'){
          $this->cardPayement($data);
        }
          //$this->cardTerminated($data);
        }else if($data['event'] == 'issuing.terminated'){
          $this->cardTerminated($data);
        }

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
<?php

namespace App\Listeners\Eversend;

use App\Events\Eversend\CardPayementFailedEvent;
use App\Models\EversendVirtualCard;
use App\Models\User;
use App\Models\VirtualCardApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\Webhook\CardPayementFailedMail;
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
       ])->info($data);
       print_r($data);
       $card_id=$data['cardId'];
         $card = EversendVirtualCard::where('card_id',$card_id)->first();
         $api =VirtualCardApi::where('name','eversend')->first();
         if($card){
             //$activated=$data['activated'];
             $user=User::find($card->user_id);
             $nbtrx=$card->nb_trx_failed+1;
             //$card->nb_trx_failed+=1;
             //$card->save();
             if($user){
                if($nbtrx==1&&$api->is_activate_penality){

                }else if($nbtrx==1&&(!$api->is_activate_penality)){

                }else if($nbtrx>=$api->nb_trx_failled&&$api->is_activate_penality){

                }else if($nbtrx>=$api->nb_trx_failled&&(!$api->is_activate_penality)){}
                 Log::build([
                     'driver' => 'single',
                     'path' => storage_path('logs/eversend.log'),
                   ])->info($data);
                 $not=[];
                 $not['card']=$card;
                 $not['data']=$data;
                 $user->notify(new CardPayementFailedMail($user,$not));
             }
         }
    }
}

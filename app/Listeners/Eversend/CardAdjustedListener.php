<?php

namespace App\Listeners\Eversend;

use App\Events\Eversend\CardAdjustedEvent;
use App\Models\EversendVirtualCard;
use App\Models\User;
use App\Notifications\Webhook\CardAdjustedMail;
use Illuminate\Support\Facades\Log;


class CardAdjustedListener
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
     * @param  \App\Events\Eversend\CardAdjustedEvent  $event
     * @return void
     */
    public function handle(CardAdjustedEvent $event)
    {
       $data= $event->data;
       Log::build([
        'driver' => 'single',
        'path' => storage_path('logs/eversend.log'),
      ])->info($data);
     // print_r($data);
      //print_r($data['cardId']);
      $card_id=$data['cardId'];
        $card = EversendVirtualCard::where('card_id',$card_id)->first();
        if($card){
            $activated=$data['activated'];
            $user=User::find($card->user_id);
            if((!$activated)&&$card->status=="active"){
                $card->status="frozen";
                $card->save();
                $server=[];
                $server['evenement']="frozen-card";
                $server['user_id']=$user->id;
                $server['card_id']=$card->id;
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/eversend.log'),
                  ])->info($data);
            }
            if($user){
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/eversend.log'),
                  ])->info($data);
                $not=[];
                $not['data']=$data;
                $not['card']=$card;
                $user->notify(new CardAdjustedMail($user,$not));
            }
        }
    }
}

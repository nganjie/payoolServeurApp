<?php

namespace App\Listeners\Eversend;

use App\Events\Eversend\CardPayementEvent;
use App\Models\EversendVirtualCard;
use App\Models\User;
use App\Notifications\Webhook\CardPayementMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CardPayementListener
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
     * @param  \App\Events\Eversend\CardPayementEvent  $event
     * @return void
     */
    public function handle(CardPayementEvent $event)
    {
        $data= $event->data;
       Log::build([
        'driver' => 'single',
        'path' => storage_path('logs/eversend.log'),
      ])->info($data);
      print_r($data);
      $card_id=$data['cardId'];
        $card = EversendVirtualCard::where('card_id',$card_id)->first();
        if($card){
            //$activated=$data['activated'];
            $user=User::find($card->user_id);
           /* $card->status="frozen";
            $card->is_deleted=true;
            $card->save();*/
            if($user){
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/eversend.log'),
                  ])->info($data);
                $not=[];
                $not['card']=$card;
                $not['data']=$data;
                $user->notify(new CardPayementMail($user,$not));
            }
        }
    }
}

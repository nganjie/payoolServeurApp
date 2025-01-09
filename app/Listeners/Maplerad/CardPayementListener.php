<?php

namespace App\Listeners\Maplerad;

use App\Events\Maplerad\CardPayementEvent;
use App\Models\MapleradVirtualCard;
use App\Models\User;
use App\Notifications\Webhook\Maplerad\CardPayementMail;
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
     * @param  \App\Events\Maplerad\CardPayementEvent  $event
     * @return void
     */
    public function handle(CardPayementEvent $event)
    {
        $data= $event->data;
       Log::build([
        'driver' => 'single',
        'path' => storage_path('logs/maplerad.log'),
      ])->info("succes payement");
      $card_id=$data['card_id'];
        $card = MapleradVirtualCard::where('card_id',$card_id)->first();
        if($card){
            //$activated=$data['activated'];
            $user=User::find($card->user_id);
           /* $card->status="frozen";
            $card->is_deleted=true;
            $card->save();*/
            if($user){
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/maplerad.log'),
                  ])->info($data);
                $not=[];
                $not['card']=$card;
                $not['data']=$data;
                $user->notify(new CardPayementMail($user,$not));
            }
        }
    }
}

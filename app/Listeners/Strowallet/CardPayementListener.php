<?php

namespace App\Listeners\Strowallet;

use App\Events\Strowallet\CardPayementEvent;
use App\Models\StrowalletVirtualCard;
use App\Models\User;
use App\Notifications\Webhook\Strowallet\CardPayementMail;
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
     * @param  \App\Events\Strowallet\CardPayementEvent  $event
     * @return void
     */
    public function handle(CardPayementEvent $event)
    {
        $data= $event->data;
       Log::build([
        'driver' => 'single',
        'path' => storage_path('logs/strowallet.log'),
      ])->info("succes payement");
      print_r($data);
      $card_id=$data['cardId'];
        $card = StrowalletVirtualCard::where('card_id',$card_id)->first();
        if($card){
            //$activated=$data['activated'];
            $user=User::find($card->user_id);
           /* $card->status="frozen";
            $card->is_deleted=true;
            $card->save();*/
            if($user){
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/strowallet.log'),
                  ])->info($data);
                $not=[];
                $not['card']=$card;
                $not['data']=$data;
                $user->notify(new CardPayementMail($user,$not));
            }
        }
    }
}

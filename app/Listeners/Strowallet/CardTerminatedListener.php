<?php

namespace App\Listeners\Strowallet;

use App\Events\Strowallet\CardTerminatedEvent;
use App\Models\StrowalletVirtualCard;
use App\Models\User;
use App\Notifications\Webhook\Strowallet\CardTerminatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CardTerminatedListener
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
     * @param  \App\Events\Strowallet\CardTerminatedEvent  $event
     * @return void
     */
    public function handle(CardTerminatedEvent $event)
    {
        $data= $event->data;
       Log::build([
        'driver' => 'single',
        'path' => storage_path('logs/strowallet.log'),
      ])->info($data);
      print_r($data);
      $card_id=$data['cardId'];
        $card = StrowalletVirtualCard::where('card_id',$card_id)->first();
        if($card){
            //$activated=$data['activated'];
            $user=User::find($card->user_id);
            $card->status="frozen";
            $card->is_deleted=true;
            $card->save();
            if($user){
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/strowallet.log'),
                  ])->info($data);
                $not=[];
                $not['card']=$card;
                $not['data']=$data;
                $user->notify(new CardTerminatedMail($user,$not));
            }
        }
    }
}

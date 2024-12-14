<?php

namespace App\Listeners\Eversend;

use App\Events\Eversend\CardTerminatedEvent;
use App\Models\EversendVirtualCard;
use App\Models\User;
use App\Notifications\Webhook\CardTerminatedMail;
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
     * @param  \App\Events\Eversend\CardTerminatedEvent  $event
     * @return void
     */
    public function handle(CardTerminatedEvent $event)
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
            $card->status="frozen";
            $card->is_deleted=true;
            $card->save();
            if($user){
                Log::build([
                    'driver' => 'single',
                    'path' => storage_path('logs/eversend.log'),
                  ])->info($data);
                $not=[];
                $not['card']=$card;
                $user->notify(new CardTerminatedMail($user,$not));
            }
        }
    }
}

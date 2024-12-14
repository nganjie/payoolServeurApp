<?php

namespace App\Listeners\Eversend;

use App\Events\Eversend\CardMaintenanceEvent;
use App\Models\EversendVirtualCard;
use App\Models\User;
use App\Notifications\Webhook\CardMaintenanceMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CardMaintenanceListener
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
     * @param  \App\Events\Eversend\CardMaintenanceEvent  $event
     * @return void
     */
    public function handle(CardMaintenanceEvent $event)
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
             $activated=$data['activated'];
             $user=User::find($card->user_id);
             /*$card->status="frozen";
             $card->is_deleted=true;
             $card->save();*/
             if($user){
                 Log::build([
                     'driver' => 'single',
                     'path' => storage_path('logs/eversend.log'),
                   ])->info($data);
                 $status=$status=$activated?__('Card - Active'):__('Card - Suspended');
                 $not=[];
                 $not['status']=$status;
                 $not['card']=$card;
                 $not['data']=$data;
                 $user->notify(new CardMaintenanceMail($user,$not));
             }
         }
    }
}

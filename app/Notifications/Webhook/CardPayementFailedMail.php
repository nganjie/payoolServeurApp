<?php

namespace App\Notifications\Webhook\Strowallet;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CardPayementFailedMail extends Notification
{
    use Queueable;

    public $user;
    public $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user,$data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {


        $user = $this->user;
        $data = $this->data;
        $date = Carbon::now();
        //dump($data);
        //dd($data->request_amount);
        $dateTime = $date->format('Y-m-d h:i:s A');
        return (new MailMessage)
                    ->greeting(__("Hello")." ".$user->fullname." !")
                    ->subject(__("Virtual Card Transaction ( Payement Failed)")." ". $data["card"]["mask"].' ')
                    ->line(__("Card Information").", ".$data["card"]["mask"])
                    ->line(__("Message")." : ".__("Payment failure, insufficient balance"))
                    ->line(__("number of failures").': '.$data['nbtrx'])
                    ->line(__("maximum number of failures").': '.$data['nbtrx_max'])
                    ->line(__("warning").': '.__("if you make successive attempts to make a payment error, your card will be blocked and you will have to pay a fine of",['nbtrx'=>$data['nbtrx'],'amount'=>$data['amande']]))
                    ->line(__("reason")." : ". $data["data"]['reason'])
                    ->line(__("amount")." : ".$data["data"]['amount']." USD")
                    ->line(__("narrative")." : ".$data["data"]['narrative'])
                    //->line(__("Status").": ". $data["data"]['cardStatus'])
                    ->line(__("Date And Time").": " .$dateTime)
                    ->line(__('Thank you for using our application!'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

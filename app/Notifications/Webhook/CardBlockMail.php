<?php

namespace App\Notifications\Webhook;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CardBlockMail extends Notification
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
                    ->subject(__("card blocking").' : '.__("Virtual Card Transaction ( Payement Failed)")." ". $data["card"]["mask"].' ')
                    ->line(__("Card Information").", ".$data["card"]["mask"])
                    ->line(__("Message")." : ".__("Payment failure, insufficient balance"))
                    ->line(__("number of failures").': '.$data['nbtrx'])
                    ->line(__("maximum number of failures").': '.$data['nbtrx_max'])
                    ->line(__("warning").': '.__("Your card has been blocked, you must go to your payool account to pay a fine of to be able to unblock it",['amount'=>$data['amande']]))
                    ->line(__("card Acceptor Name")." : ". $data["data"]['name'])
                    ->line(__("card Acceptor City")." : ". $data["data"]['city'])
                    ->line(__("amount")." : ".$data["data"]['amount']."".$data['data']['currencyCode'])
                    ->line(__("Available Balance")." : ".$data["data"]['availableBalance']."".$data['data']['currencyCode'])
                    ->line(__("Status").": ". $data["data"]['cardStatus'])
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

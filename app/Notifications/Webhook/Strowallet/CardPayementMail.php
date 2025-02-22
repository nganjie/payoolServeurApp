<?php

namespace App\Notifications\Webhook\Strowallet;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CardPayementMail extends Notification
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
                    ->subject(__("Virtual Card Transaction ( Payement)")." ". $data["card"]["card_brand"].' ')
                    ->line(__("Card Information").", ".$data["card"]["card_brand"])
                    ->line(__("Message")." : ".__("you have made a payment"))
                    ->line(__("card Acceptor Name")." : ". $data["data"]['cardAcceptorName'])
                    ->line(__("card Acceptor City")." : ". $data["data"]['cardAcceptorCity'])
                    ->line(__("amount")." : ".$data["data"]['amount']."".$data['data']['currency'])
                    ->line(__("balance")." : ".$data["data"]['balance']."".$data['data']['currency'])
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

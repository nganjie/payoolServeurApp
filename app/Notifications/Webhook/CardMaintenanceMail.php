<?php

namespace App\Notifications\Webhook;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class CardMaintenanceMail extends Notification
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
                    ->subject(__("Carte virtuelle (frais de maintenance de la carte réussis)")." ". $data["card"]["mask"].' ')
                    ->line(__("Informations sur la carte").", ".$data["card"]["mask"])
                    ->line(__("Message")." : ".__("Subscription Renewal"))
                    ->line(__("Nom de l'accepteur de carte")." : ". $data["data"]['name'])
                    ->line(__("Ville accepteur de cartes")." : ". $data["data"]['city'])
                    ->line(__("solde")." : ".$data["data"]['balance']."".$data['data']['currency'])
                    ->line(__("Statu").": ". $data["data"]['cardStatus'])
                    ->line(__("Date et heure").": " .$dateTime)
                    ->line(__("Merci d'utiliser notre application !"));
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

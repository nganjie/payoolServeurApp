<?php

namespace App\Notifications\Webhook\Soleaspay;

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
        $amount=$data["data"]['amount']/100;
        $authAmount=$data["data"]['authorization_amount']/100;
        //dump($data);
        //dd($data->request_amount);
        $dateTime = $date->format('Y-m-d h:i:s A');
        return (new MailMessage)
                    ->greeting(__("Hello")." ".$user->fullname." !")
                    ->subject(__("Transaction par carte virtuelle (paiement)")." ". $data["card"]["masked_pan"].' ')
                    ->line(__("Informations sur la carte").", ".$data["card"]["masked_pan"])
                    ->line(__("Message")." : ".__("vous avez effectué un paiement"))
                    ->line(__("Nom de l'accepteur de carte")." : ". $data["extra_data"]['merchant']['name'])
                    ->line(__("Ville accepteur de cartes")." : ". $data["extra_data"]['merchant']['city'])
                    ->line(__("Montant")." : ".$amount."".$data['data']['currency'])
                    ->line(__("Montant de l'autorisation")." : ".$authAmount."".$data['data']['currency'])
                    //->line(__("Status").": ". $data["data"]['cardStatus'])
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

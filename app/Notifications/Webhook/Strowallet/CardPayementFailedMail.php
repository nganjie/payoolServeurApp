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
                    ->subject(__("Transaction par carte virtuelle (échec du paiement)")." ". $data["card"]["card_brand"].' ')
                    ->line(__("Informations sur la carte").", ".$data["card"]["card_brand"])
                    ->line(__("Message")." : ".__("Échec de paiement, solde insuffisant"))
                    ->line(__("Nombre d'échecs").': '.$data['nbtrx'])
                    ->line(__("Nombre maximal d'échecs").': '.$data['nbtrx_max'])
                    ->line(__("avertissement").': '.__("si vous faites des tentatives successives d'erreur de paiement, votre carte sera bloquée et vous devrez payer une amende de",['nbtrx'=>$data['nbtrx'],'amount'=>$data['amande']]))
                    ->line(__("raison")." : ". $data['data']['reason'])
                    ->line(__("Montant")." : ".$data['data']['amount']." USD")
                    ->line(__("narratif")." : ".$data['data']['narrative'])
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

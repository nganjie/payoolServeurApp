<?php

namespace App\Notifications\User;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SendMessageMail extends Notification
{
    use Queueable;

    protected $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
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
        $user = $notifiable;
        $data = $this->data;
        $date = Carbon::now();
        $dateTime = $date->format('Y-m-d h:i:s A');

        return (new MailMessage)
                    ->greeting("Mise à jour de votre solde par l'administration")
                    ->subject(__("Mise à jour de votre solde"))
                    ->line('Type : '.ucwords($data['type']))
                    ->line('Message : '.ucwords($data['message']))
                    ->line(__("Remarque").' : '.new HtmlString(ucwords($data['remark'])))
                    ->line(__("Date").' : '.$dateTime)
                    ->line(__("Montant")." : ".$data['amount'].' USD')
                    ->line(__("Solde Disponible").' : '.$data['available_balance']);
                    
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

<?php

namespace App\Notifications\PORTAL;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PortalEmailNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $subject, $content, $fromDesc, $linkPost, $user;
    /**
     * Create a new notif instance.
     */
    public function __construct($subject, $content, $fromDesc, $linkPost, $user = null)
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->fromDesc = $fromDesc;
        $this->linkPost = $linkPost;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->markdown('notification', [
                'to_user' => $this->user,
                'content' => $this->content,
                'action' => url($this->linkPost)
            ])
            ->from(config('mail.from.address'), $this->fromDesc);
        }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

<?php

namespace App\Notifications\PORTAL;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Illuminate\Support\Facades\Log; // <--- Import Log Facade

class PortalEmailNotification extends Notification
{
    use Queueable;

    public $subject, $content, $fromDesc, $linkPost, $user, $sentMode;

    public function __construct($subject, $content, $fromDesc, $linkPost, $user = null, $sentMode = ['email', 'webpush'])
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->fromDesc = $fromDesc;
        $this->linkPost = $linkPost;
        $this->user = $user;
        $this->sentMode = $sentMode;
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        // Add mail channel if included in sentMode
        if (in_array('email', $this->sentMode)) {
            $channels[] = 'mail';
        }

        // Cek apakah penerima adalah Object User asli (bukan string email anonim)
        // Kita log dulu identitas penerimanya
        Log::info("PortalEmailNotification: Memeriksa channel untuk ID: " . ($notifiable->id ?? 'Anonim') . " Class: " . get_class($notifiable));

        // Cek apakah dia punya trait WebPush
        if (in_array('webpush', $this->sentMode) && method_exists($notifiable, 'routeNotificationForWebPush')) {
            Log::info("PortalEmailNotification: User valid untuk WebPush. Menambahkan channel.");
            $channels[] = WebPushChannel::class;
        } else {
            Log::warning("PortalEmailNotification: User TIDAK support WebPush (Mungkin AnonymousNotifiable atau Trait lupa di-import).");
        }

        return $channels;
    }

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

    public function toWebPush($notifiable, $notification)
    {
        // --- LOGGING POINT ---
        // Kalau log ini muncul, berarti Laravel SUDAH BERHASIL masuk ke tahap pembuatan pesan WebPush.
        Log::info("PortalEmailNotification: Sedang menyusun pesan WebPush untuk User ID: " . $notifiable->id);
        Log::info("PortalEmailNotification: Judul -> " . $this->subject);

        return (new WebPushMessage)
            ->title($this->subject)
            ->icon('/icons/icon.png')
            ->body(strip_tags($this->content))
            ->action('Lihat Detail', 'open_url')
            ->data(['url' => url($this->linkPost)]);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
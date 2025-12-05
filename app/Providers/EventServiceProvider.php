<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(NotificationFailed::class, function ($event) {
            // Kita catat semua detail error ke log
            Log::error("GAGAL KIRIM NOTIFIKASI!");
            Log::error("Channel: " . $event->channel);
            Log::error("User ID: " . $event->notifiable->id);

            // Ini yang paling penting: Pesan error dari Google/Mozilla
            // Kadang ada di $event->data atau property lain tergantung library
            Log::error("Data: " . json_encode($event->data));
        });
    }
}

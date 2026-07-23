<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Notifications\ResetPassword;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(database_path('migrations/custom'));

        Storage::disk('local')->buildTemporaryUrlsUsing(function ($path, $expiration, $options) {
            return URL::temporarySignedRoute(
                'local.temp',
                $expiration,
                array_merge($options, ['path' => $path])
            );
        });

        // Password reset link in email template...
        ResetPassword::createUrlUsing(static function ($notifiable, $token) {
            // Url of the fronted app for resetting password...
            return 'http://192.168.100.32:8081/portal_v2/#/reset-password/'.$token;
        });
    }
}

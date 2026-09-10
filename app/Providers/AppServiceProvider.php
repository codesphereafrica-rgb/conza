<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo+api', function () {
            $apiKey = config('services.brevo.key');

            if (!$apiKey) {
                throw new \RuntimeException('BREVO_API_KEY is not configured.');
            }

            return (new BrevoTransportFactory(null, HttpClient::create()))->create(
                new Dsn('brevo+api', 'default', $apiKey)
            );
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}

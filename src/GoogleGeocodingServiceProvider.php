<?php

namespace FHusquinet\GoogleGeocoding;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class GoogleGeocodingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/google-geocoding.php' => config_path('google-geocoding.php'),
            ], 'config');

            if (!class_exists('CreateGoogleGeocodingRequestsTable')) {
                $timestamp = date('Y_m_d_His', time());
                $this->publishes([
                    __DIR__ . '/../migrations/create_google_geocoding_requests_table.php.stub' => database_path(
                        "/migrations/{$timestamp}_create_google_geocoding_requests_table.php"
                    ),
                ], 'migrations');
            }
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->app->bind('geocoder', function ($app, $arguments) {
            return new GoogleGeocoding($app->make(Client::class));
        });

        $this->app->bind(GoogleGeocoding::class, function ($app, $arguments) {
            return new GoogleGeocoding($app->make(Client::class));
        });
    }
}

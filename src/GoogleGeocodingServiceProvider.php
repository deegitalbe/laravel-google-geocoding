<?php

namespace FHusquinet\GoogleGeocoding;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use FHusquinet\GoogleGeocoding\GoogleGeocoding;

class GoogleGeocodingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/google-geocoding.php' => config_path('google-geocoding.php'),
            ], 'config');
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

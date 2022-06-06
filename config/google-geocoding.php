<?php

use FHusquinet\GoogleGeocoding\GeocodingResult;
use FHusquinet\GoogleGeocoding\GeocodingResults;

return [
    /**
     * The API key used to call Google Geocoding services.
     */
    'key'               => env('GOOGLE_GEOCODING_API'),

    /**
     * The default country used to filter the results.
     * If left to null no filtering will take place by default.
     *
     * This can be overwritten on a request basis.
     */
    'country'           => null,

    /**
     * The default language used to retrieve the results.
     * This is your application's locale by default.
     *
     * This can be overwritten on a request basis.
     */
    'language'          => config('app.locale', 'en'),

    /**
     * The fallback language used in case the locale isn't defined
     * in the app config file.
     */
    'fallback_language' => 'en',

    /**
     * The cache duration used to store Google's results, the value should be in minutes.
     *
     * The default is set to 30 days which is complient with Google Maps Platform Terms of Service.
     * See: https://developers.google.com/maps/documentation/geocoding/geocoding-strategies#caching
     * for more informations.
     */
    'cache_duration'    => 60 * 24 * 30,

    /**
     * The classes used throughout this package.
     *
     * Only change these values if you wish to customize their behaviour for your application's need.
     */
    'classes'           => [
        'collection' => GeocodingResults::class,
        'item'       => GeocodingResult::class,
    ],

    /**
     * Log errors when the Google API returns an error.
     */
    'log_errors'        => true,

    /**
     * Log all requests made into your database, with the parameters and returned data.
     * This is usefull for debugging requests, and knowing how much you use to prevent
     * over-spending.
     */
    'log_requests'      => true,
    'table_name'        => 'google_geocoding_requests',
];

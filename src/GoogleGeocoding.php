<?php

namespace FHusquinet\GoogleGeocoding;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class GoogleGeocoding
{

    /**
     * The HTTP Client used to make the requests.
     */
    public $client;

    /**
     * The base URL used to generate the final URL.
     */
    public $baseUrl;

    /**
     * The URL used to call Google's API.
     */
    public $url;

    /**
     * The URL parameters used to generate the final URL.
     */
    public $urlParameters = [];

    /**
     * The components defined to filter the query for more accurate results.
     */
    public $components = [];
    
    /**
     * Inject Guzzle's HTTP Client into the class and setup the base url.
     */
    public function __construct(Client $client)
    {
        if ( ! config('google-geocoding.key') ) {
            throw new InvalidArgumentException('You need to set a key to use Google\'s Geocoding API.');
        }

        $this->client = $client;

        $this->baseUrl = "https://maps.googleapis.com/maps/api/geocode/json";

        $this->urlParameters = [
            'key' => config('google-geocoding.key'),
            'language' => config('google-geocoding.language') ?? config('google-geocoding.fallback_language'),
            'sensor' => 'false'
        ];

        if ( config('google-geocoding.country') ) {
            $this->components = ['country' => strtoupper( config('google-geocoding.country') )];
        }

        $this->buildUrl();
    }

    /**
     * Get the results from the API and return a collection.
     */
    public function get()
    {
        if ( cache()->has( $this->getCacheKey() ) ) {
            return cache()->get( $this->getCacheKey() );
        }

        $response = $this->callApi();
        $body = json_decode($response->getBody(), true);

        if ( ! isset($body['status']) || ( $body['status'] !== 'OK' && $body['status'] !== 'ZERO_RESULTS' ) ) {
            return null;
        }

        return cache()->remember($this->getCacheKey(), config('google-geocoding.cache_duration'), function () use ($body) {
            $class = config('google-geocoding.classes.collection');
            return $class::make( $body['results'] );
        });
    }

    /**
     * Get the first item in the collection.
     */
    public function first()
    {
        if ( $results = $this->get() ) {
            return $results->first();
        }

        return null;
    }

    /**
     * Call Google's API using the URL generated.
     */
    public function callApi()
    {
        return $this->client->get( $this->getUrl() );
    }

    /**
     * Define the address of the query.
     */
    public function address($query)
    {
        $this->addParameter('address', urlencode($query));

        return $this;
    }

    /**
     * Define the coordinates of the query.
     */
    public function coordinates($latitude, $longitude)
    {
        $this->addParameter('latlng', $latitude . ',' . $longitude);

        return $this;
    }

    /**
     * Define the language of the query.
     */
    public function language($language)
    {
        $this->addParameter('language', $language);

        return $this;
    }

    /**
     * Define the country of the query.
     */
    public function country($country)
    {
        $this->addComponent('country', strtoupper($country));

        return $this;
    }

    /**
     * Add a new key value pair to the components and build the URL.
     */
    public function addComponent($key, $value = null)
    {
        if ( ! is_array($key) && $value ) {
            return $this->addComponents([$key => $value]);
        }
        
        return $this->addComponents($key);
    }

    /**
     * Add a new set of key value pairs to the components and build the URL.
     */
    public function addComponents($pairs)
    {
        $this->components = array_merge($this->components, $pairs);
        $this->buildUrl();

        return $this;
    }

    /**
     * Unset a component.
     */
    public function unsetComponent($key)
    {
        unset($this->components[$key]);
        $this->buildUrl();

        return $this;
    }

    /**
     * Add a new key value pair to the url parameters and build the URL.
     */
    public function addParameter($key, $value = null)
    {
        if ( ! is_array($key) && $value ) {
            return $this->addParameters([$key => $value]);
        }
        
        return $this->addParameters($key);
    }

    /**
     * Add a new set of key value pairs to the url parameters and build the URL.
     */
    public function addParameters($pairs)
    {
        $this->urlParameters = array_merge($this->urlParameters, $pairs);
        $this->buildUrl();

        return $this;
    }

    /**
     * Unset an URL parameter.
     */
    public function unsetParameter($key)
    {
        unset($this->urlParameters[$key]);
        $this->buildUrl();

        return $this;
    }

    /**
     * Build the final URL using the parameters defined.
     */
    public function buildUrl()
    {
        $this->url = $this->baseUrl . '?' . urldecode( http_build_query( $this->getUrlParameters() ) );

        return $this;
    }

    /**
     * Get the URL parameters.
     */
    public function getUrlParameters()
    {
        $parameters = $this->urlParameters;

        if ( ! empty($this->components) ) {
            $components = '';
            foreach ( $this->components as $key => $value ) {
                $components .= $key . ':' . $value . '|';
            }
            $parameters['components'] = rtrim($components, '|');
        }

        return $parameters;
    }

    /**
     * Get the URL.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get the cache key for the URL.
     */
    public function getCacheKey()
    {
        return 'google-geocoding-' . $this->getUrl();
    }

}
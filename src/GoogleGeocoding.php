<?php

namespace FHusquinet\GoogleGeocoding;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Set the locale used for the request.
     */
    public function locale($locale)
    {
        $this->urlParameters['language'] = $locale;
        $this->buildUrl();

        return $this;
    }

    /**
     * Get the results from the API and return a collection.
     */
    public function get()
    {
        if ( cache()->has( $this->getCacheKey() ) ) {
            $this->increaseCacheUsesInLogs();
            return cache()->get( $this->getCacheKey() );
        }

        $response = $this->callApi();
        $body = json_decode($response->getBody(), true);

        if ( ! isset($body['status']) || ( $body['status'] !== 'OK' && $body['status'] !== 'ZERO_RESULTS' ) ) {
            if ( config('google-geocoding.log_errors') ) {
                if ( $body['error_message'] ) {
                    Log::error('Google returned an error when requesting geocoding results with url ['.$this->getUrl().']: '.$body['error_message']);
                } else {
                    Log::error('Google returned an error when requesting geocoding results with url ['.$this->getUrl().']: '.json_encode($body['error_message']));
                }
            }
            
            $this->storeInLogs($body);

            return null;
        }

        return cache()->remember($this->getCacheKey(), now()->addMinutes(config('google-geocoding.cache_duration')), function () use ($body) {
            $this->storeInLogs($body);
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
    public function coordinates($latitude, $longitude, $keepComponents = false)
    {
        if ( ! $keepComponents ) {
            $this->unsetAllComponents();
        }

        $this->addParameter('latlng', $latitude . ',' . $longitude);

        return $this;
    }

    /**
     * Define the coordinates of the query and keep the components by setting the flag to true.
     */
    public function coordinatesWithComponents($latitude, $longitude)
    {
        return $this->coordinates($latitude, $longitude, true);
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
     * Remove the language of the query.
     */
    public function removeLanguage()
    {
        $this->unsetParameter('language');

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
     * Remove the country of the query.
     */
    public function removeCountry()
    {
        $this->unsetComponent('country');

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
     * Unset all the components.
     */
    public function unsetAllComponents()
    {
        foreach ( $this->components as $key => $value ) {
            $this->unsetComponent($key);
        }

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
        $this->url = rtrim($this->baseUrl . '?' . urldecode( http_build_query( $this->getUrlParameters() ) ), '?');

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

    protected function storeInLogs(array $response, bool $cached = false)
    {
        if ( ! config('google-geocoding.log_requests') ) {
            return;
        }

        try {
            DB::table(config('google-geocoding.table_name'))->insert([
                'successful' => isset($response['status']) ? $response['status'] == 'OK' : false,
                'url' => $this->getUrl(),
                'parameters' => json_encode($this->getUrlParameters()),
                'response' => json_encode($response),
                'loaded_from_cache' => $cached,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Could not save google geocoding request in logs: ' . $e->getMessage());
        }
    }

    protected function increaseCacheUsesInLogs()
    {
        if ( ! config('google-geocoding.log_requests') ) {
            return;
        }

        try {
            DB::table(config('google-geocoding.table_name'))
                ->where('url', $this->getURL())
                ->update([
                    'cache_uses' => DB::raw('cache_uses + 1'),
                    'updated_at' => now()->toDateTimeString(),
                ]);
        } catch (\Exception $e) {
            Log::error('Could not update cache use for google geocoding request with url ['.$this->getUrl().'].');
        }

        $this->storeInLogs([], true);
    }

}
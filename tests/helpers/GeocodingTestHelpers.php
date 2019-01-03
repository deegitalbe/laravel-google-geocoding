<?php

namespace FHusquinet\GoogleGeocoding\Tests\Helpers;

use Mockery;
use GuzzleHttp\Psr7\Response;

trait GeocodingTestHelpers
{


    public function setLanguage($language)
    {
        config()->set(['google-geocoding.language' => $language]);
    }

    public function setKey($key)
    {
        config()->set(['google-geocoding.key' => $key]);
    }

    public function setCacheDuration($duration)
    {
        config()->set(['google-geocoding.cache_duration' => $duration]);
    }

    public function mock($class, $partial = false)
    {
        if ( $partial ) {
            $mock = Mockery::mock($class)->makePartial();
        } else {
            $mock = Mockery::mock($class);
        }

        $this->app->instance($class, $mock);

        return $mock;
    }

    public function createGuzzleResponse($body, $status = 200, $headers = [])
    {
        if ( is_array($body) ) {
            $body = json_encode($body);
        }
        return new \GuzzleHttp\Psr7\Response($status, $headers, $body);
    }
}
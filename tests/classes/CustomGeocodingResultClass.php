<?php

namespace FHusquinet\GoogleGeocoding\Tests\Classes;

use FHusquinet\GoogleGeocoding\GeocodingResult;

class CustomGeocodingResultClass extends GeocodingResult
{
    
    public function toArray()
    {
        return [
            'latitude'  => $this->geometry['location']['lat'],
            'longitude' => $this->geometry['location']['lng'],
        ];
    }

}
<?php

namespace FHusquinet\GoogleGeocoding;

use Illuminate\Contracts\Support\Arrayable;

class GeocodingResult implements Arrayable
{

    /**
     * Define each element of data given as a property on the object.
     */
    public function __construct($data = [])
    {
        foreach ( $data as $key => $value ) {
            $this->{$key} = $value;
        }
    }

    /**
     * Create a new instance.
     *
     * @param  mixed  $data
     * @return static
     */
    public static function make($data = [])
    {
        return new static($data);
    }

    /**
     * Return an array containing all the geocoding result's important data as a single array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [
            'country' => '',
            'region' => '',
            'city' => '',
            'postal_code' => '',
            'street_name' => '',
            'street_number' => '',
            'latitude'  => $this->geometry['location']['lat'],
            'longitude' => $this->geometry['location']['lng'],
        ];

        foreach ( $this->address_components as $component ) {
            foreach ( $component['types'] as $type ) {
                if ($type == 'street_number' && empty($data['street_number'])) {
                    $data['street_number'] = $component['long_name'];
                }
                if ($type == 'route' && empty($data['street_name'])) {
                    $data['street_name'] = $component['long_name'];
                }
                if ($type == 'locality' && empty($data['city'])) {
                    $data['city'] = $component['long_name'];
                }
                if ($type == 'postal_code' && empty($data['postal_code'])) {
                    $data['postal_code'] = $component['long_name'];
                }
                if ($type == 'country' && empty($data['country'])) {
                    $data['country'] = $component['long_name'];
                }
                if (($type == 'administrative_area_level_1' || $type == 'administrative_area_level_2') && empty($data['region']) ) {
                    $data['region'] = $component['long_name'];
                }
            }
        }
        
        return $data;
    }
}
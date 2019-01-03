<?php

namespace FHusquinet\GoogleGeocoding;

use Illuminate\Support\Collection;
use FHusquinet\GoogleGeocoding\GeocodingResult;

class GeocodingResults extends Collection
{

    /**
     * Create a new collection.
     *
     * @param  mixed  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $items = $this->getArrayableItems($items);

        foreach ( $items as $item ) {
            $class = config('google-geocoding.classes.item');
            $this->items[] = $class::make($item);
        }
    }
}
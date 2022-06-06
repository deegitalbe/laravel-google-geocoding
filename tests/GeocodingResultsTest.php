<?php

namespace FHusquinet\GoogleGeocoding\Tests;

use FHusquinet\GoogleGeocoding\GeocodingResult;
use FHusquinet\GoogleGeocoding\GeocodingResults;
use FHusquinet\GoogleGeocoding\Tests\Classes\CustomGeocodingResultClass;

class GeocodingResultsTest extends TestCase
{

    /** @test */
    public function it_will_create_a_new_geocoding_result_class_for_each_element_of_the_array_given_when_creating_the_collection(
    )
    {
        $collection = new GeocodingResults([
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
        ]);

        $this->assertInstanceOf(GeocodingResult::class, $collection->first());
        $this->assertEquals(1, $collection->first()->id);
    }

    /** @test */
    public function it_will_use_the_class_set_in_the_config_when_creating_a_new_geocoding_result_class()
    {
        config(
            ['google-geocoding.classes.item' => CustomGeocodingResultClass::class]
        );

        $collection = new GeocodingResults([
            [
                'id' => 1,
            ],
            [
                'id' => 2,
            ],
        ]);

        $this->assertInstanceOf(CustomGeocodingResultClass::class, $collection->first());
        $this->assertEquals(1, $collection->first()->id);
    }

    /** @test */
    public function it_can_be_returned_as_an_array()
    {
        $results = [
            [
                "address_components" => [
                    [
                        "long_name"  => "New York",
                        "short_name" => "New York",
                        "types"      => [
                            "locality",
                            "political",
                        ],
                    ],
                    [
                        "long_name"  => "État de New York",
                        "short_name" => "NY",
                        "types"      => [
                            "administrative_area_level_1",
                            "political",
                        ],
                    ],
                    [
                        "long_name"  => "États-Unis",
                        "short_name" => "US",
                        "types"      => [
                            "country",
                            "political",
                        ],
                    ],
                ],
                "formatted_address"  => "New York, État de New York, États-Unis",
                "geometry"           => [
                    "bounds"        => [
                        "northeast" => [
                            "lat" => 40.9175771,
                            "lng" => -73.7002721,
                        ],
                        "southwest" => [
                            "lat" => 40.4773991,
                            "lng" => -74.2590899,
                        ],
                    ],
                    "location"      => [
                        "lat" => 40.7127753,
                        "lng" => -74.0059728,
                    ],
                    "location_type" => "APPROXIMATE",
                    "viewport"      => [
                        "northeast" => [
                            "lat" => 40.9175771,
                            "lng" => -73.7002721,
                        ],
                        "southwest" => [
                            "lat" => 40.4773991,
                            "lng" => -74.2590899,
                        ],
                    ],
                ],
                "place_id"           => "ChIJOwg_06VPwokRYv534QaPC8g",
                "types"              => [
                    "locality",
                    "political",
                ],
            ],
        ];

        $collection = new GeocodingResults($results);

        $this->assertEquals(
            $collection->first()->toArray(),
            [
                'country'       => 'États-Unis',
                'region'        => 'État de New York',
                'city'          => 'New York',
                'postal_code'   => '',
                'street_name'   => '',
                'street_number' => '',
                'latitude'      => 40.7127753,
                'longitude'     => -74.0059728,
            ],
        );
    }

    /** @test */
    public function the_array_returned_can_be_customized_in_a_custom_item_class()
    {
        config(
            ['google-geocoding.classes.item' => CustomGeocodingResultClass::class]
        );

        $results = [
            [
                "address_components" => [
                    [
                        "long_name"  => "New York",
                        "short_name" => "New York",
                        "types"      => [
                            "locality",
                            "political",
                        ],
                    ],
                    [
                        "long_name"  => "État de New York",
                        "short_name" => "NY",
                        "types"      => [
                            "administrative_area_level_1",
                            "political",
                        ],
                    ],
                    [
                        "long_name"  => "États-Unis",
                        "short_name" => "US",
                        "types"      => [
                            "country",
                            "political",
                        ],
                    ],
                ],
                "formatted_address"  => "New York, État de New York, États-Unis",
                "geometry"           => [
                    "bounds"        => [
                        "northeast" => [
                            "lat" => 40.9175771,
                            "lng" => -73.7002721,
                        ],
                        "southwest" => [
                            "lat" => 40.4773991,
                            "lng" => -74.2590899,
                        ],
                    ],
                    "location"      => [
                        "lat" => 40.7127753,
                        "lng" => -74.0059728,
                    ],
                    "location_type" => "APPROXIMATE",
                    "viewport"      => [
                        "northeast" => [
                            "lat" => 40.9175771,
                            "lng" => -73.7002721,
                        ],
                        "southwest" => [
                            "lat" => 40.4773991,
                            "lng" => -74.2590899,
                        ],
                    ],
                ],
                "place_id"           => "ChIJOwg_06VPwokRYv534QaPC8g",
                "types"              => [
                    "locality",
                    "political",
                ],
            ],
        ];

        $collection = new GeocodingResults($results);

        $this->assertEquals(
            $collection->first()->toArray(),
            [
                'latitude'  => 40.7127753,
                'longitude' => -74.0059728,
            ],
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'google-geocoding.classes' => [
                'collection' => GeocodingResults::class,
                'item'       => GeocodingResult::class,
            ],
        ]);
    }
}
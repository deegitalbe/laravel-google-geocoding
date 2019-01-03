<?php

namespace FHusquinet\GoogleGeocoding\Tests;

use Auth;
use Mockery;
use GuzzleHttp\Client;

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Support\Facades\Cache;
use FHusquinet\GoogleGeocoding\GeocodingResult;
use FHusquinet\GoogleGeocoding\GoogleGeocoding;
use FHusquinet\GoogleGeocoding\GeocodingResults;
use FHusquinet\GoogleGeocoding\Tests\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use FHusquinet\GoogleGeocoding\Tests\Models\DefaultSeoUser;
use FHusquinet\GoogleGeocoding\Tests\Helpers\GeocodingTestHelpers;
use FHusquinet\GoogleGeocoding\Tests\Classes\CustomGeocodingCollectionClass;

class GoogleGeocodingClassTest extends TestCase
{
    use GeocodingTestHelpers;

    public function setUp()
    {
        parent::setUp();

        $this->setKey('azerty');
        $this->setLanguage('en');
        $this->setCacheDuration(60 * 24 * 30);
        config()->set(['google-geocoding.classes' => [
            'collection' => \FHusquinet\GoogleGeocoding\GeocodingResults::class,
            'item' => \FHusquinet\GoogleGeocoding\GeocodingResult::class,
        ]]);
    }

    /** @test */
    public function it_will_throw_an_exception_if_no_key_is_set_in_the_config()
    {
        $this->setKey(null);

        $this->expectException(InvalidArgumentException::class);

        $geocoder = app('geocoder');
    }

    /** @test */
    public function it_will_contain_the_key_set_and_the_fallback_language_in_the_config_and_the_sensor_parameter_set_to_false_by_default()
    {
        $this->setKey('azerty');
        $this->setLanguage(null);

        $geocoder = app('geocoder');

        $this->assertEquals(
            [
                'key' => 'azerty',
                'sensor' => 'false',
                'language' => config('google-geocoding.fallback_language')
            ],
            $geocoder->urlParameters
        );
    }

    /** @test */
    public function it_will_contain_the_key_set_and_the_language_in_the_config_and_the_sensor_parameter_set_to_false_by_default()
    {
        $this->setKey('azerty');
        $this->setLanguage('fr');

        $geocoder = app('geocoder');

        $this->assertEquals(
            [
                'key' => 'azerty',
                'sensor' => 'false',
                'language' => 'fr'
            ],
            $geocoder->urlParameters
        );
    }

    /** @test */
    public function it_will_set_the_country_as_component_if_set_in_the_config()
    {
        config(['google-geocoding.country' => 'be']);

        $geocoder = app('geocoder');

        $this->assertEquals(
            [
                'country' => 'BE'
            ],
            $geocoder->components
        );
    }

    /** @test */
    public function it_can_build_the_url_using_the_parameters_defined_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = ['key' => 'value', 'two' => 'three'];

        $response = $geocoder->buildUrl();

        $this->assertEquals(
            'https://www.google.com?key=value&two=three',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_add_the_first_parameter_to_the_base_url_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $response = $geocoder->addParameter('key', 'value');

        $this->assertEquals(
            'https://www.google.com?key=value',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_return_its_url()
    {
        $geocoder = app('geocoder');
        $geocoder->url = 'my-url';

        $this->assertEquals(
            $geocoder->getUrl(),
            'my-url'
        );
    }
    
    /** @test */
    public function it_can_add_the_first_parameter_using_an_array_to_the_base_url_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $response = $geocoder->addParameter(['key' => 'value']);

        $this->assertEquals(
            'https://www.google.com?key=value',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_add_the_a_second_parameter_to_the_base_url_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $geocoder->addParameter('key', 'value');
        $response = $geocoder->addParameter('two', 'three');

        $this->assertEquals(
            'https://www.google.com?key=value&two=three',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_add_the_multiple_parameters_at_once_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $response = $geocoder->addParameters(['key' => 'value', 'two' => 'three']);

        $this->assertEquals(
            'https://www.google.com?key=value&two=three',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_add_the_first_component_using_an_array_to_the_base_url_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = [];

        $response = $geocoder->addComponent(['key' => 'value']);

        $this->assertEquals(
            'https://www.google.com?components=key:value',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_add_the_a_second_component_to_the_base_url_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = [];

        $geocoder->addComponent('key', 'value');
        $response = $geocoder->addComponent('two', 'three');

        $this->assertEquals(
            'https://www.google.com?components=key:value|two:three',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_add_the_multiple_components_at_once_and_return_itself()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = [];

        $response = $geocoder->addComponents(['key' => 'value', 'two' => 'three']);

        $this->assertEquals(
            'https://www.google.com?components=key:value|two:three',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_unset_an_url_parameter()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = [];

        $geocoder->addComponent('key', 'value');
        $geocoder->addComponent('two', 'three');
        $response = $geocoder->unsetComponent('key');

        $this->assertEquals(
            'https://www.google.com?components=two:three',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }
    
    /** @test */
    public function it_can_unset_a_component()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = [];

        $geocoder->addParameter('key', 'value');
        $geocoder->addParameter('two', 'three');
        $response = $geocoder->unsetParameter('key');

        $this->assertEquals(
            'https://www.google.com?two=three',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_build_an_url_with_just_the_url_parameters_set()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = ['key' => 'value', 'two' => 'three'];
        $geocoder->components = [];

        $geocoder->buildUrl();

        $this->assertEquals(
            'https://www.google.com?key=value&two=three',
            $geocoder->getUrl()
        );
    }

    /** @test */
    public function it_can_build_an_url_with_just_the_components_set()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = ['key' => 'value', 'two' => 'three'];

        $geocoder->buildUrl();

        $this->assertEquals(
            'https://www.google.com?components=key:value|two:three',
            $geocoder->getUrl()
        );
    }

    /** @test */
    public function it_can_build_an_url_with_both_the_url_parameters_and_the_components_set()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = ['key' => 'value', 'two' => 'three'];
        $geocoder->components = ['key' => 'value', 'two' => 'three'];

        $geocoder->buildUrl();

        $this->assertEquals(
            'https://www.google.com?key=value&two=three&components=key:value|two:three',
            $geocoder->getUrl()
        );
    }

    /** @test */
    public function it_can_set_the_language_of_the_query()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $response = $geocoder->language('fr');

        $this->assertEquals(
            'https://www.google.com?language=fr',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_unset_the_language_of_the_query()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $geocoder->language('fr');
        $response = $geocoder->removeLanguage();

        $this->assertEquals(
            'https://www.google.com',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_set_the_country_of_the_query()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $response = $geocoder->country('us');

        $this->assertEquals(
            'https://www.google.com?components=country:US',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_unset_the_country_of_the_query()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $geocoder->country('fr');
        $response = $geocoder->removeCountry();

        $this->assertEquals(
            'https://www.google.com',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_call_googles_api()
    {
        // Create a mock and queue one response.;
        $mock = new MockHandler([
            new Response(200)  
        ]);    
   
        $container = [];   
        $history = Middleware::history($container);    
   
        $stack = HandlerStack::create($mock);  
        // Add the history middleware to the handler stack.    
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $geocoder = $this->mock(GoogleGeocoding::class, true);
        $geocoder->client = $client;

        $geocoder->shouldReceive('getUrl')->once()->andReturn('my-url');

        $response = $geocoder->callApi();

        $url = (string) $container[0]['request']->getUri();
   
        $this->assertEquals(count($container), 1); 
        $this->assertEquals($response->getStatusCode(), 200);  
        $this->assertEquals($url, 'my-url');
    }

    /** @test */
    public function it_can_add_the_parameters_required_to_retrive_results_using_the_basic_geocoding_process()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $response = $geocoder->address('Laraville 5555 USA');

        $this->assertEquals(
            'https://www.google.com?address=' . urlencode('Laraville 5555 USA'),
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_add_the_parameters_required_to_retrive_results_using_the_reverse_geocoding_process()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];

        $response = $geocoder->coordinates(40.730610, -73.935242);

        $this->assertEquals(
            'https://www.google.com?latlng=40.73061,-73.935242',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function using_the_coordinates_method_will_always_unset_the_components_as_they_do_not_well_work_together()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = ['country' => 'BE', 'postal_code' => '4020'];

        $response = $geocoder->coordinates(40.730610, -73.935242);

        $this->assertEquals(
            'https://www.google.com?latlng=40.73061,-73.935242',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function using_the_coordinates_method_with_the_third_parameter_set_to_true_will_keep_the_components()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = ['country' => 'BE', 'postal_code' => '4020'];

        $response = $geocoder->coordinates(40.730610, -73.935242, true);

        $this->assertEquals(
            'https://www.google.com?latlng=40.73061,-73.935242&components=country:BE|postal_code:4020',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function using_the_coordinates_with_components_method_will_keep_the_components()
    {
        $geocoder = app('geocoder');
        $geocoder->baseUrl = 'https://www.google.com';
        $geocoder->urlParameters = [];
        $geocoder->components = ['country' => 'BE', 'postal_code' => '4020'];

        $response = $geocoder->coordinatesWithComponents(40.730610, -73.935242);

        $this->assertEquals(
            'https://www.google.com?latlng=40.73061,-73.935242&components=country:BE|postal_code:4020',
            $geocoder->url
        );

        $this->assertInstanceOf(GoogleGeocoding::class, $response);
    }

    /** @test */
    public function it_can_get_the_results()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $response = $this->createGuzzleResponse([
            "results" => [
              [
                "address_components" => [
                  [
                    "long_name" => "New York",
                    "short_name" => "New York",
                    "types" => [
                      "locality",
                      "political",
                    ],
                  ],
                  [
                    "long_name" => "État de New York",
                    "short_name" => "NY",
                    "types" => [
                      "administrative_area_level_1",
                      "political",
                    ],
                  ],
                  [
                    "long_name" => "États-Unis",
                    "short_name" => "US",
                    "types" => [
                      "country",
                      "political",
                    ],
                  ],
                ],
                "formatted_address" => "New York, État de New York, États-Unis",
                "geometry" => [
                  "bounds" => [
                    "northeast" => [
                      "lat" => 40.9175771,
                      "lng" => -73.7002721,
                    ],
                    "southwest" => [
                      "lat" => 40.4773991,
                      "lng" => -74.2590899,
                    ],
                  ],
                  "location" => [
                    "lat" => 40.7127753,
                    "lng" => -74.0059728,
                  ],
                  "location_type" => "APPROXIMATE",
                  "viewport" => [
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
                "place_id" => "ChIJOwg_06VPwokRYv534QaPC8g",
                "types" => [
                  "locality",
                  "political",
                ],
              ],
            ],
            "status" => "OK",
        ]);

        $geocoder
            ->shouldReceive('callApi')->once()->andReturn($response);

        $collection = $geocoder->get();

        $this->assertInstanceOf(GeocodingResults::class, $collection);
        $this->assertCount(1, $collection);
    }

    /** @test */
    public function it_can_get_no_results()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $response = $this->createGuzzleResponse([
            "results" => [],
            "status" => "ZERO_RESULTS",
        ]);

        $geocoder
            ->shouldReceive('callApi')->once()->andReturn($response);

        $result = $geocoder->get();

        $this->assertInstanceOf(GeocodingResults::class, $result);
        $this->assertCount(0, get_object_vars($result));
    }

    /** @test */
    public function it_will_return_null_in_case_an_error_is_found()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $response = $this->createGuzzleResponse([
            "results" => [],
            "status" => "INVALID_REQUEST",
        ]);

        $geocoder
            ->shouldReceive('callApi')->once()->andReturn($response);

        $results = $geocoder->get();

        $this->assertNull($results);
    }

    /** @test */
    public function it_can_get_its_cache_key()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $geocoder
            ->shouldReceive('getUrl')->once()->andReturn('my-url');

        $this->assertEquals(
            'google-geocoding-my-url',
            $geocoder->getCacheKey()
        );
    }

    /** @test */
    public function it_will_cache_the_results_for_the_duration_set_in_the_config()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $results = [
            [
            "address_components" => [
                [
                "long_name" => "New York",
                "short_name" => "New York",
                "types" => [
                    "locality",
                    "political",
                ],
                ],
                [
                "long_name" => "État de New York",
                "short_name" => "NY",
                "types" => [
                    "administrative_area_level_1",
                    "political",
                ],
                ],
                [
                "long_name" => "États-Unis",
                "short_name" => "US",
                "types" => [
                    "country",
                    "political",
                ],
                ],
            ],
            "formatted_address" => "New York, État de New York, États-Unis",
            "geometry" => [
                "bounds" => [
                "northeast" => [
                    "lat" => 40.9175771,
                    "lng" => -73.7002721,
                ],
                "southwest" => [
                    "lat" => 40.4773991,
                    "lng" => -74.2590899,
                ],
                ],
                "location" => [
                "lat" => 40.7127753,
                "lng" => -74.0059728,
                ],
                "location_type" => "APPROXIMATE",
                "viewport" => [
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
            "place_id" => "ChIJOwg_06VPwokRYv534QaPC8g",
            "types" => [
                "locality",
                "political",
            ],
          ]
        ];

        $response = $this->createGuzzleResponse([
            "results" => $results,
            "status" => "OK",
        ]);

        $geocoder
            ->shouldReceive('getCacheKey')->twice()->andReturn('cache-key')
            ->shouldReceive('callApi')->once()->andReturn($response);

        Cache::shouldReceive('has')
            ->once()
            ->with('cache-key')
            ->andReturn(false);

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $duration, $closure) {
                return $key == 'cache-key' && $duration == config('google-geocoding.cache_duration');
            })
            ->andReturn('cached-results');

        $this->assertEquals(
            $geocoder->get(),
            'cached-results'
        );
    }

    /** @test */
    public function it_will_returned_the_cached_results_if_they_exist()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $geocoder
            ->shouldReceive('getCacheKey')->twice()->andReturn('cache-key')
            ->shouldReceive('callApi')->never();

        Cache::shouldReceive('has')
            ->once()
            ->with('cache-key')
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->once()
            ->with('cache-key')
            ->andReturn('cached-results');

        Cache::shouldReceive('remember')
            ->never();

        $this->assertEquals(
            $geocoder->get(),
            'cached-results'
        );
    }

    /** @test */
    public function it_will_not_cache_the_results_in_case_an_error_is_found()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $response = $this->createGuzzleResponse([
            "results" => [],
            "status" => "INVALID_REQUEST",
        ]);

        $geocoder
            ->shouldReceive('getCacheKey')->once()->andReturn('cache-key')
            ->shouldReceive('callApi')->once()->andReturn($response);

        Cache::shouldReceive('has')
            ->once()
            ->with('cache-key')
            ->andReturn(false);

        Cache::shouldReceive('remember')
            ->never();

        $results = $geocoder->get();

        $this->assertNull($results);
    }

    /** @test */
    public function the_collection_class_returned_can_be_customized_through_the_config()
    {
        config(['google-geocoding.classes.collection' => \FHusquinet\GoogleGeocoding\Tests\Classes\CustomGeocodingCollectionClass::class]);

        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $response = $this->createGuzzleResponse([
            "results" => [
              [
                "address_components" => [
                  [
                    "long_name" => "New York",
                    "short_name" => "New York",
                    "types" => [
                      "locality",
                      "political",
                    ],
                  ],
                  [
                    "long_name" => "État de New York",
                    "short_name" => "NY",
                    "types" => [
                      "administrative_area_level_1",
                      "political",
                    ],
                  ],
                  [
                    "long_name" => "États-Unis",
                    "short_name" => "US",
                    "types" => [
                      "country",
                      "political",
                    ],
                  ],
                ],
                "formatted_address" => "New York, État de New York, États-Unis",
                "geometry" => [
                  "bounds" => [
                    "northeast" => [
                      "lat" => 40.9175771,
                      "lng" => -73.7002721,
                    ],
                    "southwest" => [
                      "lat" => 40.4773991,
                      "lng" => -74.2590899,
                    ],
                  ],
                  "location" => [
                    "lat" => 40.7127753,
                    "lng" => -74.0059728,
                  ],
                  "location_type" => "APPROXIMATE",
                  "viewport" => [
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
                "place_id" => "ChIJOwg_06VPwokRYv534QaPC8g",
                "types" => [
                  "locality",
                  "political",
                ],
              ],
            ],
            "status" => "OK",
        ]);

        $geocoder
            ->shouldReceive('callApi')->once()->andReturn($response);

        $collection = $geocoder->get();

        $this->assertInstanceOf(CustomGeocodingCollectionClass::class, $collection);
        $this->assertCount(1, $collection);
    }

    /** @test */
    public function it_can_return_the_first_result()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $geocoder->shouldReceive('get')->once()->andReturn(GeocodingResults::make([['id' => 1], ['id' => 2]]));

        $first = $geocoder->first();

        $this->assertInstanceOf(GeocodingResult::class, $first);
        $this->assertEquals(1, $first->id);
    }

    /** @test */
    public function it_will_return_null_if_there_are_no_results()
    {
        $geocoder = $this->mock(GoogleGeocoding::class, true);

        $geocoder->shouldReceive('get')->once()->andReturn(null);

        $this->assertNull(
            $geocoder->first()
        );
    }
}
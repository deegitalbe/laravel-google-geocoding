<?php

namespace FHusquinet\GoogleGeocoding\Tests;

use FHusquinet\GoogleGeocoding\GoogleGeocodingServiceProvider;
use FHusquinet\GoogleGeocoding\Models\CampaignActivity;
use FHusquinet\GoogleGeocoding\Tests\Models\Article;
use FHusquinet\GoogleGeocoding\Tests\Models\User;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }

    public function doNotMarkAsRisky()
    {
        $this->assertTrue(true);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            GoogleGeocodingServiceProvider::class,
        ];
    }

    private function checkRequirements(): void
    {
        collect($this->getAnnotations())->filter(function ($location) {
            return in_array('!Travis', array_get($location, 'requires', []));
        })->each(function ($location) {
            getenv('TRAVIS') && $this->markTestSkipped('Travis will not run this test.');
        });
    }
}
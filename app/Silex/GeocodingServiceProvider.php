<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Geocoding\CachedGeocodingService;
use CultuurNet\UDB3\Geocoding\DefaultGeocodingService;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle7\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GeocodingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app[GeocodingService::class] = $app->share(
            function (HybridContainerApplication $app) {
                $googleMapsApiKey = null;

                if (isset($app['geocoding_service.google_maps_api_key'])) {
                    $googleMapsApiKey = $app['geocoding_service.google_maps_api_key'];
                }

                $geocodingService = new DefaultGeocodingService(
                    new StatefulGeocoder(
                        new GoogleMaps(
                            new Client(),
                            null,
                            $googleMapsApiKey
                        )
                    ),
                    LoggerFactory::create($app->getLeagueContainer(), LoggerName::forService('geo-coordinates', 'google'))
                );

                return new CachedGeocodingService(
                    $geocodingService,
                    $app['cache']('geocoords')
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

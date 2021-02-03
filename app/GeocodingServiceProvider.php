<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Geocoding\CachedGeocodingService;
use CultuurNet\UDB3\Geocoding\DefaultGeocodingService;
use CultuurNet\UDB3\Geocoding\GeocodingServiceInterface;
use Geocoder\Geocoder;
use Geocoder\HttpAdapter\CurlHttpAdapter;
use Geocoder\Provider\GoogleMapsProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class GeocodingServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['geocoding_service'] = $app->share(
            function (Application $app) {
                $googleMapsApiKey = null;

                if (isset($app['geocoding_service.google_maps_api_key'])) {
                    $googleMapsApiKey = $app['geocoding_service.google_maps_api_key'];
                }

                return new DefaultGeocodingService(
                    new Geocoder(
                        new GoogleMapsProvider(
                            new CurlHttpAdapter(),
                            null,
                            null,
                            true,
                            $googleMapsApiKey
                        )
                    ),
                    $app['logger.command_bus']
                );
            }
        );

        $app->extend(
            'geocoding_service',
            function (GeocodingServiceInterface $geocodingService, Application $app) {
                return new CachedGeocodingService(
                    $geocodingService,
                    $app['cache']('geocoords')
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}

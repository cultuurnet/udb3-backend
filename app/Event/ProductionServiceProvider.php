<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\Productions\ProductionCommandHandler;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\Productions\SimilaritiesClient;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ProductionServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app[ProductionRepository::class] = $app->share(
            function ($app) {
                return new ProductionRepository($app['dbal_connection']);
            }
        );

        $app[SimilaritiesClient::class] = $app->share(
            function ($app) {
                return new SimilaritiesClient(
                    new \GuzzleHttp\Client(),
                    $app['config']['event_similarities_api']['base_url'],
                    $app['config']['event_similarities_api']['api_key']
                );
            }
        );

        $app[ProductionCommandHandler::class] = $app->share(
            function ($app) {
                return new ProductionCommandHandler($app[ProductionRepository::class], $app[SimilaritiesClient::class]);
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

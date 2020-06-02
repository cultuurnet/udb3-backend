<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ReplayServiceProvider implements ServiceProviderInterface
{

    /**
     * @inheritDoc
     */
    public function register(Application $app)
    {
        $app[EventStreamBuilder::class] = $app->share(
            function (Application $app) {
                return new EventStreamBuilder(
                    $app['dbal_connection'],
                    $app['eventstore_payload_serializer']
                );
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}

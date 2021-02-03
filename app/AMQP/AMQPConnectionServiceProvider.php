<?php

namespace CultuurNet\SilexAMQP;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AMQPConnectionServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['amqp.connection'] = $app->share(
            function (Application $app) {
                $connection = new AMQPStreamConnection(
                    $app['amqp.connection.host'],
                    $app['amqp.connection.port'],
                    $app['amqp.connection.user'],
                    $app['amqp.connection.password'],
                    $app['amqp.connection.vhost']
                );

                return $connection;
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

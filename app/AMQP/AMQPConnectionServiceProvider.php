<?php

namespace CultuurNet\UDB3\Silex\AMQP;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AMQPConnectionServiceProvider implements ServiceProviderInterface
{
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


    public function boot(Application $app)
    {
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Silex\AuditTrail\ErrorLogger;
use CultuurNet\UDB3\Silex\AuditTrail\RequestLogger;
use CultuurNet\UDB3\Silex\AuditTrail\ResponseLogger;
use CultuurNet\UDB3\Silex\AuditTrail\TokenStorageProcessor;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AuditTrailServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['audit_trail_logger'] = $app->share(
            function (Application $app) {
                $streamHandler = new StreamHandler(
                    __DIR__ . '/../log/audit_trail.log'
                );
                $streamHandler->setFormatter(
                    new JsonFormatter()
                );
                $auditTrail = new Logger('audit_trail');
                $auditTrail->pushHandler(
                    $streamHandler
                );
                $auditTrail->pushProcessor(
                    new WebProcessor()
                );
                $auditTrail->pushProcessor(
                    new TokenStorageProcessor(
                        $app['security.token_storage']
                    )
                );
                return $auditTrail;
            }
        );

        $app['request_logger'] = $app->share(
            function (Application $app) {
                return new RequestLogger(
                    $app['audit_trail_logger']
                );
            }
        );

        $app['response_logger'] = $app->share(
            function (Application $app) {
                return new ResponseLogger(
                    $app['audit_trail_logger']
                );
            }
        );

        $app['error_logger'] = $app->share(
            function (Application $app) {
                return new ErrorLogger(
                    $app['audit_trail_logger']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

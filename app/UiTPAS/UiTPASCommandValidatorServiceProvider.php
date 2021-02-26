<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPAS;

use CultuurNet\UDB3\Broadway\CommandHandling\Validation\CompositeCommandValidator;
use CultuurNet\UDB3\UiTPAS\Event\CommandHandling\Validation\EventHasTicketSalesCommandValidator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UiTPASCommandValidatorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['uitpas_event_has_ticket_sales_command_validator'] = $app->share(
            function (Application $app) {
                return new EventHasTicketSalesCommandValidator(
                    $app['uitpas'],
                    $app['logger.command_bus']
                );
            }
        );

        $app->extend(
            'event_command_validator',
            function (CompositeCommandValidator $commandValidator, Application $app) {
                $commandValidator->register($app['uitpas_event_has_ticket_sales_command_validator']);
                return $commandValidator;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

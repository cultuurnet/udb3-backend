<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Export;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\EventExport\EventExportCommandHandler;
use CultuurNet\UDB3\EventExport\EventExportService;
use CultuurNet\UDB3\EventExport\EventExportServiceInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Twig\GoogleMapUrlGenerator;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\CultureFeedEventInfoService;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion\EventOrganizerPromotionQueryFactory;
use CultuurNet\UDB3\EventExport\Notification\Swift\NotificationMailer;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use Psr\Log\LoggerAwareInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Twig_Environment;
use Twig_Extensions_Extension_Text;

class ExportServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['event_export_twig_environment'] = $app->share(
            function ($app) {
                $loader = new \Twig_Loader_Filesystem(
                    __DIR__ . '/../../src/EventExport/Format/HTML/templates'
                );

                $twig = new Twig_Environment($loader);

                $twig->addExtension(
                    new GoogleMapUrlGenerator($app['geocoding_service.google_maps_api_key'])
                );

                $twig->addExtension(new Twig_Extensions_Extension_Text());

                return $twig;
            }
        );

        $app['event_export_service'] = $app->share(
            function ($app) {
                return $this->createEventExportService(
                    $app,
                    $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS]
                );
            }
        );

        // Set up the event export command bus.
        $app['resque_command_bus_factory']('event_export');

        // Set up the event export command handler.
        $app['event_export_command_handler'] = $app->share(
            function (Application $app) {
                $eventInfoService = new CultureFeedEventInfoService(
                    $app['uitpas'],
                    new EventOrganizerPromotionQueryFactory($app['clock'])
                );

                $eventInfoService->setLogger(
                    LoggerFactory::create($app, LoggerName::forResqueWorker('event-export', 'uitpas'))
                );

                $eventExportCommandHandler = new EventExportCommandHandler(
                    $app['event_export_service'],
                    $app['config']['prince']['binary'],
                    $eventInfoService,
                    $app['calendar_summary_repository'],
                    $app['event_export_twig_environment']
                );
                $eventExportCommandHandler->setLogger(
                    LoggerFactory::create($app, LoggerName::forResqueWorker('event-export'))
                );
                return $eventExportCommandHandler;
            }
        );

        // Tie the event export command handler to the command bus.
        $app->extend(
            'event_export_command_bus_out',
            function (CommandBus $commandBus, Application $app) {
                $commandBus->subscribe($app['event_export_command_handler']);
                return $commandBus;
            }
        );
    }

    public function boot(Application $app)
    {
    }


    private function createEventExportService(
        Application $app,
        SearchServiceInterface $searchService
    ): EventExportServiceInterface {
        $logger = LoggerFactory::create($app, LoggerName::forResqueWorker('event-export'));
        if ($searchService instanceof LoggerAwareInterface) {
            $searchService = clone $searchService;
            $searchService->setLogger($logger);
        }

        return new EventExportService(
            $app['external_event_service'],
            $searchService,
            new Version4Generator(),
            realpath(__DIR__ . '/../../web/downloads'),
            new CallableIriGenerator(
                function ($fileName) use ($app) {
                    return $app['config']['url'] . '/downloads/' . $fileName;
                }
            ),
            new NotificationMailer(
                $app['mailer'],
                $app['event_export_notification_mail_factory']
            ),
            new ResultsGenerator(
                $searchService,
                null,
                (int) ($app['config']['export']['page_size'] ?? 100)
            ),
            $app['config']['export']['max_items']
        );
    }
}

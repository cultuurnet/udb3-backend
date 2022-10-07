<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Export;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryWithFormatterRepository;
use CultuurNet\UDB3\EventExport\EventExportCommandHandler;
use CultuurNet\UDB3\EventExport\EventExportService;
use CultuurNet\UDB3\EventExport\EventExportServiceInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Twig\GoogleMapUrlGenerator;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\CultureFeedEventInfoService;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion\EventOrganizerPromotionQueryFactory;
use CultuurNet\UDB3\EventExport\Notification\Swift\NotificationMailer;
use CultuurNet\UDB3\Http\Export\ExportEventsAsJsonLdRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsOoXmlRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsPdfRequestHandler;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifierFactory;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use Psr\Log\LoggerAwareInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Twig_Environment;
use Twig_Extensions_Extension_Text;

final class ExportServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['event_export_twig_environment'] = $app->share(
            function ($app) {
                $loader = new \Twig_Loader_Filesystem(
                    __DIR__ . '/../../../src/EventExport/Format/HTML/templates'
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
            function (HybridContainerApplication $app) {
                return $this->createEventExportService($app);
            }
        );

        // Set up the event export command handler.
        $app['event_export_command_handler'] = $app->share(
            function (HybridContainerApplication $app) {
                $eventInfoService = new CultureFeedEventInfoService(
                    $app['uitpas'],
                    new EventOrganizerPromotionQueryFactory($app['clock'])
                );

                $eventInfoService->setLogger(
                    LoggerFactory::create($app->getLeagueContainer(), LoggerName::forResqueWorker('event-export', 'uitpas'))
                );

                $eventExportCommandHandler = new EventExportCommandHandler(
                    $app['event_export_service'],
                    $app['config']['prince']['binary'],
                    new CalendarSummaryWithFormatterRepository($app['event_jsonld_repository']),
                    $eventInfoService,
                    $app['event_export_twig_environment']
                );
                $eventExportCommandHandler->setLogger(
                    LoggerFactory::create($app->getLeagueContainer(), LoggerName::forResqueWorker('event-export'))
                );
                return $eventExportCommandHandler;
            }
        );

        $app[ExportEventsAsJsonLdRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ExportEventsAsJsonLdRequestHandler(
                    $app['event_export_command_bus']
                );
            }
        );

        $app[ExportEventsAsOoXmlRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ExportEventsAsOoXmlRequestHandler(
                    $app['event_export_command_bus']
                );
            }
        );

        $app[ExportEventsAsPdfRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ExportEventsAsPdfRequestHandler(
                    $app['event_export_command_bus']
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }

    private function createEventExportService(HybridContainerApplication $app): EventExportServiceInterface {
        $searchService = $app[Sapi3SearchServiceProvider::SEARCH_SERVICE_EVENTS];

        $logger = LoggerFactory::create($app->getLeagueContainer(), LoggerName::forResqueWorker('event-export'));
        if ($searchService instanceof LoggerAwareInterface) {
            $searchService = clone $searchService;
            $searchService->setLogger($logger);
        }

        return new EventExportService(
            $app['event_jsonld_repository'],
            new ItemIdentifierFactory($app['config']['item_url_regex']),
            $searchService,
            new Version4Generator(),
            realpath(__DIR__ . '/../../../web/downloads'),
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

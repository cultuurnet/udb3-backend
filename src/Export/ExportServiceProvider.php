<?php

namespace CultuurNet\UDB3\Silex\Export;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\EventExport\EventExportCommandHandler;
use CultuurNet\UDB3\EventExport\EventExportServiceCollection;
use CultuurNet\UDB3\EventExport\EventExportServiceInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\CultureFeedEventInfoService;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion\EventOrganizerPromotionQueryFactory;
use CultuurNet\UDB3\EventExport\SapiVersion;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExportServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['event_export_service_collection'] = $app->share(
            function ($app) {
                $eventExportServiceCollection = new EventExportServiceCollection();

                $eventExportServiceCollection = $eventExportServiceCollection
                    ->withService(
                        SapiVersion::V2(),
                        $this->createEventExportService(
                            $app,
                            $app['search_service']
                        )
                    )
                    ->withService(
                        new SapiVersion(SapiVersion::V3),
                        $this->createEventExportService(
                            $app,
                            $app['sapi3_search_service']
                        )
                    );

                return $eventExportServiceCollection;
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

                $eventInfoService->setLogger($app['logger.uitpas']);

                return new EventExportCommandHandler(
                    $app['event_export_service_collection'],
                    $app['config']['prince']['binary'],
                    $eventInfoService,
                    $app['calendar_summary_repository']
                );
            }
        );

        // Tie the event export command handler to the command bus.
        $app->extend(
            'event_export_command_bus_out',
            function (CommandBusInterface $commandBus, Application $app) {
                $commandBus->subscribe($app['event_export_command_handler']);
                return $commandBus;
            }
        );
    }

    public function boot(Application $app)
    {
    }

    /**
     * @param Application $app
     * @param SearchServiceInterface $searchService
     * @return EventExportServiceInterface
     */
    private function createEventExportService(
        Application $app,
        SearchServiceInterface $searchService
    ): EventExportServiceInterface {
        /** @var ToggleManager $toggles */
        $toggles = $app['toggles'];

        if ($toggles->active('variations', $app['toggles.context'])) {
            $eventService =  $app['personal_variation_decorated_event_service'];
        } else {
            $eventService = $app['external_event_service'];
        }

        return new \CultuurNet\UDB3\EventExport\EventExportService(
            $eventService,
            $searchService,
            new \Broadway\UuidGenerator\Rfc4122\Version4Generator(),
            realpath(__DIR__ .  '/../../web/downloads'),
            new CallableIriGenerator(
                function ($fileName) use ($app) {
                    return $app['config']['url'] . '/downloads/' . $fileName;
                }
            ),
            new \CultuurNet\UDB3\EventExport\Notification\Swift\NotificationMailer(
                $app['mailer'],
                $app['event_export_notification_mail_factory']
            ),
            new \CultuurNet\UDB3\Search\ResultsGenerator($searchService)
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Export;

use CultuurNet\UDB3\EventExport\Notification\Swift\DefaultMessageFactory;
use CultuurNet\UDB3\EventExport\Notification\DefaultPlainTextBodyFactory;
use CultuurNet\UDB3\EventExport\Notification\DefaultHTMLBodyFactory;
use CultuurNet\UDB3\EventExport\Notification\LiteralSubjectFactory;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryWithFormatterRepository;
use CultuurNet\UDB3\EventExport\EventExportCommandHandler;
use CultuurNet\UDB3\EventExport\EventExportService;
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
use CultuurNet\UDB3\Search\EventsSapi3SearchService;
use Psr\Log\LoggerAwareInterface;
use Twig_Environment;
use Twig_Extensions_Extension_Text;

final class ExportServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_export_twig_environment',
            'event_export_service',
            'event_export_command_handler',
            ExportEventsAsJsonLdRequestHandler::class,
            ExportEventsAsOoXmlRequestHandler::class,
            ExportEventsAsPdfRequestHandler::class,
            'event_export_notification_mail_factory',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_export_twig_environment',
            function () use ($container): Twig_Environment {
                $loader = new \Twig_Loader_Filesystem(
                    __DIR__ . '/../../src/EventExport/Format/HTML/templates'
                );

                $twig = new Twig_Environment($loader);

                $twig->addExtension(new GoogleMapUrlGenerator($container->get('config')['google_maps_api_key']));
                $twig->addExtension(new Twig_Extensions_Extension_Text());

                return $twig;
            }
        );

        $container->addShared(
            'event_export_service',
            function () use ($container): EventExportService {
                $searchService = $container->get(EventsSapi3SearchService::class);

                $logger = LoggerFactory::create($container, LoggerName::forResqueWorker('event-export'));
                if ($searchService instanceof LoggerAwareInterface) {
                    $searchService = clone $searchService;
                    $searchService->setLogger($logger);
                }

                return new EventExportService(
                    $container->get('event_jsonld_repository'),
                    new ItemIdentifierFactory($container->get('config')['item_url_regex']),
                    $searchService,
                    new Version4Generator(),
                    realpath(__DIR__ . '/../../web/downloads'),
                    new CallableIriGenerator(
                        function ($fileName) use ($container) {
                            return $container->get('config')['url'] . '/downloads/' . $fileName;
                        }
                    ),
                    new NotificationMailer(
                        $container->get('mailer'),
                        $container->get('event_export_notification_mail_factory'),
                    ),
                    new ResultsGenerator(
                        $searchService,
                        null,
                        (int) ($container->get('config')['export']['page_size'] ?? 100)
                    ),
                    $container->get('config')['export']['max_items']
                );
            }
        );

        $container->addShared(
            'event_export_command_handler',
            function () use ($container): EventExportCommandHandler {
                $eventInfoService = new CultureFeedEventInfoService(
                    $container->get('uitpas'),
                    new EventOrganizerPromotionQueryFactory($container->get('clock'))
                );

                $eventInfoService->setLogger(
                    LoggerFactory::create($container, LoggerName::forResqueWorker('event-export', 'uitpas'))
                );

                $eventExportCommandHandler = new EventExportCommandHandler(
                    $container->get('event_export_service'),
                    $container->get('config')['prince']['binary'],
                    new CalendarSummaryWithFormatterRepository($container->get('event_jsonld_repository')),
                    $eventInfoService,
                    $container->get('event_export_twig_environment'),
                );
                $eventExportCommandHandler->setLogger(
                    LoggerFactory::create($container, LoggerName::forResqueWorker('event-export'))
                );

                return $eventExportCommandHandler;
            }
        );

        $container->addShared(
            ExportEventsAsJsonLdRequestHandler::class,
            function () use ($container): ExportEventsAsJsonLdRequestHandler {
                return new ExportEventsAsJsonLdRequestHandler($container->get('event_export_command_bus'));
            }
        );

        $container->addShared(
            ExportEventsAsOoXmlRequestHandler::class,
            function () use ($container): ExportEventsAsOoXmlRequestHandler {
                return new ExportEventsAsOoXmlRequestHandler($container->get('event_export_command_bus'));
            }
        );

        $container->addShared(
            ExportEventsAsPdfRequestHandler::class,
            function () use ($container): ExportEventsAsPdfRequestHandler {
                return new ExportEventsAsPdfRequestHandler($container->get('event_export_command_bus'));
            }
        );

        $container->addShared(
            'event_export_notification_mail_factory',
            fn () => new DefaultMessageFactory(
                new DefaultPlainTextBodyFactory(),
                new DefaultHTMLBodyFactory(),
                new LiteralSubjectFactory(
                    $container->get('config')['export']['mail']['subject']
                ),
                $container->get('config')['mail']['sender']['address'],
                $container->get('config')['mail']['sender']['name']
            )
        );
    }
}

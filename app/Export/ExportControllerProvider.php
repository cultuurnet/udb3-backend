<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Export;

use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLDJSONDeserializer;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXMLJSONDeserializer;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDFJSONDeserializer;
use CultuurNet\UDB3\Http\Export\ExportEventsAsJsonLdRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsOoXmlRequestHandler;
use CultuurNet\UDB3\Http\Export\ExportEventsAsPdfRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class ExportControllerProvider implements ControllerProviderInterface
{
    /**
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $app[ExportEventsAsJsonLdRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ExportEventsAsJsonLdRequestHandler(
                    new ExportEventsAsJsonLDJSONDeserializer(),
                    $app['event_export_command_bus']
                );
            }
        );

        $app[ExportEventsAsOoXmlRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ExportEventsAsOoXmlRequestHandler(
                    new ExportEventsAsOOXMLJSONDeserializer(),
                    $app['event_export_command_bus']
                );
            }
        );

        $app[ExportEventsAsPdfRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ExportEventsAsPdfRequestHandler(
                    new ExportEventsAsPDFJSONDeserializer(),
                    $app['event_export_command_bus']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/json/', ExportEventsAsJsonLdRequestHandler::class);
        $controllers->post('/ooxml/', ExportEventsAsOoXmlRequestHandler::class);
        $controllers->post('/pdf/', ExportEventsAsPdfRequestHandler::class);

        return $controllers;
    }
}

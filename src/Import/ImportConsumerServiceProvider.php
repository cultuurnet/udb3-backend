<?php

namespace CultuurNet\UDB3\Silex\Import;

use Broadway\CommandHandling\SimpleCommandBus;
use CultuurNet\BroadwayAMQP\CommandBusForwardingConsumer;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Import\Command\Deserializer\ImportEventDocumentDeserializer;
use CultuurNet\UDB3\Model\Import\Command\Deserializer\ImportOrganizerDocumentDeserializer;
use CultuurNet\UDB3\Model\Import\Command\Deserializer\ImportPlaceDocumentDeserializer;
use CultuurNet\UDB3\Model\Import\Command\HttpImportCommandHandler;
use CultuurNet\UDB3\Model\Import\Command\ImportEventDocument;
use CultuurNet\UDB3\Model\Import\Command\ImportOrganizerDocument;
use CultuurNet\UDB3\Model\Import\Command\ImportPlaceDocument;
use Guzzle\Http\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class ImportConsumerServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['imports_log_handler'] = $app->share(
            function () {
                return new StreamHandler(__DIR__ . '/../../log/import-commands.log');
            }
        );

        $app['imports_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('import-commands');
                $logger->pushHandler($app['imports_log_handler']);
                return $logger;
            }
        );

        $app['event_import_iri_generator'] = $app->share(
            function (Application $app) {
                $baseUrl = rtrim($app['config']['url'], '/');

                return new CallableIriGenerator(
                    function ($item) use ($baseUrl) {
                        return $baseUrl . '/imports/events/' . $item;
                    }
                );
            }
        );

        $app['event_import_command_handler'] = $app->share(
            function (Application $app) {
                return new HttpImportCommandHandler(
                    ImportEventDocument::class,
                    $app['event_import_iri_generator'],
                    new Client()
                );
            }
        );

        $app['place_import_iri_generator'] = $app->share(
            function (Application $app) {
                $baseUrl = rtrim($app['config']['url'], '/');

                return new CallableIriGenerator(
                    function ($item) use ($baseUrl) {
                        return $baseUrl . '/imports/places/' . $item;
                    }
                );
            }
        );

        $app['place_import_command_handler'] = $app->share(
            function (Application $app) {
                return new HttpImportCommandHandler(
                    ImportPlaceDocument::class,
                    $app['place_import_iri_generator'],
                    new Client()
                );
            }
        );

        $app['organizer_import_iri_generator'] = $app->share(
            function (Application $app) {
                $baseUrl = rtrim($app['config']['url'], '/');

                return new CallableIriGenerator(
                    function ($item) use ($baseUrl) {
                        return $baseUrl . '/imports/organizers/' . $item;
                    }
                );
            }
        );

        $app['organizer_import_command_handler'] = $app->share(
            function (Application $app) {
                return new HttpImportCommandHandler(
                    ImportOrganizerDocument::class,
                    $app['organizer_import_iri_generator'],
                    new Client()
                );
            }
        );

        $app['import_consumer_command_bus'] = $app->share(
            function (Application $app) {
                $commandBus = new SimpleCommandBus();
                $commandBus->subscribe($app['event_import_command_handler']);
                $commandBus->subscribe($app['place_import_command_handler']);
                $commandBus->subscribe($app['organizer_import_command_handler']);
                return $commandBus;
            }
        );

        $app['import_deserializer_locator'] = $app->share(
            function () {
                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.udb3-commands.import-event+json'
                    ),
                    new ImportEventDocumentDeserializer()
                );
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.udb3-commands.import-place+json'
                    ),
                    new ImportPlaceDocumentDeserializer()
                );
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.udb3-commands.import-organizer+json'
                    ),
                    new ImportOrganizerDocumentDeserializer()
                );
                return $deserializerLocator;
            }
        );

        $app['import_command_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                $consumer = new CommandBusForwardingConsumer(
                    $app['amqp.connection'],
                    $app['import_consumer_command_bus'],
                    $app['import_deserializer_locator'],
                    new StringLiteral($app['config']['amqp']['consumer_tag']),
                    new StringLiteral($app['config']['amqp']['consumers']['imports']['exchange']),
                    new StringLiteral($app['config']['amqp']['consumers']['imports']['queue'])
                );

                $consumer->setLogger($app['imports_logger']);

                return $consumer;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}

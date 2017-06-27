<?php

namespace CultuurNet\UDB3\Silex\Labels;

use Broadway\EventStore\DBALEventStore;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Label\CommandHandler;
use CultuurNet\UDB3\Label\ConstraintAwareLabelService;
use CultuurNet\UDB3\Label\Events\LabelNameUniqueConstraintService;
use CultuurNet\UDB3\Label\LabelEventRelationTypeResolver;
use CultuurNet\UDB3\Label\LabelRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\ItemVisibilityProjector;
use CultuurNet\UDB3\Label\ReadModels\JSON\Projector as JsonProjector;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\BroadcastingWriteRepositoryDecorator;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine\DBALReadRepository as JsonReadRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine\DBALWriteRepository as JsonWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine\SchemaConfigurator as JsonSchemaConfigurator;
use CultuurNet\UDB3\Label\ReadModels\Relations\Projector as RelationsProjector;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository as RelationsReadRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALWriteRepository as RelationsWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\SchemaConfigurator as RelationsSchemaConfigurator;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\LabelRolesWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\SchemaConfigurator as LabelRolesSchemaConfigurator;
use CultuurNet\UDB3\Label\ReadModels\Roles\LabelRolesProjector;
use CultuurNet\UDB3\Label\Services\ReadService;
use CultuurNet\UDB3\Label\Services\WriteService;
use CultuurNet\UDB3\Silex\DatabaseSchemaInstaller;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Symfony\Label\Query\QueryFactory;
use CultuurNet\UDB3\Symfony\Management\User\CultureFeedUserIdentification;
use CultuurNet\UDB3\UDB2\Label\RelatedUDB3LabelApplier;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class LabelServiceProvider implements ServiceProviderInterface
{
    const JSON_TABLE = 'labels_json';
    const RELATIONS_TABLE = 'labels_relations';
    const LABEL_ROLES_TABLE = 'label_roles';

    const JSON_REPOSITORY_SCHEMA = 'labels.json_repository_schema';
    const RELATIONS_REPOSITORY_SCHEMA = 'labels.relations_repository_schema';
    const LABEL_ROLES_REPOSITORY_SCHEMA = 'labels.labels_roles_repository_schema';
    const JSON_READ_REPOSITORY = 'labels.json_read_repository';
    const JSON_WRITE_REPOSITORY = 'labels.json_write_repository';
    const RELATIONS_READ_REPOSITORY = 'labels.relations_read_repository';
    const RELATIONS_WRITE_REPOSITORY = 'labels.relations_write_repository';
    const LABEL_ROLES_WRITE_REPOSITORY = 'labels.label_roles_write_repository';

    const READ_SERVICE = 'labels.read_service';
    const WRITE_SERVICE = 'labels.write_service';

    const UNIQUE_EVENT_STORE = 'labels.unique_event_store';
    const REPOSITORY = 'labels.repository';
    const COMMAND_HANDLER = 'labels.command_handler';

    const JSON_PROJECTOR = 'labels.json_projector';
    const RELATIONS_PROJECTOR = 'labels.relations_projector';
    const PLACE_LABEL_PROJECTOR = 'labels.place_label_projector';
    const EVENT_LABEL_PROJECTOR = 'labels.event_label_projector';
    const ORGANIZER_LABEL_PROJECTOR = 'labels.organizer_label_projector';
    const LABEL_ROLES_PROJECTOR = 'labels.label_roles_projector';

    const QUERY_FACTORY = 'label.query_factory';

    const LOGGER = 'labels.logger';

    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $this->setUpLogger($app);

        $this->setUpReadModels($app);

        $this->setUpServices($app);

        $this->setUpEventStore($app);

        $this->setUpCommandHandler($app);

        $this->setUpProjectors($app);

        $this->setUpQueryFactory($app);

        $app['related_udb3_labels_applier'] = $app->share(
            function (Application $app) {
                return new RelatedUDB3LabelApplier(
                    $app[self::RELATIONS_READ_REPOSITORY],
                    $app[self::JSON_READ_REPOSITORY],
                    $app[self::LOGGER]
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }

    /**
     * @param Application $app
     */
    private function setUpReadModels(Application $app)
    {
        $app[self::JSON_REPOSITORY_SCHEMA] = $app->share(
            function () {
                return new JsonSchemaConfigurator(
                    new StringLiteral(self::JSON_TABLE)
                );
            }
        );

        $app[self::RELATIONS_REPOSITORY_SCHEMA] = $app->share(
            function () {
                return new RelationsSchemaConfigurator(
                    new StringLiteral(self::RELATIONS_TABLE)
                );
            }
        );

        $app[self::LABEL_ROLES_REPOSITORY_SCHEMA] = $app->share(
            function () {
                return new LabelRolesSchemaConfigurator(
                    new StringLiteral(self::LABEL_ROLES_TABLE)
                );
            }
        );

        $app[self::JSON_READ_REPOSITORY] = $app->share(
            function (Application $app) {
                return new JsonReadRepository(
                    $app['dbal_connection'],
                    new StringLiteral(self::JSON_TABLE),
                    new StringLiteral(self::LABEL_ROLES_TABLE),
                    new StringLiteral(UserPermissionsServiceProvider::USER_ROLES_TABLE)
                );
            }
        );

        $app[self::JSON_WRITE_REPOSITORY] = $app->share(
            function (Application $app) {
                return new BroadcastingWriteRepositoryDecorator(
                    new JsonWriteRepository(
                        $app['dbal_connection'],
                        new StringLiteral(self::JSON_TABLE)
                    ),
                    $app['event_bus']
                );
            }
        );

        $app[self::RELATIONS_WRITE_REPOSITORY] = $app->share(
            function (Application $app) {
                return new RelationsWriteRepository(
                    $app['dbal_connection'],
                    new StringLiteral(self::RELATIONS_TABLE)
                );
            }
        );

        $app[self::RELATIONS_READ_REPOSITORY] = $app->share(
            function (Application $app) {
                return new RelationsReadRepository(
                    $app['dbal_connection'],
                    new StringLiteral(self::RELATIONS_TABLE)
                );
            }
        );

        $app[self::LABEL_ROLES_WRITE_REPOSITORY] = $app->share(
            function (Application $app) {
                return new LabelRolesWriteRepository(
                    $app['dbal_connection'],
                    new StringLiteral(self::LABEL_ROLES_TABLE)
                );
            }
        );

        $app['database.installer'] = $app->extend(
            'database.installer',
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer->addSchemaConfigurator(
                    $app[LabelServiceProvider::JSON_REPOSITORY_SCHEMA]
                );
                $installer->addSchemaConfigurator(
                    $app[LabelServiceProvider::RELATIONS_REPOSITORY_SCHEMA]
                );
                $installer->addSchemaConfigurator(
                    $app[LabelServiceProvider::LABEL_ROLES_REPOSITORY_SCHEMA]
                );
                return $installer;
            }
        );
    }

    /**
     * @param Application $app
     */
    private function setUpServices(Application $app)
    {
        $app['labels.constraint_aware_service'] = $app->share(
            function (Application $app) {
                return new ConstraintAwareLabelService(
                    $app[self::REPOSITORY],
                    new Version4Generator()
                );
            }
        );

        $app[self::READ_SERVICE] = $app->share(
            function (Application $app) {
                return new ReadService(
                    $app[self::JSON_READ_REPOSITORY]
                );
            }
        );

        $app[self::WRITE_SERVICE] = $app->share(
            function (Application $app) {
                return new WriteService(
                    $app['event_command_bus'],
                    new Version4Generator()
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    private function setUpEventStore(Application $app)
    {
        $app[self::UNIQUE_EVENT_STORE] = $app->share(
            function (Application $app) {
                $eventStore = new DBALEventStore(
                    $app['dbal_connection'],
                    $app['eventstore_payload_serializer'],
                    new SimpleInterfaceSerializer(),
                    'labels'
                );

                return new UniqueDBALEventStoreDecorator(
                    $eventStore,
                    $app['dbal_connection'],
                    new StringLiteral('labels_unique'),
                    new LabelNameUniqueConstraintService()
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    private function setUpCommandHandler(Application $app)
    {
        $app[self::REPOSITORY] = $app->share(
            function (Application $app) {
                return new LabelRepository(
                    $app[self::UNIQUE_EVENT_STORE],
                    $app['event_bus'],
                    [$app['event_stream_metadata_enricher']]
                );
            }
        );

        $app[self::COMMAND_HANDLER] = $app->share(
            function (Application $app) {
                return new CommandHandler(
                    $app[self::REPOSITORY],
                    new Version4Generator()
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    private function setUpProjectors(Application $app)
    {
        $app[self::JSON_PROJECTOR] = $app->share(
            function (Application $app) {
                return new JsonProjector(
                    $app[self::JSON_WRITE_REPOSITORY],
                    $app[self::JSON_READ_REPOSITORY]
                );
            }
        );

        $app[self::RELATIONS_PROJECTOR] = $app->share(
            function (Application $app) {
                return new RelationsProjector(
                    $app[self::RELATIONS_WRITE_REPOSITORY],
                    $app[self::RELATIONS_READ_REPOSITORY],
                    new LabelEventRelationTypeResolver()
                );
            }
        );

        $app[self::LABEL_ROLES_PROJECTOR] = $app->share(
            function (Application $app) {
                return new LabelRolesProjector(
                    $app[self::LABEL_ROLES_WRITE_REPOSITORY]
                );
            }
        );

        $app[self::PLACE_LABEL_PROJECTOR] = $app->share(
            function (Application $app) {
                $projector = new ItemVisibilityProjector(
                    $app['place_jsonld_repository'],
                    $app[self::RELATIONS_READ_REPOSITORY]
                );

                $projector->setLogger($app[self::LOGGER]);

                return $projector;
            }
        );

        $app[self::EVENT_LABEL_PROJECTOR] = $app->share(
            function (Application $app) {
                $projector =  new ItemVisibilityProjector(
                    $app['event_jsonld_repository'],
                    $app[self::RELATIONS_READ_REPOSITORY]
                );

                $projector->setLogger($app[self::LOGGER]);

                return $projector;
            }
        );

        $app[self::ORGANIZER_LABEL_PROJECTOR] = $app->share(
            function (Application $app) {
                $projector =  new ItemVisibilityProjector(
                    $app['organizer_jsonld_repository'],
                    $app[self::RELATIONS_READ_REPOSITORY]
                );

                $projector->setLogger($app[self::LOGGER]);

                return $projector;
            }
        );
    }

    private function setUpQueryFactory(Application $app)
    {
        $app[self::QUERY_FACTORY] = $app->share(
            function (Application $app) {
                $userIdentification = new CultureFeedUserIdentification(
                    $app['current_user'],
                    $app['config']['user_permissions']
                );

                return new QueryFactory($userIdentification);
            }
        );
    }

    /**
     * @param Application $app
     */
    private function setUpLogger(Application $app)
    {
        $app[self::LOGGER] = $app->share(
            function () {
                $logger = new Logger('labels');
                $logger->pushHandler(new StreamHandler('php://stdout'));
                $logger->pushHandler(new StreamHandler(__DIR__ . '/../../log/labels.log'));

                return $logger;
            }
        );
    }
}

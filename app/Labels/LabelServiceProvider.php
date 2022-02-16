<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Labels;

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
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\GodUserReadRepositoryDecorator;
use CultuurNet\UDB3\Label\ReadModels\Relations\Projector as RelationsProjector;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository as RelationsReadRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALWriteRepository as RelationsWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\LabelRolesWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\Roles\LabelRolesProjector;
use CultuurNet\UDB3\Label\Services\ReadService;
use CultuurNet\UDB3\Label\Services\WriteService;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\RelationshipModelLockedLabelRepository;
use CultuurNet\UDB3\Silex\AggregateType;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Http\Label\Query\QueryFactory;
use CultuurNet\UDB3\UDB2\Label\RelatedUDB3LabelApplier;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\StringLiteral;

class LabelServiceProvider implements ServiceProviderInterface
{
    public const JSON_TABLE = 'labels_json';
    public const RELATIONS_TABLE = 'labels_relations';
    public const LABEL_ROLES_TABLE = 'label_roles';

    public const JSON_READ_REPOSITORY = 'labels.json_read_repository';
    public const JSON_WRITE_REPOSITORY = 'labels.json_write_repository';
    public const RELATIONS_READ_REPOSITORY = 'labels.relations_read_repository';
    public const RELATIONS_WRITE_REPOSITORY = 'labels.relations_write_repository';
    public const LABEL_ROLES_WRITE_REPOSITORY = 'labels.label_roles_write_repository';

    public const READ_SERVICE = 'labels.read_service';
    public const WRITE_SERVICE = 'labels.write_service';

    public const UNIQUE_EVENT_STORE = 'labels.unique_event_store';
    public const REPOSITORY = 'labels.repository';
    public const COMMAND_HANDLER = 'labels.command_handler';

    public const JSON_PROJECTOR = 'labels.json_projector';
    public const RELATIONS_PROJECTOR = 'labels.relations_projector';
    public const PLACE_LABEL_PROJECTOR = 'labels.place_label_projector';
    public const EVENT_LABEL_PROJECTOR = 'labels.event_label_projector';
    public const ORGANIZER_LABEL_PROJECTOR = 'labels.organizer_label_projector';
    public const LABEL_ROLES_PROJECTOR = 'labels.label_roles_projector';

    public const QUERY_FACTORY = 'label.query_factory';

    public const LOGGER = 'labels.logger';

    public function register(Application $app): void
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

    public function boot(Application $app): void
    {
    }

    private function setUpReadModels(Application $app): void
    {
        $app[self::JSON_READ_REPOSITORY] = $app->share(
            function (Application $app) {
                return new GodUserReadRepositoryDecorator(
                    new JsonReadRepository(
                        $app['dbal_connection'],
                        new StringLiteral(self::JSON_TABLE),
                        new StringLiteral(self::LABEL_ROLES_TABLE),
                        new StringLiteral(UserPermissionsServiceProvider::USER_ROLES_TABLE)
                    ),
                    $app['config']['user_permissions']['allow_all']
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

        $app['labels.labels_locked_for_import_repository'] = $app->share(
            function (Application $app) {
                return new RelationshipModelLockedLabelRepository(
                    $app[self::RELATIONS_READ_REPOSITORY]
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
    }

    private function setUpServices(Application $app): void
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

    private function setUpEventStore(Application $app): void
    {
        $app[self::UNIQUE_EVENT_STORE] = $app->share(
            function (Application $app) {
                $eventStore = $app['event_store_factory'](
                    AggregateType::label()
                );

                return new UniqueDBALEventStoreDecorator(
                    $eventStore,
                    $app['dbal_connection'],
                    'labels_unique',
                    new LabelNameUniqueConstraintService()
                );
            }
        );
    }

    private function setUpCommandHandler(Application $app): void
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
                    $app[self::REPOSITORY]
                );
            }
        );
    }

    private function setUpProjectors(Application $app): void
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

    private function setUpQueryFactory(Application $app): void
    {
        $app[self::QUERY_FACTORY] = $app->share(
            function (Application $app) {
                return new QueryFactory($app['current_user_is_god_user'] ? null : $app['current_user_id']);
            }
        );
    }

    private function setUpLogger(Application $app): void
    {
        $app[self::LOGGER] = $app->share(
            function (Application $app) {
                return LoggerFactory::create(
                    $app,
                    LoggerName::forService('labels'),
                    [new StreamHandler('php://stdout')]
                );
            }
        );
    }
}

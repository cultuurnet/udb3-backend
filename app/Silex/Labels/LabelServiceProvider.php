<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Labels;

use Broadway\EventHandling\EventBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Http\Label\CreateLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\GetLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\PatchLabelRequestHandler;
use CultuurNet\UDB3\Http\Label\Query\QueryFactory;
use CultuurNet\UDB3\Http\Label\SearchLabelsRequestHandler;
use CultuurNet\UDB3\Label\CommandHandler;
use CultuurNet\UDB3\Label\ConstraintAwareLabelService;
use CultuurNet\UDB3\Label\Events\LabelNameUniqueConstraintService;
use CultuurNet\UDB3\Label\LabelEventRelationTypeResolver;
use CultuurNet\UDB3\Label\LabelRepository;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\LabelVisibilityOnRelatedDocumentsProjector;
use CultuurNet\UDB3\Label\ReadModels\JSON\Projector as JsonProjector;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\BroadcastingWriteRepositoryDecorator;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine\DBALReadRepository as JsonReadRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine\DBALWriteRepository as JsonWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\GodUserReadRepositoryDecorator;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\InMemoryExcludedLabelsRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Projector as RelationsProjector;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository as RelationsReadRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALWriteRepository as RelationsWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine\LabelRolesWriteRepository;
use CultuurNet\UDB3\Label\ReadModels\Roles\LabelRolesProjector;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\User\CurrentUser;
use League\Container\Container;
use Monolog\Handler\StreamHandler;
use Silex\Application;

final class LabelServiceProvider extends AbstractServiceProvider
{
    public const JSON_TABLE = 'labels_json';
    public const RELATIONS_TABLE = 'labels_relations';
    public const LABEL_ROLES_TABLE = 'label_roles';

    public const JSON_READ_REPOSITORY = 'labels.json_read_repository';
    public const JSON_WRITE_REPOSITORY = 'labels.json_write_repository';
    public const RELATIONS_READ_REPOSITORY = 'labels.relations_read_repository';
    public const RELATIONS_WRITE_REPOSITORY = 'labels.relations_write_repository';
    public const LABEL_ROLES_WRITE_REPOSITORY = 'labels.label_roles_write_repository';

    public const WRITE_SERVICE = 'labels.write_service';

    public const UNIQUE_EVENT_STORE = 'labels.unique_event_store';
    public const REPOSITORY = 'labels.repository';
    public const COMMAND_HANDLER = 'labels.command_handler';

    public const JSON_PROJECTOR = 'labels.json_projector';
    public const RELATIONS_PROJECTOR = 'labels.relations_projector';
    public const LABEL_ROLES_PROJECTOR = 'labels.label_roles_projector';

    public const QUERY_FACTORY = 'label.query_factory';

    public const LOGGER = 'labels.logger';


    protected function getProvidedServiceNames(): array
    {
        return [
            CreateLabelRequestHandler::class,
            PatchLabelRequestHandler::class,
            GetLabelRequestHandler::class,
            SearchLabelsRequestHandler::class
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $this->setUpLogger($container);

        $this->setUpReadModels($container);

        $this->setUpServices($container);

        $this->setUpEventStore($container);

        $this->setUpCommandHandler($container);

        $this->setUpProjectors($container);

        $this->setUpQueryFactory($container);

        $container->addShared(
            CreateLabelRequestHandler::class,
            function() use ($container): CreateLabelRequestHandler {
                return new CreateLabelRequestHandler(
                    $container->get('event_command_bus'),
                    new Version4Generator()
                );
            }
        );

        $container->addShared(
            PatchLabelRequestHandler::class,
            function() use ($container): PatchLabelRequestHandler {
                return new PatchLabelRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            GetLabelRequestHandler::class,
            function() use ($container): GetLabelRequestHandler {
                return new GetLabelRequestHandler($container->get(LabelServiceProvider::JSON_READ_REPOSITORY));
            }
        );

        $container->addShared(
            SearchLabelsRequestHandler::class,
            function() use ($container): SearchLabelsRequestHandler {
                return new SearchLabelsRequestHandler(
                    $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
                        $container->get(LabelServiceProvider::QUERY_FACTORY)
                );
            }
        );
    }

    private function setUpReadModels(Container $container): void
    {
        $container->addShared(
            self::JSON_READ_REPOSITORY,
            function() use ($container): ReadRepositoryInterface {
                $labels = file_exists(__DIR__ . '/../../../config.excluded_labels.php') ? require __DIR__ . '/../../../config.excluded_labels.php' : [];

                return new GodUserReadRepositoryDecorator(
                    new JsonReadRepository(
                        $container->get('dbal_connection'),
                        new StringLiteral(self::JSON_TABLE),
                        new StringLiteral(self::LABEL_ROLES_TABLE),
                        new StringLiteral(UserPermissionsServiceProvider::USER_ROLES_TABLE),
                        new InMemoryExcludedLabelsRepository($labels ?? [])
                    ),
                    $container->get('config')['user_permissions']['allow_all']
                );
            }
        );

        $container->addShared(
            self::JSON_WRITE_REPOSITORY,
            function() use ($container): WriteRepositoryInterface {
                return new BroadcastingWriteRepositoryDecorator(
                    new JsonWriteRepository(
                        $container->get('dbal_connection'),
                        new StringLiteral(self::JSON_TABLE)
                    ),
                    $container->get(EventBus::class)
                );
            }
        );

        $container->addShared(
            self::RELATIONS_WRITE_REPOSITORY,
            function() use ($container): RelationsWriteRepository {
                return new RelationsWriteRepository(
                    $container->get('dbal_connection'),
                    new StringLiteral(self::RELATIONS_TABLE)
                );
            }
        );

        $container->addShared(
            self::RELATIONS_READ_REPOSITORY,
            function() use ($container): RelationsReadRepository {
                return new RelationsReadRepository(
                    $container->get('dbal_connection'),
                    new StringLiteral(self::RELATIONS_TABLE)
                );
            }
        );

        $container->addShared(
            self::LABEL_ROLES_WRITE_REPOSITORY,
            function() use ($container): LabelRolesWriteRepository {
                return new LabelRolesWriteRepository(
                    $container->get('dbal_connection'),
                    new StringLiteral(self::LABEL_ROLES_TABLE)
                );
            }
        );
    }

    private function setUpServices(Container $container): void
    {
        $container->addShared(
            'labels.constraint_aware_service',
            function () use ($container): LabelServiceInterface {
                return new ConstraintAwareLabelService(
                    $container->get(self::REPOSITORY),
                    new Version4Generator()
                );
            }
        );

        $container->addShared(
            CdbXMLToJsonLDLabelImporter::class,
            function () use ($container): CdbXMLToJsonLDLabelImporter {
                return new CdbXMLToJsonLDLabelImporter($container->get(self::JSON_READ_REPOSITORY));
            }
        );
    }

    private function setUpEventStore(Container $container): void
    {
        $container->addShared(
            self::UNIQUE_EVENT_STORE,
            function() use ($container){
                $eventStore = $container->get('event_store_factory')(
                    AggregateType::label()
                );

                return new UniqueDBALEventStoreDecorator(
                    $eventStore,
                    $container->get('dbal_connection'),
                    'labels_unique',
                    new LabelNameUniqueConstraintService()
                );
            }
        );
    }

    private function setUpCommandHandler(Container $container): void
    {
        $container->addShared(
            self::REPOSITORY,
            function() use ($container): LabelRepository {
                return new LabelRepository(
                    $container->get(self::UNIQUE_EVENT_STORE),
                    $container->get(EventBus::class),
                    [$container->get('event_stream_metadata_enricher')]
                );
            }
        );

        $container->addShared(
            self::COMMAND_HANDLER,
            function() use ($container): CommandHandler {
                return new CommandHandler(
                    $container->get(self::REPOSITORY)
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

        $app[LabelVisibilityOnRelatedDocumentsProjector::class] = $app->share(
            function (Application $app) {
                $projector = (new LabelVisibilityOnRelatedDocumentsProjector($app[self::RELATIONS_READ_REPOSITORY]))
                    ->withDocumentRepositoryForRelationType(
                        RelationType::event(),
                        $app['event_jsonld_repository']
                    )
                    ->withDocumentRepositoryForRelationType(
                        RelationType::place(),
                        $app['place_jsonld_repository']
                    )
                    ->withDocumentRepositoryForRelationType(
                        RelationType::organizer(),
                        $app['organizer_jsonld_repository']
                    );

                $projector->setLogger($app[self::LOGGER]);

                return $projector;
            }
        );
    }

    private function setUpQueryFactory(Container $container): void
    {
        $container->addShared(
            self::QUERY_FACTORY,
            function() use ($container): QueryFactory {
                /** @var CurrentUser $currentUser */
                $currentUser = $container->get(CurrentUser::class);
                return new QueryFactory($currentUser->isGodUser() ? null : $currentUser->getId());
            }
        );
    }

    private function setUpLogger(Container $container): void
    {
        $container->addShared(
            self::LOGGER,
            function() use ($container) {
                return LoggerFactory::create(
                    $container,
                    LoggerName::forService('labels'),
                    [new StreamHandler('php://stdout')]
                );
            }
        );
    }
}

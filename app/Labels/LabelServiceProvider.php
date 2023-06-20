<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Labels;

use Broadway\EventHandling\EventBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
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
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\AppConfigReadRepositoryDecorator;
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
use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\User\CurrentUser;
use League\Container\Container;
use Monolog\Handler\StreamHandler;

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
            SearchLabelsRequestHandler::class,
            self::JSON_READ_REPOSITORY,
            self::JSON_WRITE_REPOSITORY,
            self::RELATIONS_WRITE_REPOSITORY,
            self::RELATIONS_READ_REPOSITORY,
            self::LABEL_ROLES_WRITE_REPOSITORY,
            'labels.constraint_aware_service',
            CdbXMLToJsonLDLabelImporter::class,
            self::UNIQUE_EVENT_STORE,
            self::REPOSITORY,
            self::COMMAND_HANDLER,
            self::JSON_PROJECTOR,
            self::RELATIONS_PROJECTOR,
            self::LABEL_ROLES_PROJECTOR,
            LabelVisibilityOnRelatedDocumentsProjector::class,
            self::QUERY_FACTORY,
            self::LOGGER,
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
            function () use ($container): CreateLabelRequestHandler {
                return new CreateLabelRequestHandler(
                    $container->get('event_command_bus'),
                    new Version4Generator()
                );
            }
        );

        $container->addShared(
            PatchLabelRequestHandler::class,
            function () use ($container): PatchLabelRequestHandler {
                return new PatchLabelRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            GetLabelRequestHandler::class,
            function () use ($container): GetLabelRequestHandler {
                return new GetLabelRequestHandler($container->get(LabelServiceProvider::JSON_READ_REPOSITORY));
            }
        );

        $container->addShared(
            SearchLabelsRequestHandler::class,
            function () use ($container): SearchLabelsRequestHandler {
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
            function () use ($container): ReadRepositoryInterface {
                $labels = file_exists(__DIR__ . '/../../config.excluded_labels.php') ? require __DIR__ . '/../../config.excluded_labels.php' : [];

                return new AppConfigReadRepositoryDecorator(
                    new GodUserReadRepositoryDecorator(
                        new JsonReadRepository(
                            $container->get('dbal_connection'),
                            self::JSON_TABLE,
                            self::LABEL_ROLES_TABLE,
                            UserPermissionsServiceProvider::USER_ROLES_TABLE,
                            new InMemoryExcludedLabelsRepository($labels ?? [])
                        ),
                        $container->get('config')['user_permissions']['allow_all']
                    ),
                    $container->get('config')['client_permissions']
                );
            }
        );

        $container->addShared(
            self::JSON_WRITE_REPOSITORY,
            function () use ($container): WriteRepositoryInterface {
                return new BroadcastingWriteRepositoryDecorator(
                    new JsonWriteRepository(
                        $container->get('dbal_connection'),
                        self::JSON_TABLE
                    ),
                    $container->get(EventBus::class)
                );
            }
        );

        $container->addShared(
            self::RELATIONS_WRITE_REPOSITORY,
            function () use ($container): RelationsWriteRepository {
                return new RelationsWriteRepository(
                    $container->get('dbal_connection'),
                    new StringLiteral(self::RELATIONS_TABLE)
                );
            }
        );

        $container->addShared(
            self::RELATIONS_READ_REPOSITORY,
            function () use ($container): RelationsReadRepository {
                return new RelationsReadRepository(
                    $container->get('dbal_connection'),
                    new StringLiteral(self::RELATIONS_TABLE)
                );
            }
        );

        $container->addShared(
            self::LABEL_ROLES_WRITE_REPOSITORY,
            function () use ($container): LabelRolesWriteRepository {
                return new LabelRolesWriteRepository(
                    $container->get('dbal_connection'),
                    self::LABEL_ROLES_TABLE
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
            function () use ($container) {
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
            function () use ($container): LabelRepository {
                return new LabelRepository(
                    $container->get(self::UNIQUE_EVENT_STORE),
                    $container->get(EventBus::class),
                    [$container->get('event_stream_metadata_enricher')]
                );
            }
        );

        $container->addShared(
            self::COMMAND_HANDLER,
            function () use ($container): CommandHandler {
                return new CommandHandler(
                    $container->get(self::REPOSITORY)
                );
            }
        );
    }

    private function setUpProjectors(Container $container): void
    {
        $container->addShared(
            self::JSON_PROJECTOR,
            function () use ($container): JsonProjector {
                return new JsonProjector(
                    $container->get(self::JSON_WRITE_REPOSITORY),
                    $container->get(self::JSON_READ_REPOSITORY)
                );
            }
        );

        $container->addShared(
            self::RELATIONS_PROJECTOR,
            function () use ($container): RelationsProjector {
                return new RelationsProjector(
                    $container->get(self::RELATIONS_WRITE_REPOSITORY),
                    $container->get(self::RELATIONS_READ_REPOSITORY),
                    new LabelEventRelationTypeResolver()
                );
            }
        );

        $container->addShared(
            self::LABEL_ROLES_PROJECTOR,
            function () use ($container): LabelRolesProjector {
                return new LabelRolesProjector(
                    $container->get(self::LABEL_ROLES_WRITE_REPOSITORY)
                );
            }
        );

        $container->addShared(
            LabelVisibilityOnRelatedDocumentsProjector::class,
            function () use ($container) {
                $projector = (new LabelVisibilityOnRelatedDocumentsProjector($container->get(self::RELATIONS_READ_REPOSITORY)))
                    ->withDocumentRepositoryForRelationType(
                        RelationType::event(),
                        $container->get('event_jsonld_repository')
                    )
                    ->withDocumentRepositoryForRelationType(
                        RelationType::place(),
                        $container->get('place_jsonld_repository')
                    )
                    ->withDocumentRepositoryForRelationType(
                        RelationType::organizer(),
                        $container->get('organizer_jsonld_repository')
                    );

                $projector->setLogger($container->get(self::LOGGER));

                return $projector;
            }
        );
    }

    private function setUpQueryFactory(Container $container): void
    {
        $container->addShared(
            self::QUERY_FACTORY,
            function () use ($container): QueryFactory {
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
            function () use ($container) {
                return LoggerFactory::create(
                    $container,
                    LoggerName::forService('labels'),
                    [new StreamHandler('php://stdout')]
                );
            }
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\ReadModel\History\EventPlaceHistoryProjector;
use CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALEventPlaceHistoryRepository;
use CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALEventRelationsRepository;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventPlaceHistoryRepository;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsProjector;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\JSONLDMainLanguageQuery;

final class EventReadServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            EventRelationsProjector::class,
            EventRelationsRepository::class,
            EventPlaceHistoryProjector::class,
            EventPlaceHistoryRepository::class,
            'event_main_language_query',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            EventRelationsProjector::class,
            function () use ($container): EventRelationsProjector {
                return new EventRelationsProjector(
                    $container->get(EventRelationsRepository::class),
                    $container->get('udb2_event_cdbid_extractor'),
                );
            }
        );

        $container->addShared(
            EventRelationsRepository::class,
            function () use ($container): DBALEventRelationsRepository {
                return new DBALEventRelationsRepository($container->get('dbal_connection'));
            }
        );

        $container->addShared(
            'event_main_language_query',
            function () use ($container): JSONLDMainLanguageQuery {
                return new JSONLDMainLanguageQuery(
                    $container->get('event_jsonld_repository'),
                    new Language('nl')
                );
            }
        );

        $container->addShared(
            EventPlaceHistoryProjector::class,
            function () use ($container): EventPlaceHistoryProjector {
                return new EventPlaceHistoryProjector(
                    $container->get(EventPlaceHistoryRepository::class),
                    $container->get('event_jsonld_cache'),
                    $container->get('udb2_event_cdbid_extractor'),
                    LoggerFactory::create($this->getContainer(), LoggerName::forWeb())
                );
            }
        );

        $container->addShared(
            EventPlaceHistoryRepository::class,
            function () use ($container): DBALEventPlaceHistoryRepository {
                return new DBALEventPlaceHistoryRepository($container->get('dbal_connection'));
            }
        );
    }
}

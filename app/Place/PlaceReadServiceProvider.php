<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\ReadModel\MainLanguage\JSONLDMainLanguageQuery;
use CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine\DBALPlaceRelationsRepository;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsProjector;

final class PlaceReadServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            PlaceRelationsProjector::class,
            PlaceRelationsRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            PlaceRelationsProjector::class,
            function () use ($container) {
                return new PlaceRelationsProjector(
                    $container->get(PlaceRelationsRepository::class)
                );
            }
        );

        $container->addShared(
            PlaceRelationsRepository::class,
            function () use ($container) {
                return new DBALPlaceRelationsRepository(
                    $container->get('dbal_connection')
                );
            }
        );
    }
}

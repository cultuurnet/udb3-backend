<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\UniqueAddressIdentifierFactory;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\UniqueAddressIdentifierProjector;
use CultuurNet\UDB3\User\CurrentUser;

final class PlaceUniqueAddressIdentifierProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            UniqueAddressIdentifierProjector::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            UniqueAddressIdentifierProjector::class,
            fn () => new UniqueAddressIdentifierProjector(
                $container->get('cache')(PlaceRequestHandlerServiceProvider::DUPLICATE_PLACE_IDENTIFIER),
                $container->get(UniqueAddressIdentifierFactory::class),
                $container->get(CurrentUser::class)->getId(),
            )
        );
    }
}

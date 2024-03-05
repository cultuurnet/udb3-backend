<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;

class OwnershipTest extends AggregateRootScenarioTestCase
{
    protected function getAggregateRootClass(): string
    {
        return Ownership::class;
    }

    /**
     * @test
     */
    public function it_can_be_requested(): void
    {
        $this->scenario
            ->when(function () {
                return Ownership::requestOwnership(
                    new UUID('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                    new UUID('9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ItemType::organizer(),
                    new UserId('auth0|63e22626e39a8ca1264bd29b')
                );
            })
            ->then([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b'
                ),
            ]);
    }
}

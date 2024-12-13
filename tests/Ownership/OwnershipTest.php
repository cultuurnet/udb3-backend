<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use Broadway\EventSourcing\Testing\AggregateRootScenarioTestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
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
                    new Uuid('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                    new Uuid('9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ItemType::organizer(),
                    new UserId('auth0|63e22626e39a8ca1264bd29b'),
                    new UserId('google-oauth2|102486314601596809843')
                );
            })
            ->then([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
            ]);
    }

    /**
     * @test
     */
    public function it_can_be_approved(): void
    {
        $this->scenario
            ->withAggregateId('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
            ->given([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
            ])
            ->when(function (Ownership $ownership) {
                $ownership->approve();
            })
            ->then([
                new OwnershipApproved('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            ]);
    }

    /**
     * @test
     */
    public function it_approves_only_once(): void
    {
        $this->scenario
            ->withAggregateId('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
            ->given([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
                new OwnershipApproved('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            ])
            ->when(function (Ownership $ownership) {
                $ownership->approve();
            })
            ->then([]);
    }

    /**
     * @test
     */
    public function it_can_be_rejected(): void
    {
        $this->scenario
            ->withAggregateId('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
            ->given([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
            ])
            ->when(function (Ownership $ownership) {
                $ownership->reject();
            })
            ->then([
                new OwnershipRejected('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            ]);
    }

    /**
     * @test
     */
    public function it_rejects_only_once(): void
    {
        $this->scenario
            ->withAggregateId('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
            ->given([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
                new OwnershipRejected('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            ])
            ->when(function (Ownership $ownership) {
                $ownership->reject();
            })
            ->then([]);
    }

    /**
     * @test
     * @dataProvider givensForDelete
     */
    public function it_can_be_deleted(array $givens): void
    {
        $this->scenario
            ->withAggregateId('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
            ->given($givens)
            ->when(function (Ownership $ownership) {
                $ownership->delete();
            })
            ->then([
                new OwnershipDeleted('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            ]);
    }

    public static function givensForDelete(): array
    {
        $ownershipRequested = new OwnershipRequested(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            'google-oauth2|102486314601596809843'
        );

        return [
            'requested' => [
                [
                    $ownershipRequested,
                ],
            ],
            'approved' => [
                [
                    $ownershipRequested,
                    new OwnershipApproved('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                ],
            ],
            'rejected' => [
                [
                    $ownershipRequested,
                    new OwnershipRejected('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_deletes_only_once(): void
    {
        $this->scenario
            ->withAggregateId('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
            ->given([
                new OwnershipRequested(
                    'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
                    '9e68dafc-01d8-4c1c-9612-599c918b981d',
                    'organizer',
                    'auth0|63e22626e39a8ca1264bd29b',
                    'google-oauth2|102486314601596809843'
                ),
                new OwnershipDeleted('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e'),
            ])
            ->when(function (Ownership $ownership) {
                $ownership->delete();
            })
            ->then([]);
    }
}

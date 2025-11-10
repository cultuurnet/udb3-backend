<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories\Search;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use PHPUnit\Framework\TestCase;

class DBALOwnershipSearchRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALOwnershipSearchRepository $ownershipSearchRepository;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->ownershipSearchRepository = new DBALOwnershipSearchRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_can_save_ownership_items(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );

        $this->ownershipSearchRepository->save($ownershipItem);

        $this->assertEquals(
            $ownershipItem,
            $this->ownershipSearchRepository->getById('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
        );
    }

    /**
     * @test
     * @dataProvider ownershipStateDataProvider
     */
    public function it_can_update_ownership_state(OwnershipState $ownershipState): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $this->ownershipSearchRepository->updateState(
            $ownershipItem->getId(),
            $ownershipState
        );

        $updatedOwnershipItem = new OwnershipItem(
            $ownershipItem->getId(),
            $ownershipItem->getItemId(),
            $ownershipItem->getItemType(),
            $ownershipItem->getOwnerId(),
            $ownershipState->toString()
        );

        $this->assertEquals(
            $updatedOwnershipItem,
            $this->ownershipSearchRepository->getById('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
        );
    }

    public static function ownershipStateDataProvider(): array
    {
        return [
            [OwnershipState::requested()],
            [OwnershipState::approved()],
            [OwnershipState::rejected()],
            [OwnershipState::deleted()],
        ];
    }

    /**
     * @test
     */
    public function it_can_update_the_role_id(): void
    {
        $roleId = new Uuid('a75aa571-8131-4fd6-ab9b-59c7672095e5');
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $this->ownershipSearchRepository->updateRoleId($ownershipItem->getId(), $roleId);

        $updatedOwnershipItem = (new OwnershipItem(
            $ownershipItem->getId(),
            $ownershipItem->getItemId(),
            $ownershipItem->getItemType(),
            $ownershipItem->getOwnerId(),
            OwnershipState::requested()->toString()
        ))->withRoleId($roleId);

        $this->assertEquals(
            $updatedOwnershipItem,
            $this->ownershipSearchRepository->getById('e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e')
        );
    }

    /**
     * @test
     */
    public function it_can_search_ownership_items_by_item_id(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $anotherOwnershipItem = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($anotherOwnershipItem);

        $this->assertEquals(
            new OwnershipItemCollection($anotherOwnershipItem, $ownershipItem),
            $this->ownershipSearchRepository->search(
                new SearchQuery([
                    new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d'),
                ])
            )
        );
    }

    /**
     * @test
     */
    public function it_can_search_ownerships_by_state(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $anotherOwnershipItem = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($anotherOwnershipItem);

        $this->assertEquals(
            new OwnershipItemCollection($anotherOwnershipItem),
            $this->ownershipSearchRepository->search(
                new SearchQuery([
                    new SearchParameter('state', OwnershipState::approved()->toString()),
                ])
            )
        );
    }

    /**
     * @test
     */
    public function it_can_search_ownerships_by_multiple_states(): void
    {
        $requestedOwnership = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($requestedOwnership);

        $approvedOwnership = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($approvedOwnership);

        $otherOwner = new OwnershipItem(
            'a17b54af-6a99-4fdb-bc02-112659be2451',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::rejected()->toString()
        );
        $this->ownershipSearchRepository->save($otherOwner);

        $rejectedOwnership = new OwnershipItem(
            'd3b07384-d9a0-4c1e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::rejected()->toString()
        );
        $this->ownershipSearchRepository->save($rejectedOwnership);

        $results = $this->ownershipSearchRepository->search(
            new SearchQuery([
                new SearchParameter('state', OwnershipState::requested()->toString()),
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('ownerId', 'auth0|63e22626e39a8ca1264bd29b'),
            ])
        );

        $this->assertEquals(2, $results->count());
        $this->assertTrue($results->contains($requestedOwnership));
        $this->assertTrue($results->contains($approvedOwnership));
    }

    /**
     * @test
     */
    public function it_can_search_ownership_items_by_owner_id(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $anotherOwnershipItem = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($anotherOwnershipItem);

        $this->assertEquals(
            new OwnershipItemCollection($ownershipItem),
            $this->ownershipSearchRepository->search(
                new SearchQuery([
                    new SearchParameter('ownerId', 'auth0|63e22626e39a8ca1264bd29b'),
                ])
            )
        );
    }

    /**
     * @test
     */
    public function it_takes_into_account_start_when_searching(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $anotherOwnershipItem = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($anotherOwnershipItem);

        $this->assertEquals(
            new OwnershipItemCollection($ownershipItem),
            $this->ownershipSearchRepository->search(
                new SearchQuery(
                    [
                        new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ],
                    1
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_takes_into_account_limit_when_searching(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $anotherOwnershipItem = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($anotherOwnershipItem);

        $this->assertEquals(
            new OwnershipItemCollection($anotherOwnershipItem),
            $this->ownershipSearchRepository->search(
                new SearchQuery(
                    [
                        new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ],
                    0,
                    1
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_takes_into_account_limit_and_start_when_searching(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $anotherOwnershipItem = new OwnershipItem(
            'a17b54af-6a99-4fdb-bc02-112659be2451',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($anotherOwnershipItem);

        $evenAnotherOwnershipItem = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            '5d0891db-1c4d-47b7-88cc-b48844fa259b',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($evenAnotherOwnershipItem);

        $this->assertEquals(
            new OwnershipItemCollection($anotherOwnershipItem),
            $this->ownershipSearchRepository->search(
                new SearchQuery(
                    [
                        new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ],
                    1,
                    1
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_calculates_total_items(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItem);

        $anotherOwnershipItem = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'a75aa571-8131-4fd6-ab9b-59c7672095e5',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($anotherOwnershipItem);

        $evenAnotherOwnershipItem = new OwnershipItem(
            'a17b54af-6a99-4fdb-bc02-112659be2451',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            '5d0891db-1c4d-47b7-88cc-b48844fa259b',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($evenAnotherOwnershipItem);

        $this->assertEquals(
            3,
            $this->ownershipSearchRepository->searchTotal(
                new SearchQuery(
                    [
                        new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d'),
                    ],
                    1,
                    1
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_throws_when_ownership_not_found_by_id(): void
    {
        $ownershipItem = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|63e22626e39a8ca1264bd29b',
            OwnershipState::requested()->toString()
        );

        $this->ownershipSearchRepository->save($ownershipItem);

        $this->expectException(OwnershipItemNotFound::class);
        $this->expectExceptionMessage('Ownership with id "wrong-id" was not found.');

        $this->ownershipSearchRepository->getById('wrong-id');
    }

    /**
     * @test
     * @dataProvider  sortOptionsProvider
     */
    public function it_can_sort_search_results(string $sortField, array $expectedOutputs,): void
    {
        $ownershipItemA = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|user-c',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItemA);

        $ownershipItemB = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|user-a',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItemB);

        $ownershipItemC = new OwnershipItem(
            'a17b54af-6a99-4fdb-bc02-112659be2451',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|user-b',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItemC);

        $result = $this->ownershipSearchRepository->search(
            new SearchQuery(
                [
                    new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d'),
                ],
                0,
                10,
                $sortField
            )
        );

        $resultArray = $result->toArray();
        $this->assertEquals( $expectedOutputs, array_map(fn($item) => $item->getOwnerId(), $resultArray) );
    }

    public function sortOptionsProvider() : array
    {
        return [/*
            ['owner_id',  ['auth0|user-a', 'auth0|user-b', 'auth0|user-c']],
           ['+owner_id', ['auth0|user-a', 'auth0|user-b', 'auth0|user-c']],
            ['-owner_id',  ['auth0|user-c', 'auth0|user-b', 'auth0|user-a']],
    */
            ['created',  ['auth0|user-c', 'auth0|user-a', 'auth0|user-b']],
       //     ['+created', ['auth0|user-c', 'auth0|user-a', 'auth0|user-b']],
   //         ['-created',  ['auth0|user-b', 'auth0|user-a', 'auth0|user-c']],
        ];
    }

    /**
     * @test
     */
    public function it_uses_default_sorting_when_no_sort_order_specified(): void
    {
        $ownershipItemA = new OwnershipItem(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|user-z',
            OwnershipState::requested()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItemA);

        $ownershipItemB = new OwnershipItem(
            '672265b6-d4d0-416e-9b0b-c29de7d18125',
            '9e68dafc-01d8-4c1c-9612-599c918b981d',
            'organizer',
            'auth0|user-a',
            OwnershipState::approved()->toString()
        );
        $this->ownershipSearchRepository->save($ownershipItemB);

        $result = $this->ownershipSearchRepository->search(
            new SearchQuery([
                new SearchParameter('itemId', '9e68dafc-01d8-4c1c-9612-599c918b981d'),
            ])
        );

        $resultArray = $result->toArray();
        $this->assertEquals('auth0|user-a', $resultArray[0]->getOwnerId());
        $this->assertEquals('auth0|user-z', $resultArray[1]->getOwnerId());
    }
}

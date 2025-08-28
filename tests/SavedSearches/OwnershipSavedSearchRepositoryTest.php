<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OwnershipSavedSearchRepositoryTest extends TestCase
{
    private const USER_ID = 'e2a78b6e-6eba-42a4-a366-248c5a8df792';

    private DocumentRepository $organizerDocumentRepository;

    /**
     * @var OwnershipSearchRepository&MockObject
     */
    private $ownershipSearchRepository;
    private OwnershipSavedSearchRepository $ownershipSavedSearchRepository;

    protected function setUp(): void
    {
        $this->organizerDocumentRepository = new InMemoryDocumentRepository();
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->ownershipSavedSearchRepository = new OwnershipSavedSearchRepository(
            JsonWebTokenFactory::createWithClaims(
                [
                    'sub' => self::USER_ID,
                ]
            ),
            $this->organizerDocumentRepository,
            $this->ownershipSearchRepository
        );
    }

    /**
     * @test
     * @dataProvider SavedSearchDataprovider
     */
    public function it_returns_an_empty_ownership_collection_if_no_were_found(array $jsonDocuments, OwnershipItemCollection $ownershipItemCollection, array $savedSearches): void
    {
        foreach ($jsonDocuments as $jsonDocument) {
            $this->organizerDocumentRepository->save($jsonDocument);
        }

        $this->ownershipSearchRepository->expects($this->once())
        ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('ownerId', self::USER_ID),
            ]))
            ->willReturn($ownershipItemCollection);

        $this->assertEquals(
            $savedSearches,
            $this->ownershipSavedSearchRepository->ownedByCurrentUser()
        );
    }

    public function SavedSearchDataprovider(): array
    {
        $itemId1 = 'c84d6167-5e71-4f2c-aedf-ad346e569e03';
        $itemId2 = '24bc3c50-5c9e-4e34-8bc7-1c9826352775';
        $ownershipItemId1 = 'a700c463-1401-4d91-a85c-fa289b6d8d8e';
        $ownershipItem2 = '8ba5ee51-8e0c-4179-8516-3942ac4ab6d2';

        return [
            'no_approved_ownerships' => [
                [],
                new OwnershipItemCollection(),
                [],
            ],
            'approved_ownership_were_found' => [
                [
                    new JsonDocument(
                        $itemId1,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'name' => [
                                    'nl' => 'Foobar NL',
                                ],
                            ]
                        )
                    ),
                    new JsonDocument(
                        $itemId2,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'name' => [
                                    'nl' => 'Bar Foo NL',
                                ],
                            ]
                        )
                    ),
                ],
                new OwnershipItemCollection(
                    new OwnershipItem(
                        $ownershipItemId1,
                        $itemId1,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        $ownershipItem2,
                        $itemId2,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    )
                ),
                [
                    new SavedSearch('Aanbod Foobar NL', new QueryString('organizer.id:' . $itemId1)),
                    new SavedSearch('Aanbod Bar Foo NL', new QueryString('organizer.id:' . $itemId2)),
                ],
            ],
            'fallback_if_dutch_is_not_present' => [
                [
                    new JsonDocument(
                        $itemId1,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'name' => [
                                    'en' => 'Foobar EN',
                                ],
                            ]
                        )
                    ),
                    new JsonDocument(
                        $itemId2,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'name' => [
                                    'fr' => 'Bar Foo FR',
                                ],
                            ]
                        )
                    ),
                ],
                new OwnershipItemCollection(
                    new OwnershipItem(
                        $ownershipItemId1,
                        $itemId1,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        $ownershipItem2,
                        $itemId2,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    )
                ),
                [
                    new SavedSearch('Aanbod Foobar EN', new QueryString('organizer.id:' . $itemId1)),
                    new SavedSearch('Aanbod Bar Foo FR', new QueryString('organizer.id:' . $itemId2)),
                ],
            ],
            'no_organizers' => [
                [
                    new JsonDocument(
                        $itemId1,
                        Json::encode(
                            [
                                '@type' => 'event',
                                'name' => [
                                    'nl' => 'Foobar EN',
                                ],
                            ]
                        )
                    ),
                    new JsonDocument(
                        $itemId2,
                        Json::encode(
                            [
                                '@type' => 'place',
                                'name' => [
                                    'nl' => 'Bar Foo FR',
                                ],
                            ]
                        )
                    ),
                ],
                new OwnershipItemCollection(
                    new OwnershipItem(
                        $ownershipItemId1,
                        $itemId1,
                        'event',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        $ownershipItem2,
                        $itemId2,
                        'place',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    )
                ),
                [],
            ],
        ];
    }
}

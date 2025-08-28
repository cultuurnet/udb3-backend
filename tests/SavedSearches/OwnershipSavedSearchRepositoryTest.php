<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
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

    private JsonWebToken $token;

    private DocumentRepository $organizerDocumentRepository;

    /**
     * @var OwnershipSearchRepository&MockObject
     */
    private $ownershipSearchRepository;
    private OwnershipSavedSearchRepository $ownershipSavedSearchRepository;

    protected function setUp(): void
    {
        $this->token = JsonWebTokenFactory::createWithClaims(
            [
                'sub' => self::USER_ID,
            ]
        );

        $this->organizerDocumentRepository = new InMemoryDocumentRepository();
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);

        $this->ownershipSavedSearchRepository = new OwnershipSavedSearchRepository(
            $this->token,
            $this->organizerDocumentRepository,
            $this->ownershipSearchRepository
        );
    }
    /**
     * @test
     */
    public function it_returns_an_empty_ownership_collection_if_no_were_found(): void
    {
        $this->ownershipSearchRepository->expects($this->once())
        ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('ownerId', self::USER_ID),
            ]))
            ->willReturn(new OwnershipItemCollection());

        $this->assertEquals(
            [],
            $this->ownershipSavedSearchRepository->ownedByCurrentUser()
        );
    }

    /**
     * @test
     */
    public function it_returns_ownership_collections_if_some_were_found(): void
    {
        $itemId1 = 'c84d6167-5e71-4f2c-aedf-ad346e569e03';
        $jsonLd = new JsonDocument(
            $itemId1,
            Json::encode(
                [
                    '@type' => 'organizer',
                    'name' => [
                        'nl' => 'Foobar NL',
                    ],
                ]
            )
        );
        $this->organizerDocumentRepository->save($jsonLd);

        $itemId2 = '24bc3c50-5c9e-4e34-8bc7-1c9826352775';
        $jsonLd2 = new JsonDocument(
            $itemId2,
            Json::encode(
                [
                    '@type' => 'organizer',
                    'name' => [
                        'nl' => 'Bar Foo NL',
                    ],
                ]
            )
        );
        $this->organizerDocumentRepository->save($jsonLd2);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('ownerId', self::USER_ID),
            ]))
            ->willReturn(
                new OwnershipItemCollection(
                    new OwnershipItem(
                        'a700c463-1401-4d91-a85c-fa289b6d8d8e',
                        $itemId1,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        '8ba5ee51-8e0c-4179-8516-3942ac4ab6d2',
                        $itemId2,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    )
                )
            );

        $this->assertEquals(
            [
                new SavedSearch('Aanbod Foobar NL', new QueryString('organizer.id:' . $itemId1)),
                new SavedSearch('Aanbod Bar Foo NL', new QueryString('organizer.id:' . $itemId2)),
            ],
            $this->ownershipSavedSearchRepository->ownedByCurrentUser()
        );
    }

    /**
     * @test
     */
    public function it_can_fallback_to_other_languages_if_dutch_is_not_present(): void
    {
        $itemId1 = 'c84d6167-5e71-4f2c-aedf-ad346e569e03';
        $jsonLd = new JsonDocument(
            $itemId1,
            Json::encode(
                [
                    '@type' => 'organizer',
                    'name' => [
                        'en' => 'Foobar EN',
                    ],
                ]
            )
        );
        $this->organizerDocumentRepository->save($jsonLd);

        $itemId2 = '24bc3c50-5c9e-4e34-8bc7-1c9826352775';
        $jsonLd2 = new JsonDocument(
            $itemId2,
            Json::encode(
                [
                    '@type' => 'organizer',
                    'name' => [
                        'fr' => 'Bar Foo FR',
                    ],
                ]
            )
        );
        $this->organizerDocumentRepository->save($jsonLd2);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('ownerId', self::USER_ID),
            ]))
            ->willReturn(
                new OwnershipItemCollection(
                    new OwnershipItem(
                        'a700c463-1401-4d91-a85c-fa289b6d8d8e',
                        $itemId1,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        '8ba5ee51-8e0c-4179-8516-3942ac4ab6d2',
                        $itemId2,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    )
                )
            );

        $this->assertEquals(
            [
                new SavedSearch('Aanbod Foobar EN', new QueryString('organizer.id:' . $itemId1)),
                new SavedSearch('Aanbod Bar Foo FR', new QueryString('organizer.id:' . $itemId2)),
            ],
            $this->ownershipSavedSearchRepository->ownedByCurrentUser()
        );
    }

    /**
     * @test
     */
    public function it_only_support_organizers(): void
    {
        $itemId1 = 'c84d6167-5e71-4f2c-aedf-ad346e569e03';
        $jsonLd = new JsonDocument(
            $itemId1,
            Json::encode(
                [
                    '@type' => 'organizer',
                    'name' => [
                        'en' => 'Foobar EN',
                    ],
                ]
            )
        );
        $this->organizerDocumentRepository->save($jsonLd);

        $itemId2 = '24bc3c50-5c9e-4e34-8bc7-1c9826352775';
        $jsonLd2 = new JsonDocument(
            $itemId2,
            Json::encode(
                [
                    '@type' => 'organizer',
                    'name' => [
                        'fr' => 'Bar Foo FR',
                    ],
                ]
            )
        );
        $this->organizerDocumentRepository->save($jsonLd2);

        $this->ownershipSearchRepository->expects($this->once())
            ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('ownerId', self::USER_ID),
            ]))
            ->willReturn(
                new OwnershipItemCollection(
                    new OwnershipItem(
                        'a700c463-1401-4d91-a85c-fa289b6d8d8e',
                        $itemId1,
                        'event',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        '8ba5ee51-8e0c-4179-8516-3942ac4ab6d2',
                        $itemId2,
                        'place',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    )
                )
            );

        $this->assertEquals(
            [],
            $this->ownershipSavedSearchRepository->ownedByCurrentUser()
        );
    }
}

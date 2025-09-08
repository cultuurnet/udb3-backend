<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use CultuurNet\UDB3\Http\Ownership\Search\SearchParameter;
use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Moderation\Organizer\WorkflowStatus;
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
    public function it_returns_ownerships(array $jsonDocuments, OwnershipItemCollection $ownershipItemCollection, array $savedSearches): void
    {
        foreach ($jsonDocuments as $jsonDocument) {
            $this->organizerDocumentRepository->save($jsonDocument);
        }

        $this->ownershipSearchRepository->expects($this->once())
        ->method('search')
            ->with(new SearchQuery([
                new SearchParameter('state', OwnershipState::approved()->toString()),
                new SearchParameter('itemType', 'organizer'),
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
        $approvedOrganizer = 'c84d6167-5e71-4f2c-aedf-ad346e569e03';
        $approvedOrganizer2 = '24bc3c50-5c9e-4e34-8bc7-1c9826352775';
        $deletedOrganizer = '68b84192-487c-832a-a344-86f8e3f29c38';

        $ownershipItemId1 = 'a700c463-1401-4d91-a85c-fa289b6d8d8e';
        $ownershipItemId2 = '8ba5ee51-8e0c-4179-8516-3942ac4ab6d2';
        $ownershipItemId3 = '35217a9e-18fd-4a6c-864d-468bce1bc9a7';
        $ownershipItemId4 = 'e05fec06-64c6-4acd-be4a-c7757a6974ab';

        return [
            'no_approved_ownerships' => [
                [],
                new OwnershipItemCollection(),
                [],
            ],
            'approved_ownership_were_found' => [
                [
                    new JsonDocument(
                        $approvedOrganizer,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'mainLanguage' => 'nl',
                                'workflowStatus' => WorkflowStatus::ACTIVE()->toString(),
                                'name' => [
                                    'nl' => 'Foobar NL',
                                    'fr' => 'Foobar FR',
                                ],
                            ]
                        )
                    ),
                    new JsonDocument(
                        $approvedOrganizer2,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'mainLanguage' => 'fr',
                                'workflowStatus' => WorkflowStatus::ACTIVE()->toString(),
                                'name' => [
                                    'nl' => 'Bar Foo NL',
                                    'fr' => 'Bar Foo FR',
                                ],
                            ]
                        )
                    ),
                    new JsonDocument(
                        $deletedOrganizer,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'mainLanguage' => 'fr',
                                'workflowStatus' => WorkflowStatus::DELETED()->toString(),
                                'name' => [
                                    'nl' => 'Deleted Bar Foo NL',
                                    'fr' => 'Deleted Bar Foo FR',
                                ],
                            ]
                        )
                    ),
                ],
                new OwnershipItemCollection(
                    new OwnershipItem(
                        $ownershipItemId1,
                        $approvedOrganizer,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        $ownershipItemId2,
                        $approvedOrganizer2,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        $ownershipItemId3,
                        $deletedOrganizer,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                ),
                [
                    new SavedSearch('Aanbod Foobar NL', new QueryString('organizer.id:' . $approvedOrganizer)),
                    new SavedSearch('Aanbod Bar Foo FR', new QueryString('organizer.id:' . $approvedOrganizer2)),
                ],
            ],
            'faulty_data' => [
                [
                    new JsonDocument(
                        $approvedOrganizer,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'mainLanguage' => 'en',
                                'workflowStatus' => WorkflowStatus::ACTIVE()->toString(),
                                'name' => [
                                    'nl' => 'Wrong mainlangue NL',
                                    'fr' => 'Wrong mainlangue FR',
                                ],
                            ]
                        )
                    ),
                    new JsonDocument(
                        $approvedOrganizer2,
                        Json::encode(
                            [
                                '@type' => 'organizer',
                                'workflowStatus' => WorkflowStatus::ACTIVE()->toString(),
                                'name' => [
                                    'nl' => 'No Mainlanguage NL',
                                    'fr' => 'No Mainlanguage FR',
                                ],
                            ]
                        )
                    ),
                ],
                new OwnershipItemCollection(
                    new OwnershipItem(
                        $ownershipItemId1,
                        $approvedOrganizer,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                    new OwnershipItem(
                        $ownershipItemId2,
                        $approvedOrganizer2,
                        'organizer',
                        self::USER_ID,
                        OwnershipState::approved()->toString()
                    ),
                ),
                [
                    new SavedSearch('Aanbod Wrong mainlangue NL', new QueryString('organizer.id:' . $approvedOrganizer)),
                    new SavedSearch('Aanbod No Mainlanguage NL', new QueryString('organizer.id:' . $approvedOrganizer2)),
                ],
            ],
        ];
    }
}

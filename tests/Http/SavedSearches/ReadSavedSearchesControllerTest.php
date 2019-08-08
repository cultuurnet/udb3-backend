<?php

namespace CultuurNet\UDB3\Symfony\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\SavedSearchReadRepositoryCollection;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use ValueObjects\StringLiteral\StringLiteral;

class ReadSavedSearchesControllerTest extends TestCase
{
    /**
     * @var SavedSearchRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $savedSearchRepository;

    /**
     * @var ReadSavedSearchesController
     */
    private $readSavedSearchesController;
    
    protected function setUp(): void
    {
        $this->savedSearchRepository = $this->createMock(SavedSearchRepositoryInterface::class);

        $savedSearchReadRepositoryCollection = new SavedSearchReadRepositoryCollection();
        $savedSearchReadRepositoryCollection = $savedSearchReadRepositoryCollection->withRepository(
            SapiVersion::V2(),
            $this->savedSearchRepository
        );

        $this->readSavedSearchesController = new ReadSavedSearchesController(
            $savedSearchReadRepositoryCollection
        );
    }

    /**
     * @test
     */
    public function it_gets_saved_searches_of_current_user(): void
    {
        $savedSearches = [
            new SavedSearch(
                new StringLiteral('Saved search 0'),
                new QueryString('city:leuven')
            ),
            new SavedSearch(
                new StringLiteral('Saved search 1'),
                new QueryString('city:herent')
            ),
        ];

        $this->savedSearchRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn($savedSearches);

        $actualResponse = $this->readSavedSearchesController->ownedByCurrentUser(
            SapiVersion::V2
        );

        $this->assertEquals(
            JsonResponse::create(
                $savedSearches
            ),
            $actualResponse
        );
    }
}

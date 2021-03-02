<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use ValueObjects\StringLiteral\StringLiteral;

class ReadSavedSearchesControllerTest extends TestCase
{
    /**
     * @var SavedSearchRepositoryInterface|MockObject
     */
    private $savedSearchRepository;

    /**
     * @var ReadSavedSearchesController
     */
    private $readSavedSearchesController;

    protected function setUp(): void
    {
        $this->savedSearchRepository = $this->createMock(SavedSearchRepositoryInterface::class);
        $this->readSavedSearchesController = new ReadSavedSearchesController($this->savedSearchRepository);
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

        $actualResponse = $this->readSavedSearchesController->ownedByCurrentUser();

        $this->assertEquals(
            JsonResponse::create(
                $savedSearches
            ),
            $actualResponse
        );
    }
}

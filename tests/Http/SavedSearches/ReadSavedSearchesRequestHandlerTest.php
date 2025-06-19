<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadSavedSearchesRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private SavedSearchesOwnedByCurrentUser&MockObject $savedSearchRepository;

    private ReadSavedSearchesRequestHandler $readSavedSearchesRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->savedSearchRepository = $this->createMock(SavedSearchesOwnedByCurrentUser::class);
        $this->readSavedSearchesRequestHandler = new ReadSavedSearchesRequestHandler($this->savedSearchRepository);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_gets_saved_searches_of_current_user(): void
    {
        $savedSearches = [
            new SavedSearch(
                'Saved search 0',
                new QueryString('city:leuven')
            ),
            new SavedSearch(
                'Saved search 1',
                new QueryString('city:herent'),
                'b706ca05-9139-422c-92e4-8aeb512466d6'
            ),
        ];

        $this->savedSearchRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn($savedSearches);

        $readSavedSearchesRequest = $this->psr7RequestBuilder
            ->build('GET');

        $response = $this->readSavedSearchesRequestHandler->handle($readSavedSearchesRequest);

        $this->assertJsonResponse(
            new JsonResponse($savedSearches, StatusCodeInterface::STATUS_OK),
            $response
        );
    }
}

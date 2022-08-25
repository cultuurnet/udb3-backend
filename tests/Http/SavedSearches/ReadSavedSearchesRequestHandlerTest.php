<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\StringLiteral;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadSavedSearchesRequestHandlerTest extends TestCase
{
    /**
     * @var SavedSearchRepositoryInterface|MockObject
     */
    private $savedSearchRepository;

    private ReadSavedSearchesRequestHandler $readSavedSearchesRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->savedSearchRepository = $this->createMock(SavedSearchRepositoryInterface::class);
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
                new StringLiteral('Saved search 0'),
                new QueryString('city:leuven')
            ),
            new SavedSearch(
                new StringLiteral('Saved search 1'),
                new QueryString('city:herent'),
                new StringLiteral('b706ca05-9139-422c-92e4-8aeb512466d6')
            ),
        ];

        $this->savedSearchRepository->expects($this->once())
            ->method('ownedByCurrentUser')
            ->willReturn($savedSearches);

        $readSavedSearchesRequest = $this->psr7RequestBuilder
            ->build('GET');

        $response = $this->readSavedSearchesRequestHandler->handle($readSavedSearchesRequest);

        $this->assertEquals(
            Json::encode($savedSearches),
            $response->getBody()->getContents()
        );
    }
}

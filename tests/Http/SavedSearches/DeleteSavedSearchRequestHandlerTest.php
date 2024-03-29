<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use PHPUnit\Framework\TestCase;

class DeleteSavedSearchRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const USER_ID = 'b9dc94df-c96b-4b71-8880-bd46e4e9a644';
    private const SEARCH_ID = '3d8711b0-3721-413d-a035-110ef87c4675';

    private TraceableCommandBus $commandBus;

    private DeleteSavedSearchRequestHandler $deleteSavedSearchRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteSavedSearchRequestHandler = new DeleteSavedSearchRequestHandler(
            self::USER_ID,
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_can_delete_a_search(): void
    {
        $deleteSavedSearchRequest = $this->psr7RequestBuilder
            ->withRouteParameter('id', self::SEARCH_ID)
            ->build('DELETE');

        $response = $this->deleteSavedSearchRequestHandler->handle($deleteSavedSearchRequest);

        $this->assertEquals(
            [
                new UnsubscribeFromSavedSearch(
                    self::USER_ID,
                    self::SEARCH_ID
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }
}

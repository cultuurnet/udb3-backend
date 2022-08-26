<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\StringLiteral;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

class CreateSavedSearchRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const USER_ID = 'b9dc94df-c96b-4b71-8880-bd46e4e9a644';

    private TraceableCommandBus $commandBus;

    private CreateSavedSearchRequestHandler $createSavedSearchRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->createSavedSearchRequestHandler = new CreateSavedSearchRequestHandler(
            self::USER_ID,
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }


    /**
     * @test
     */
    public function it_can_save_a_search(): void
    {
        $createSavedSearchRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray(
                [
                    'name' => 'Avondlessen in Gent',
                    'query' => 'regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)',
                ]
            )
            ->build('POST');

        $response = $this->createSavedSearchRequestHandler->handle($createSavedSearchRequest);

        $this->assertEquals(
            [
                new SubscribeToSavedSearch(
                    new StringLiteral(self::USER_ID),
                    new StringLiteral('Avondlessen in Gent'),
                    new QueryString('regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(null, StatusCodeInterface::STATUS_CREATED),
            $response
        );
    }

    /**
     * @test
     */
    public function it_will_throw_when_name_is_missing(): void
    {
        $createSavedSearchRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray(
                [
                    'query' => 'regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)',
                ]
            )
            ->build('POST');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('name is missing');

        $this->createSavedSearchRequestHandler->handle($createSavedSearchRequest);
    }

    /**
     * @test
     */
    public function it_will_throw_when_query_is_missing(): void
    {
        $createSavedSearchRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray(
                [
                    'name' => 'Avondlessen in Gent',
                ]
            )
            ->build('POST');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('query is missing');

        $this->createSavedSearchRequestHandler->handle($createSavedSearchRequest);
    }
}

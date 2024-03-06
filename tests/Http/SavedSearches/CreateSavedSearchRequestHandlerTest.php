<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

class CreateSavedSearchRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const USER_ID = 'b9dc94df-c96b-4b71-8880-bd46e4e9a644';
    private const ID = '3c504b25-b221-4aa5-ad75-5510379ba502';

    private TraceableCommandBus $commandBus;

    private CreateSavedSearchRequestHandler $createSavedSearchRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $mockVersion4Generator = $this->getMockBuilder(Version4Generator::class)->getMock();
        $mockVersion4Generator->expects($this->once())
            ->method('generate')
            ->willReturn(self::ID);

        $this->commandBus = new TraceableCommandBus();

        $this->createSavedSearchRequestHandler = new CreateSavedSearchRequestHandler(
            self::USER_ID,
            $this->commandBus,
            $mockVersion4Generator,
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
                    self::ID,
                    self::USER_ID,
                    'Avondlessen in Gent',
                    new QueryString('regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(['id' => self::ID], StatusCodeInterface::STATUS_CREATED),
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

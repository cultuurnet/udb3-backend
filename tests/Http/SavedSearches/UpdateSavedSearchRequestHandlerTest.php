<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Deserializer\MissingValueException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\SavedSearches\Command\UpdateSavedSearch;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchReadRepository;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

class UpdateSavedSearchRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const USER_ID = 'b9dc94df-c96b-4b71-8880-bd46e4e9a644';
    private const SAVED_SEARCH_ID = 'c269632a-a887-4f21-8455-1631c31e4df5';
    private const SAVED_SEARCH_ID_FROM_OTHER_USER = '1631c31e4df5-4f21-dt1j-a887-c269632a';
    private const NON_EXISTING_SAVED_SEARCH_ID = 'a887-4f21-8455-40065c90-c269632a';

    private TraceableCommandBus $commandBus;

    private UpdateSavedSearchRequestHandler $updateSavedSearchRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $savedSearchReadRepository = $this->createMock(SavedSearchReadRepository::class);
        $savedSearchReadRepository->expects($this->once())
            ->method('findById')
            ->willReturnCallback(function ($searchId) {
                switch ($searchId) {
                    case self::SAVED_SEARCH_ID:
                        return new SavedSearch(
                            'Avondlessen in Gent',
                            new QueryString('regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)'),
                            self::SAVED_SEARCH_ID,
                            self::USER_ID
                        );
                    case self::SAVED_SEARCH_ID_FROM_OTHER_USER:
                        return new SavedSearch(
                            'Avondlessen in Gent',
                            new QueryString('regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)'),
                            self::SAVED_SEARCH_ID,
                            '40065c90-f85c-49c5-b892-2d1f065def1a'
                        );
                    default:
                        return null;
                }
            });

        $this->updateSavedSearchRequestHandler = new UpdateSavedSearchRequestHandler(
            self::USER_ID,
            $this->commandBus,
            $savedSearchReadRepository
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }


    /**
     * @test
     */
    public function it_can_update_a_search(): void
    {
        $id = self::SAVED_SEARCH_ID;
        $createSavedSearchRequest = $this->psr7RequestBuilder
            ->withRouteParameter('id', $id)
            ->withJsonBodyFromArray(
                [
                    'name' => 'Avondlessen in Gent',
                    'query' => 'regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)',
                ]
            )
            ->build('PUT');

        $response = $this->updateSavedSearchRequestHandler->handle($createSavedSearchRequest);

        $this->assertEquals(
            [
                new UpdateSavedSearch(
                    $id,
                    self::USER_ID,
                    'Avondlessen in Gent',
                    new QueryString('regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)')
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertEquals(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function it_will_throw_when_name_is_missing(): void
    {
        $createSavedSearchRequest = $this->psr7RequestBuilder
            ->withRouteParameter('id', self::SAVED_SEARCH_ID)
            ->withJsonBodyFromArray(
                [
                    'query' => 'regions:nis-44021 AND (typicalAgeRange:[18 TO *] AND name.*:Avondlessen)',
                ]
            )
            ->build('PUT');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('name is missing');

        $this->updateSavedSearchRequestHandler->handle($createSavedSearchRequest);
    }

    /**
     * @test
     */
    public function it_will_throw_when_query_is_missing(): void
    {
        $createSavedSearchRequest = $this->psr7RequestBuilder
            ->withRouteParameter('id', self::SAVED_SEARCH_ID)
            ->withJsonBodyFromArray(
                [
                    'name' => 'Avondlessen in Gent',
                ]
            )
            ->build('PUT');

        $this->expectException(MissingValueException::class);
        $this->expectExceptionMessage('query is missing');

        $this->updateSavedSearchRequestHandler->handle($createSavedSearchRequest);
    }

    /**
     * @test
     */
    public function it_will_throw_when_user_does_not_own_saved_search(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('id', self::SAVED_SEARCH_ID_FROM_OTHER_USER)
            ->withJsonBodyFromArray(
                [
                    'name' => 'Avondlessen in Gent',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::unauthorizedSavedSearch(),
            fn () => $this->updateSavedSearchRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_will_throw_when_saved_search_does_not_exist(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('id', self::NON_EXISTING_SAVED_SEARCH_ID)
            ->withJsonBodyFromArray(
                [
                    'name' => 'Avondlessen in Gent',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::savedSearchNotFound(self::NON_EXISTING_SAVED_SEARCH_ID),
            fn () => $this->updateSavedSearchRequestHandler->handle($request)
        );
    }
}

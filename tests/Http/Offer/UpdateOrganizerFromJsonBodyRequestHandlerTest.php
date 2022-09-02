<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

class UpdateOrganizerFromJsonBodyRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const ORGANIZER_ID = 'd03b2ac9-19b2-46d7-8e59-224e80733163';

    private TraceableCommandBus $commandBus;

    private UpdateOrganizerFromJsonBodyRequestHandler $updateOrganizerFromJsonBodyRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $organizerRepository = new InMemoryDocumentRepository();
        $organizerRepository->save(
            new JsonDocument(self::ORGANIZER_ID, '{}')
        );

        $this->commandBus = new TraceableCommandBus();

        $this->updateOrganizerFromJsonBodyRequestHandler = new UpdateOrganizerFromJsonBodyRequestHandler(
            $this->commandBus,
            $organizerRepository
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_updating_the_organizer_of_an_offer_via_json_body(
        string $offerType,
        UpdateOrganizer $updateOrganizer
    ): void {
        $updateOrganizerFromJsonBodyRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(['organizer' => self::ORGANIZER_ID])
            ->build('POST');

        $response = $this->updateOrganizerFromJsonBodyRequestHandler->handle($updateOrganizerFromJsonBodyRequest);

        $this->assertEquals(
            [
                $updateOrganizer,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_when_organizer_is_missing(
        string $offerType,
        UpdateOrganizer $updateOrganizer
    ): void {
        $updateOrganizerFromJsonBodyRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(
                []
            )
            ->build('POST');

        $response = $this->updateOrganizerFromJsonBodyRequestHandler->handle($updateOrganizerFromJsonBodyRequest);

        $this->assertJsonResponse(
            new JsonResponse(
                ['error' => 'organizer required'],
                StatusCodeInterface::STATUS_BAD_REQUEST
            ),
            $response
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_if_organizer_does_not_exist(string $offerType): void
    {
        $nonExistingId = 'ab296131-876f-4178-bd11-955c3def6647';
        $updateOrganizerFromJsonBodyRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(['organizer' => $nonExistingId])
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('Organizer with id "' . $nonExistingId . '" does not exist.'),
            fn () => $this->updateOrganizerFromJsonBodyRequestHandler->handle($updateOrganizerFromJsonBodyRequest)
        );
    }

    public function offerTypeDataProvider(): array
    {
        $updateOrganizer = new UpdateOrganizer(
            self::OFFER_ID,
            self::ORGANIZER_ID
        );
        return [
            [
                'offerType' => 'events',
                'updateOrganizer' => $updateOrganizer,
            ],
            [
                'offerType' => 'places',
                'updateOrganizer' => $updateOrganizer,
            ],
        ];
    }
}

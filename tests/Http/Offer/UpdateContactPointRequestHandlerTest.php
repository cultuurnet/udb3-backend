<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint as EventUpdateContactPoint;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint as PlaceUpdateContactPoint;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

final class UpdateContactPointRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateContactPointRequestHandler $updateContactPointRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateContactPointRequestHandler = new UpdateContactPointRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_updating_the_contact_point_of_an_offer(
        string $offerType,
        AbstractUpdateContactPoint $updateContactPoint
    ): void {
        $updateContactPointRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(
                [
                    'contactPoint' => [
                        'url' => [
                            'https://www.publiq.be/',
                        ],
                        'email' => [],
                        'phone' => [
                            '0475/123123',
                            '02/123123',
                        ],
                    ],
                ]
            )
            ->build('PUT');

        $response = $this->updateContactPointRequestHandler->handle($updateContactPointRequest);

        $this->assertEquals(
            [
                $updateContactPoint,
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
    public function it_throws_updating_whn_contact_point_is_incomplete(
        string $offerType,
        AbstractUpdateContactPoint $updateContactPoint
    ): void {
        $updateContactPointRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(
                [
                    'contactPoint' => [
                        'url' => [
                            'https://www.publiq.be/',
                        ],
                        'phone' => [
                            '0475/123123',
                            '02/123123',
                        ],
                    ],
                ]
            )
            ->build('PUT');

        $response = $this->updateContactPointRequestHandler->handle($updateContactPointRequest);

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'error' => 'contactPoint and his properties required',
                ],
                StatusCodeInterface::STATUS_BAD_REQUEST
            ),
            $response
        );
    }

    public function offerTypeDataProvider(): array
    {
        $contactPoint = new ContactPoint(
            [
                '0475/123123',
                '02/123123',
            ],
            [],
            [
                'https://www.publiq.be/',
            ]
        );

        return [
            [
                'offerType' => 'events',
                'updateContactPoint' => new EventUpdateContactPoint(
                    self::OFFER_ID,
                    $contactPoint
                ),
            ],
            [
                'offerType' => 'places',
                'updateContactPoint' => new PlaceUpdateContactPoint(
                    self::OFFER_ID,
                    $contactPoint
                ),
            ],
        ];
    }
}

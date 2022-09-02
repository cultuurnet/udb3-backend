<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo as EventUpdateBookingInfo;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo as PlaceUpdateBookingInfo;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\TestCase;

final class UpdateBookingInfoRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateBookingInfoRequestHandler $updateBookingInfoRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateBookingInfoRequestHandler = new UpdateBookingInfoRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_updating_the_booking_info_of_an_offer(
        string $offerType,
        AbstractUpdateBookingInfo $updateBookingInfo
    ): void {
        $updateBookingInfoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(
                [
                    'bookingInfo' => [
                        'url' => 'https://www.publiq.be/',
                        'urlLabel' => ['nl' => 'Publiq vzw'],
                        'phone' => '02/1232323',
                        'email' => 'info@publiq.be',
                        'availabilityStarts' => '2023-01-01T00:00:00+01:00',
                        'availabilityEnds' => '2028-01-31T23:59:59+01:00',
                    ],
                ]
            )
            ->build('PUT');

        $response = $this->updateBookingInfoRequestHandler->handle($updateBookingInfoRequest);

        $this->assertEquals(
            [
                $updateBookingInfo,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function offerTypeDataProvider(): array
    {
        $bookingInfo = new BookingInfo(
            'https://www.publiq.be/',
            new MultilingualString(new Language('nl'), new StringLiteral('Publiq vzw')),
            '02/1232323',
            'info@publiq.be',
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2023-01-01T00:00:00+01:00'),
            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2028-01-31T23:59:59+01:00')
        );

        return [
            [
                'offerType' => 'events',
                'updateBookingInfo' => new EventUpdateBookingInfo(
                    self::OFFER_ID,
                    $bookingInfo
                ),
            ],
            [
                'offerType' => 'places',
                'updateBookingInfo' => new PlaceUpdateBookingInfo(
                    self::OFFER_ID,
                    $bookingInfo
                ),
            ],
        ];
    }
}

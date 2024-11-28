<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo as EventUpdateBookingInfo;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo as PlaceUpdateBookingInfo;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Iterator;
use PHPUnit\Framework\TestCase;

final class UpdateBookingInfoRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    use AssertApiProblemTrait;

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
        string $url,
        AbstractUpdateBookingInfo $updateBookingInfo
    ): void {
        $updateBookingInfoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(
                [
                    'bookingInfo' => [
                        'url' => $url,
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
        $basicUrl = 'https://www.publiq.be/';
        $specialCharactersUrl = 'https://publiq-vzw.com/Inschrijven.aspx?ReservatieCat=2#/inschrijven/activiteiten';

        $bookingInfoBasicUrl = new BookingInfo(
            $basicUrl,
            new MultilingualString(new Language('nl'), 'Publiq vzw'),
            new TelephoneNumber('02/1232323'),
            new EmailAddress('info@publiq.be'),
            DateTimeFactory::fromAtom('2023-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2028-01-31T23:59:59+01:00')
        );

        $bookingInfoSpecialCharactersUrl = new BookingInfo(
            $specialCharactersUrl,
            new MultilingualString(new Language('nl'), 'Publiq vzw'),
            new TelephoneNumber('02/1232323'),
            new EmailAddress('info@publiq.be'),
            DateTimeFactory::fromAtom('2023-01-01T00:00:00+01:00'),
            DateTimeFactory::fromAtom('2028-01-31T23:59:59+01:00')
        );

        return [
            [
                'offerType' => 'events',
                'url' => $basicUrl,
                'updateBookingInfo' => new EventUpdateBookingInfo(
                    self::OFFER_ID,
                    $bookingInfoBasicUrl
                ),
            ],
            [
                'offerType' => 'places',
                'url' => $basicUrl,
                'updateBookingInfo' => new PlaceUpdateBookingInfo(
                    self::OFFER_ID,
                    $bookingInfoBasicUrl
                ),
            ],
            [
                'offerType' => 'events',
                'url' => $specialCharactersUrl,
                'updateBookingInfo' => new EventUpdateBookingInfo(
                    self::OFFER_ID,
                    $bookingInfoSpecialCharactersUrl
                ),
            ],
            [
                'offerType' => 'places',
                'url' => $specialCharactersUrl,
                'updateBookingInfo' => new PlaceUpdateBookingInfo(
                    self::OFFER_ID,
                    $bookingInfoSpecialCharactersUrl
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideApiProblemTestData
     */
    public function it_throws_an_api_problem_when_invalid_data(array $input, array $schemaErrors): void
    {
        $updateBookingInfoRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray($input)
            ->build('PUT');


        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$schemaErrors),
            fn () => $this->updateBookingInfoRequestHandler->handle($updateBookingInfoRequest)
        );
    }

    public function provideApiProblemTestData(): Iterator
    {
        yield 'start date is after end date' => [
            'input' => [
                'url' => 'https://www.publiq.be/',
                'urlLabel' => ['nl' => 'Publiq vzw'],
                'phone' => '02/1232323',
                'email' => 'info@publiq.be',
                'availabilityStarts' => '2028-01-01T00:00:00+01:00',
                'availabilityEnds' => '2023-01-31T23:59:59+01:00',
            ],
            'schemaErrors' => [
                new SchemaError(
                    '/availabilityEnds',
                    'availabilityEnds should not be before availabilityStarts'
                ),
            ],
        ];

        yield 'url without urlLabel' => [
            'input' => [
                'url' => 'https://www.publiq.be/',
            ],
            'schemaErrors' => [
                new SchemaError(
                    '/',
                    '\'urlLabel\' property is required by \'url\' property'
                ),
            ],
        ];

        yield 'url with triple slashes' => [
            'input' => [
                'url' => 'https:///www.publiq.be/',
                'urlLabel' => ['nl' => 'Publiq vzw'],
                'phone' => '02/1232323',
                'email' => 'info@publiq.be',
                'availabilityStarts' => '2023-01-01T00:00:00+01:00',
                'availabilityEnds' => '2028-01-31T23:59:59+01:00',
            ],
            'schemaErrors' => [
                new SchemaError(
                    '/url',
                    'The string should match pattern: ^http[s]?:\/\/\w'
                ),
            ],
        ];
    }
}

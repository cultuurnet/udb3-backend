<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Providers\Ticketmatic;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Providers\InvalidReservationLink;
use CultuurNet\UDB3\Ticketing\Providers\ReservationLinkBookingInfo;
use CultuurNet\UDB3\Ticketing\Providers\ReservationLinkNotFound;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class HttpTicketmaticProviderTest extends TestCase
{
    private const RESERVATION_LINK = 'https://leuven-school-met-cultuur.test.ticketmatic.com/30cc/register/12345';

    private MockHandler $mockHandler;

    private LoggerInterface&MockObject $logger;

    private HttpTicketmaticProvider $provider;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new HttpTicketmaticProvider(
            new Client(['handler' => HandlerStack::create($this->mockHandler)]),
            'https://leuven-school-met-cultuur.test.ticketmatic.com/api/v1/',
            'token-abc',
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_sends_the_reservation_link_with_the_bearer_token(): void
    {
        $this->mockHandler->append($this->bookingInfoResponse(true, null));

        $this->provider->getBookingInfo(new Url(self::RESERVATION_LINK));

        $request = $this->mockHandler->getLastRequest();
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals(
            'https://leuven-school-met-cultuur.test.ticketmatic.com/api/v1/booking-info?link=https%3A%2F%2Fleuven-school-met-cultuur.test.ticketmatic.com%2F30cc%2Fregister%2F12345',
            (string) $request->getUri()
        );
        $this->assertEquals('Bearer token-abc', $request->getHeaderLine('Authorization'));
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
    }

    /**
     * @test
     */
    public function it_sends_the_reservation_link_in_lowercase(): void
    {
        $this->mockHandler->append($this->bookingInfoResponse(true, null));

        $this->provider->getBookingInfo(new Url('https://Leuven-School-Met-Cultuur.test.ticketmatic.com/30CC/register/12345'));

        $this->assertEquals(
            'https://leuven-school-met-cultuur.test.ticketmatic.com/api/v1/booking-info?link=https%3A%2F%2Fleuven-school-met-cultuur.test.ticketmatic.com%2F30cc%2Fregister%2F12345',
            (string) $this->mockHandler->getLastRequest()->getUri()
        );
    }

    /**
     * @test
     */
    public function it_returns_the_identifier_and_the_availability(): void
    {
        $this->mockHandler->append($this->bookingInfoResponse(true, null));

        $this->assertEquals(
            new ReservationLinkBookingInfo('ticketmatic:30cc:12345', BookingAvailability::Available(), null),
            $this->provider->getBookingInfo(new Url(self::RESERVATION_LINK))
        );
    }

    /**
     * @test
     */
    public function it_returns_the_waiting_list_when_tickets_are_no_longer_available(): void
    {
        $waitingListUrl = 'https://leuven-school-met-cultuur.test.ticketmatic.com/30cc/waitinglist/12345';
        $this->mockHandler->append($this->bookingInfoResponse(false, $waitingListUrl));

        $this->assertEquals(
            new ReservationLinkBookingInfo('ticketmatic:30cc:12345', BookingAvailability::Unavailable(), new Url($waitingListUrl)),
            $this->provider->getBookingInfo(new Url(self::RESERVATION_LINK))
        );
    }

    /**
     * @test
     */
    public function it_throws_when_there_is_no_open_event_behind_the_link(): void
    {
        $this->mockHandler->append(
            new Response(404, [], Json::encode(['error' => 'No event found for given reservation link']))
        );

        $this->expectException(ReservationLinkNotFound::class);

        $this->provider->getBookingInfo(new Url(self::RESERVATION_LINK));
    }

    /**
     * @test
     */
    public function it_throws_when_the_link_is_not_a_reservation_link_of_this_environment(): void
    {
        $this->mockHandler->append(
            new Response(422, [], Json::encode([
                'message' => 'The link is not a School met Cultuur reservation link.',
                'errors' => ['link' => ['The link is not a School met Cultuur reservation link.']],
            ]))
        );

        $this->expectException(InvalidReservationLink::class);

        $this->provider->getBookingInfo(new Url(self::RESERVATION_LINK));
    }

    /**
     * @test
     */
    public function it_logs_and_throws_on_other_errors(): void
    {
        $this->mockHandler->append(new Response(500, [], Json::encode(['message' => 'Server Error'])));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Ticketmatic booking info request failed', [
                'reservation_link' => self::RESERVATION_LINK,
                'status_code' => 500,
                'body' => '{"message":"Server Error"}',
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ticketmatic API returned status code 500');

        $this->provider->getBookingInfo(new Url(self::RESERVATION_LINK));
    }

    private function bookingInfoResponse(bool $ticketsAvailable, ?string $waitingListUrl): Response
    {
        return new Response(200, [], Json::encode([
            'accountId' => '30cc',
            'eventId' => 12345,
            'ticketsAvailable' => $ticketsAvailable,
            'waitingListUrl' => $waitingListUrl,
        ]));
    }
}

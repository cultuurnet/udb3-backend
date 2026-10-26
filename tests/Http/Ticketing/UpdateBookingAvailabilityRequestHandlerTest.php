<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ticketing;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateBookingAvailabilityByReservationLink;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistration;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrations;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateBookingAvailabilityRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const CLIENT_ID = 'ticketmatic-availability-sync';
    private const IDENTIFIER = 'ticketmatic:30cc:12345';
    private const EVENT_ID = '40021958-0ad8-46bd-8528-3ac3686818a1';
    private const OTHER_EVENT_ID = 'a0a78cd3-df53-4359-97a3-04b3680e69a4';
    private const RESERVATION_LINK = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345';

    private TraceableCommandBus $commandBus;

    private TicketingRegistrationRepository&MockObject $ticketingRegistrationRepository;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->ticketingRegistrationRepository = $this->createMock(TicketingRegistrationRepository::class);
    }

    /**
     * @test
     */
    public function it_updates_the_availability_on_every_event_with_the_identifier(): void
    {
        $this->ticketingRegistrationRepository->method('findByIdentifier')
            ->with(self::IDENTIFIER)
            ->willReturn(
                new TicketingRegistrations(
                    new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::RESERVATION_LINK), self::IDENTIFIER),
                    new TicketingRegistration(new Uuid(self::OTHER_EVENT_ID), new Url(self::RESERVATION_LINK), self::IDENTIFIER)
                )
            );

        $response = $this->getRequestHandler(self::CLIENT_ID)->handle(
            $this->getRequest('{"identifier":"' . self::IDENTIFIER . '","type":"Unavailable","waitingListUrl":"https://www.example.com/wachtlijst"}')
        );

        $this->assertEquals(StatusCodeInterface::STATUS_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals(
            [
                new UpdateBookingAvailabilityByReservationLink(
                    self::EVENT_ID,
                    new Url(self::RESERVATION_LINK),
                    BookingAvailability::Unavailable()
                ),
                new UpdateBookingAvailabilityByReservationLink(
                    self::OTHER_EVENT_ID,
                    new Url(self::RESERVATION_LINK),
                    BookingAvailability::Unavailable()
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_returns_not_found_for_an_unknown_identifier(): void
    {
        $this->ticketingRegistrationRepository->method('findByIdentifier')->willReturn(new TicketingRegistrations());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('No reservation link is registered with identifier "' . self::IDENTIFIER . '".'),
            fn () => $this->getRequestHandler(self::CLIENT_ID)->handle(
                $this->getRequest('{"identifier":"' . self::IDENTIFIER . '","type":"Available"}')
            )
        );
        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_forbids_other_clients(): void
    {
        $this->ticketingRegistrationRepository->expects($this->never())->method('findByIdentifier');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('Only ticket sales providers can update the availability of reservation links.'),
            fn () => $this->getRequestHandler('other-client')->handle(
                $this->getRequest('{"identifier":"' . self::IDENTIFIER . '","type":"Available"}')
            )
        );
    }

    /**
     * @test
     */
    public function it_forbids_a_token_without_client(): void
    {
        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('Only ticket sales providers can update the availability of reservation links.'),
            fn () => $this->getRequestHandler(null)->handle(
                $this->getRequest('{"identifier":"' . self::IDENTIFIER . '","type":"Available"}')
            )
        );
    }

    /**
     * @test
     */
    public function it_rejects_a_body_without_identifier(): void
    {
        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(new SchemaError('/', 'The required properties (identifier) are missing')),
            fn () => $this->getRequestHandler(self::CLIENT_ID)->handle($this->getRequest('{"type":"Available"}'))
        );
        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_rejects_an_unknown_type(): void
    {
        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/type', 'The data should match one item from enum')
            ),
            fn () => $this->getRequestHandler(self::CLIENT_ID)->handle(
                $this->getRequest('{"identifier":"' . self::IDENTIFIER . '","type":"SoldOut"}')
            )
        );
        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    private function getRequestHandler(?string $clientId): UpdateBookingAvailabilityRequestHandler
    {
        return new UpdateBookingAvailabilityRequestHandler(
            $this->commandBus,
            $this->ticketingRegistrationRepository,
            JsonWebTokenFactory::createWithClaims($clientId === null ? [] : ['azp' => $clientId]),
            [self::CLIENT_ID]
        );
    }

    private function getRequest(string $body): ServerRequestInterface
    {
        return (new Psr7RequestBuilder())->withBodyFromString($body)->build('POST');
    }
}

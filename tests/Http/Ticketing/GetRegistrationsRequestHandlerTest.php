<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ticketing;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebTokenFactory;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistration;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrations;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class GetRegistrationsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const CLIENT_ID = 'cultuurkuur';
    private const IDENTIFIER = 'ticketmatic:30cc:12345';
    private const OTHER_IDENTIFIER = 'ticketmatic:30cc:67890';
    private const EVENT_ID = '40021958-0ad8-46bd-8528-3ac3686818a1';
    private const OTHER_EVENT_ID = 'a0a78cd3-df53-4359-97a3-04b3680e69a4';
    private const RESERVATION_LINK = 'https://leuven-school-met-cultuur.ticketmatic.com/30cc/register/12345';
    private const OTHER_RESERVATION_LINK = 'https://leuven-school-met-cultuur.ticketmatic.com/30CC/register/12345';

    private TicketingRegistrationRepository&MockObject $ticketingRegistrationRepository;

    protected function setUp(): void
    {
        $this->ticketingRegistrationRepository = $this->createMock(TicketingRegistrationRepository::class);
    }

    /**
     * @test
     */
    public function it_returns_the_events_per_identifier_in_the_requested_order(): void
    {
        $this->ticketingRegistrationRepository->expects($this->once())
            ->method('findByIdentifiers')
            ->with([self::OTHER_IDENTIFIER, self::IDENTIFIER])
            ->willReturn(
                new TicketingRegistrations(
                    new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::RESERVATION_LINK), self::IDENTIFIER),
                    new TicketingRegistration(new Uuid(self::OTHER_EVENT_ID), new Url(self::RESERVATION_LINK), self::IDENTIFIER)
                )
            );

        $response = $this->getRequestHandler(self::CLIENT_ID)->handle(
            $this->getRequest('?identifiers=' . self::OTHER_IDENTIFIER . ',' . self::IDENTIFIER)
        );

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals(
            [
                'registrations' => [
                    ['identifier' => self::OTHER_IDENTIFIER, 'events' => []],
                    [
                        'identifier' => self::IDENTIFIER,
                        'events' => [
                            'https://io.uitdatabank.dev/events/' . self::EVENT_ID,
                            'https://io.uitdatabank.dev/events/' . self::OTHER_EVENT_ID,
                        ],
                    ],
                ],
            ],
            Json::decodeAssociatively($response->getBody()->getContents())
        );
    }

    /**
     * @test
     */
    public function it_returns_an_event_with_several_links_for_the_identifier_once(): void
    {
        $this->ticketingRegistrationRepository->method('findByIdentifiers')
            ->willReturn(
                new TicketingRegistrations(
                    new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::RESERVATION_LINK), self::IDENTIFIER),
                    new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::OTHER_RESERVATION_LINK), self::IDENTIFIER)
                )
            );

        $response = $this->getRequestHandler(self::CLIENT_ID)->handle(
            $this->getRequest('?identifiers=' . self::IDENTIFIER)
        );

        $this->assertEquals(
            [
                'registrations' => [
                    [
                        'identifier' => self::IDENTIFIER,
                        'events' => ['https://io.uitdatabank.dev/events/' . self::EVENT_ID],
                    ],
                ],
            ],
            Json::decodeAssociatively($response->getBody()->getContents())
        );
    }

    /**
     * @test
     */
    public function it_matches_an_identifier_in_another_case(): void
    {
        $this->ticketingRegistrationRepository->method('findByIdentifiers')
            ->willReturn(
                new TicketingRegistrations(
                    new TicketingRegistration(new Uuid(self::EVENT_ID), new Url(self::RESERVATION_LINK), self::IDENTIFIER)
                )
            );

        $response = $this->getRequestHandler(self::CLIENT_ID)->handle(
            $this->getRequest('?identifiers=' . strtoupper(self::IDENTIFIER))
        );

        $this->assertEquals(
            [
                'registrations' => [
                    [
                        'identifier' => strtoupper(self::IDENTIFIER),
                        'events' => ['https://io.uitdatabank.dev/events/' . self::EVENT_ID],
                    ],
                ],
            ],
            Json::decodeAssociatively($response->getBody()->getContents())
        );
    }

    /**
     * @test
     */
    public function it_returns_not_found_without_identifiers(): void
    {
        $this->ticketingRegistrationRepository->expects($this->never())->method('findByIdentifiers');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::queryParameterMissing('identifiers'),
            fn () => $this->getRequestHandler(self::CLIENT_ID)->handle($this->getRequest('?identifiers='))
        );
    }

    /**
     * @test
     */
    public function it_forbids_other_clients(): void
    {
        $this->ticketingRegistrationRepository->expects($this->never())->method('findByIdentifiers');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('Only ticketing partners can look up the events of registrations.'),
            fn () => $this->getRequestHandler('other-client')->handle(
                $this->getRequest('?identifiers=' . self::IDENTIFIER)
            )
        );
    }

    /**
     * @test
     */
    public function it_forbids_a_token_without_client(): void
    {
        $this->assertCallableThrowsApiProblem(
            ApiProblem::forbidden('Only ticketing partners can look up the events of registrations.'),
            fn () => $this->getRequestHandler(null)->handle($this->getRequest('?identifiers=' . self::IDENTIFIER))
        );
    }

    private function getRequestHandler(?string $clientId): GetRegistrationsRequestHandler
    {
        return new GetRegistrationsRequestHandler(
            $this->ticketingRegistrationRepository,
            new CallableIriGenerator(fn (string $eventId): string => 'https://io.uitdatabank.dev/events/' . $eventId),
            JsonWebTokenFactory::createWithClaims($clientId === null ? [] : ['azp' => $clientId]),
            [self::CLIENT_ID]
        );
    }

    private function getRequest(string $query): ServerRequestInterface
    {
        return (new Psr7RequestBuilder())->withUriFromString('/ticketing/registrations' . $query)->build('GET');
    }
}

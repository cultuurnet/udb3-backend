<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Providers\Ticketmatic;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Ticketing\Providers\InvalidReservationLink;
use CultuurNet\UDB3\Ticketing\Providers\ReservationLinkBookingInfo;
use CultuurNet\UDB3\Ticketing\Providers\ReservationLinkNotFound;
use CultuurNet\UDB3\Ticketing\Providers\TicketingProvider;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The School met Cultuur plugin of Ticketmatic, see https://leuven-1b2f95.gitlab.io/leuven-school-met-cultuur/api/
 */
final class HttpTicketmaticProvider implements TicketingProvider
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $apiBaseUrl,
        private readonly string $bearerToken,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getBookingInfo(Url $reservationLink): ReservationLinkBookingInfo
    {
        // Ticketmatic only generates lowercase links, so a link typed with capitals still finds its event.
        $query = http_build_query(['link' => strtolower($reservationLink->toString())], '', '&', PHP_QUERY_RFC3986);

        $response = $this->client->sendRequest(
            new Request(
                'GET',
                rtrim($this->apiBaseUrl, '/') . '/booking-info?' . $query,
                [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                    'Accept' => 'application/json',
                ]
            )
        );

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw ReservationLinkNotFound::forLink($reservationLink);
        }

        if ($response->getStatusCode() === StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY) {
            throw InvalidReservationLink::forLink($reservationLink);
        }

        if ($response->getStatusCode() !== StatusCodeInterface::STATUS_OK) {
            $this->logger->error('Ticketmatic booking info request failed', [
                'reservation_link' => $reservationLink->toString(),
                'status_code' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
            ]);

            throw new RuntimeException('Ticketmatic API returned status code ' . $response->getStatusCode());
        }

        $data = Json::decodeAssociatively((string) $response->getBody());

        return new ReservationLinkBookingInfo(
            sprintf('ticketmatic:%s:%d', $data['accountId'], $data['eventId']),
            $data['ticketsAvailable'] === true ? BookingAvailability::Available() : BookingAvailability::Unavailable(),
            isset($data['waitingListUrl']) ? new Url($data['waitingListUrl']) : null
        );
    }
}

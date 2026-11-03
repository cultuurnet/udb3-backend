<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing\Repositories;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class DBALTicketingRegistrationRepository implements TicketingRegistrationRepository
{
    private const TABLE = 'ticketing_registrations';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function add(TicketingRegistration $registration): void
    {
        try {
            $this->connection->insert(
                self::TABLE,
                [
                    'udb_event_id' => $registration->getEventId()->toString(),
                    'reservation_link' => $registration->getReservationLink()->toString(),
                    'identifier' => $registration->getIdentifier(),
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // The link is already stored for this event.
        }
    }

    public function remove(Uuid $eventId, Url $reservationLink): void
    {
        $this->connection->delete(
            self::TABLE,
            [
                'udb_event_id' => $eventId->toString(),
                'reservation_link' => $reservationLink->toString(),
            ]
        );
    }

    public function findByEvent(Uuid $eventId): TicketingRegistrations
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE)
            ->where('udb_event_id = :eventId')
            ->setParameter(':eventId', $eventId->toString())
            ->orderBy('reservation_link')
            ->execute()
            ->fetchAllAssociative();

        return $this->createRegistrations($rows);
    }

    public function findByIdentifier(string $identifier): TicketingRegistrations
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE)
            ->where('identifier = :identifier')
            ->setParameter(':identifier', $identifier)
            ->orderBy('udb_event_id')
            ->addOrderBy('reservation_link')
            ->execute()
            ->fetchAllAssociative();

        return $this->createRegistrations($rows);
    }

    public function findWithoutIdentifier(): TicketingRegistrations
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE)
            ->where('identifier IS NULL')
            ->orderBy('udb_event_id')
            ->addOrderBy('reservation_link')
            ->execute()
            ->fetchAllAssociative();

        return $this->createRegistrations($rows);
    }

    public function setIdentifier(Uuid $eventId, Url $reservationLink, string $identifier): void
    {
        $this->connection->update(
            self::TABLE,
            ['identifier' => $identifier],
            [
                'udb_event_id' => $eventId->toString(),
                'reservation_link' => $reservationLink->toString(),
            ]
        );
    }

    private function createRegistrations(array $rows): TicketingRegistrations
    {
        return new TicketingRegistrations(
            ...array_map(fn (array $row) => $this->createRegistration($row), $rows)
        );
    }

    private function createRegistration(array $row): TicketingRegistration
    {
        return new TicketingRegistration(
            new Uuid($row['udb_event_id']),
            new Url($row['reservation_link']),
            $row['identifier']
        );
    }
}

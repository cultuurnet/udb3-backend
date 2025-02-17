<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use DateTimeInterface;
use Doctrine\DBAL\Connection;

final class DBALMailsSentRepository implements MailsSentRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function isMailSent(Uuid $identifier, string $type): bool
    {
        return $this->connection->createQueryBuilder()
                ->select('*')
                ->from('mails_sent')
                ->andWhere('identifier = :identifier')
                ->andWhere('type = :type')
                ->setParameters(['identifier' => $identifier->toString(), 'type' => $type])
                ->execute()
                ->fetchOne()
            !== false;
    }

    public function addMailSent(Uuid $identifier, EmailAddress $email, string $type, DateTimeInterface $dateTime): void
    {
        $this->connection->insert('mails_sent', [
            'identifier' => $identifier->toString(),
            'email' => $email->toString(),
            'type' => $type,
            'dateTime' => $dateTime->format(DateTimeInterface::ATOM),
        ]);
    }
}

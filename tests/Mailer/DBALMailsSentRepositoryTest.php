<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class DBALMailsSentRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const DATE_TIME_VALUE = '2025-01-01T12:30:00+00:00';

    private DBALMailsSentRepository $repository;

    public function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new DBALMailsSentRepository($this->connection);
    }

    /** @test */
    public function handles_add_mail_sent(): void
    {
        $identifier = Uuid::uuid4();
        $email = new EmailAddress('koen@publiq.be');
        $type = OwnershipApproved::class;
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, self::DATE_TIME_VALUE);

        $this->repository->addMailSent($identifier, $email, $type, $date);

        $result = $this->connection->fetchAssociative(
            'SELECT * FROM mails_sent'
        );

        $this->assertNotNull($result);
        $this->assertEquals($identifier->toString(), $result['identifier']);
        $this->assertEquals($email->toString(), $result['email']);
        $this->assertEquals($type, $result['type']);
        $this->assertEquals($date->format('Y-m-d H:i:s'), $result['dateTime']);
    }

    /**
     * @dataProvider mailSentDataProvider
     * @test
     */
    public function handles_is_mail_sent(Uuid $identifier, string $type, Uuid $identifierMatched, string $typeMatched, bool $isSent): void
    {
        $email = new EmailAddress('koen@publiq.be');
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, self::DATE_TIME_VALUE);

        $this->connection->insert('mails_sent', [
            'identifier' => $identifier->toString(),
            'email' => $email->toString(),
            'type' => $type,
            'dateTime' => $date->format(DateTimeInterface::ATOM),
        ]);

        $this->assertEquals($isSent, $this->repository->isMailSent($identifierMatched, $typeMatched));
    }

    public function mailSentDataProvider(): array
    {
        $uuid = Uuid::uuid4();
        $uuid2 = Uuid::uuid4();

        return [
            [$uuid, OwnershipApproved::class, $uuid, OwnershipApproved::class, true],
            [$uuid2, OwnershipApproved::class, $uuid2, OwnershipRejected::class, false],
            [Uuid::uuid4(), OwnershipApproved::class, Uuid::uuid4(), OwnershipApproved::class, false],
        ];
    }
}

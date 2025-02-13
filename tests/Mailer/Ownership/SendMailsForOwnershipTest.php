<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendMailsForOwnershipTest extends TestCase
{
    private const DATE_TIME_VALUE = '2025-1-1T12:30:00+00:00';

    private SendMailsForOwnership $sendMailsForOwnership;

    /** @var DomainMessageSpecificationInterface|MockObject */
    private $domainMessageSpecification;
    /** @var Mailer|MockObject */
    private $mailer;
    /** @var DocumentRepository|MockObject */
    private $organizerRepository;
    /** @var UserIdentityResolver|MockObject */
    private $identityResolver;
    /** @var MailsSentRepository|MockObject */
    private $mailsSentRepository;
    private CallableIriGenerator $iriGenerator;

    protected function setUp(): void
    {
        $this->domainMessageSpecification = $this->createMock(DomainMessageSpecificationInterface::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->organizerRepository = $this->createMock(DocumentRepository::class);
        $this->identityResolver = $this->createMock(UserIdentityResolver::class);
        $this->mailsSentRepository = $this->createMock(MailsSentRepository::class);
        $this->iriGenerator = new CallableIriGenerator(
            fn (string $id) => 'http://localhost/organizers/' . $id
        );

        $this->sendMailsForOwnership = new SendMailsForOwnership(
            $this->domainMessageSpecification,
            $this->mailer,
            $this->organizerRepository,
            $this->identityResolver,
            $this->iriGenerator,
            $this->mailsSentRepository,
            $this->createMock(LoggerInterface::class),
            true
        );
    }

    /** @test
     * This is the happy path
     * */
    public function it_handles_ownership_requested_event(): void
    {
        $organizerName = 'Publiq VZW';
        $email = 'grotesmurf@publiq.be';
        $name = 'Grote smurf';

        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $organizerId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $event = new OwnershipRequested(
            $id,
            $organizerId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
        $domainMessage = (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($event);

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(false);

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(new JsonDocument($organizerId, json_encode(['name' => ['nl' => $organizerName]])));

        $this->identityResolver
            ->expects($this->once())
            ->method('getUserById')
            ->with($ownerId)
            ->willReturn(new UserIdentityDetails($ownerId, $name, $email));

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->willReturn(false);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with(
                new EmailAddress($email),
                'Beheers aanvraag voor organisatie ' . $organizerName,
                'approved.html.twig',
                'approved.txt.twig',
                [
                    'organisationName' => $organizerName,
                    'firstName' => $name,
                    'organisationUrl' => 'http://localhost/organizers/' . $organizerId,
                ]
            )
            ->willReturn(true);

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('addMailSent')
            ->with(
                new Uuid($id),
                new EmailAddress($email),
                OwnershipRequested::class,
                DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, self::DATE_TIME_VALUE)
            );

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    /** @test */
    public function it_blocks_replays(): void
    {
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $event = new OwnershipRequested(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $itemId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
        $domainMessage = (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($event);

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(true);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    /** @test */
    public function it_handles_organizer_does_not_exist(): void
    {
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $event = new OwnershipRequested(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $itemId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
        $domainMessage = (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($event);

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(false);

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($itemId)
            ->willThrowException(new DocumentDoesNotExist());

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    /** @test */
    public function it_handles_user_does_not_exist(): void
    {
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $event = new OwnershipRequested(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $itemId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
        $domainMessage = (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($event);

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(false);

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($itemId)
            ->willReturn(new JsonDocument($itemId, json_encode(['name' => ['nl' => 'Publiq VZW']])));

        $this->identityResolver
            ->expects($this->once())
            ->method('getUserById')
            ->with($ownerId)
            ->willReturn(null);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }

    /** @test */
    public function it_handles_mail_already_sent(): void
    {
        $itemId = '9e68dafc-01d8-4c1c-9612-599c918b981d';
        $ownerId = 'auth0|63e22626e39a8ca1264bd29b';

        $event = new OwnershipRequested(
            'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e',
            $itemId,
            'organizer',
            $ownerId,
            'google-oauth2|102486314601596809843'
        );
        $domainMessage = (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($event);

        $this->domainMessageSpecification
            ->expects($this->once())
            ->method('isSatisfiedBy')
            ->with($domainMessage)
            ->willReturn(false);

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->willReturn(true);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }
}

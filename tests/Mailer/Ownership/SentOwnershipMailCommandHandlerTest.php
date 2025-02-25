<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Mailer\Command\SentOwnershipRequestedMail;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;

class SentOwnershipMailCommandHandlerTest extends TestCase
{
    /** @var Mailer|MockObject */
    private $mailer;

    /** @var MailsSentRepository|MockObject */
    private $mailsSentRepository;
    private SentOwnershipMailCommandHandler $commandHandler;
    /** @var OwnershipSearchRepository|MockObject */
    private $ownershipSearchRepository;

    /** @var UserIdentityResolver|MockObject */
    private $identityResolver;

    /** @var TwigEnvironment|MockObject */
    private $twig;

    /** @var DocumentRepository|MockObject */
    private $organizerRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(Mailer::class);
        $this->mailsSentRepository = $this->createMock(MailsSentRepository::class);
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);
        $this->organizerRepository = $this->createMock(DocumentRepository::class);
        $this->twig = $this->createMock(TwigEnvironment::class);
        $this->identityResolver = $this->createMock(UserIdentityResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->commandHandler = new SentOwnershipMailCommandHandler(
            $this->mailer,
            $this->mailsSentRepository,
            $this->organizerRepository,
            $this->identityResolver,
            new CallableIriGenerator(
                fn (string $id) => 'http://localhost/organizers/' . $id
            ),
            $this->twig,
            $this->ownershipSearchRepository,
            $this->logger
        );
    }

    /** @test */
    public function it_can_sent_mail(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $ownerId = 'd6e21fa4-8d8d-4f23-b0cc-c63e34e43a01';
        $organizerId = 'd146a8cb-14c8-4364-9207-9d32d36f6959';

        $organizerName = 'Publiq VZW';
        $name = 'Grote smurf';

        $email = new EmailAddress('grotesmurf@publiq.be');
        $subject = 'Beheers aanvraag voor organisatie Publiq VZW';
        $html = '<p>body</p>';
        $text = 'body';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid($id), SentOwnershipRequestedMail::class)
            ->willReturn(false);

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('addMailSent')
            ->with(
                new Uuid($id),
                $email,
                SentOwnershipRequestedMail::class
            );

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn(new OwnershipItem(
                $id,
                $organizerId,
                'organizer',
                $ownerId,
                'requested'
            ));

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(new JsonDocument($organizerId, json_encode(['name' => ['nl' => $organizerName]])));

        $this->identityResolver
            ->expects($this->once())
            ->method('getUserById')
            ->with($ownerId)
            ->willReturn(new UserIdentityDetails($ownerId, $name, $email->toString()));

        $expectedParams = [
            'organisationName' => $organizerName,
            'firstName' => $name,
            'organisationUrl' => 'http://localhost/organizers/' . $organizerId,
        ];

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function (string $type, array $params) use ($expectedParams) {
                $this->assertEquals($expectedParams, $params);
                switch ($type) {
                    case 'ownershipRequested.html.twig':
                        return '<p>body</p>';
                    case 'ownershipRequested.txt.twig':
                        return 'body';
                    default:
                        $this->fail(sprintf('Type %s is unexpected', $type));
                }
            });

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with(
                $email,
                $subject,
                $html,
                $text
            )
            ->willReturn(true);

        $this->commandHandler->handle(new SentOwnershipRequestedMail($id));
    }

    /** @test */
    public function it_handles_mail_already_sent(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid($id), SentOwnershipRequestedMail::class)
            ->willReturn(true);

        $this->mailsSentRepository
            ->expects($this->never())
            ->method('addMailSent');

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->commandHandler->handle(new SentOwnershipRequestedMail($id));
    }

    /** @test */
    public function it_fails_when_it_cannot_find_ownership_request(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid($id), SentOwnershipRequestedMail::class)
            ->willReturn(false);

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willThrowException(OwnershipItemNotFound::byId($id));

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SentOwnershipRequestedMail($id));
    }

    /** @test */
    public function it_fails_when_organiser_is_not_found(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $ownerId = 'd6e21fa4-8d8d-4f23-b0cc-c63e34e43a01';
        $organizerId = 'd146a8cb-14c8-4364-9207-9d32d36f6959';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid($id), SentOwnershipRequestedMail::class)
            ->willReturn(false);

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn(new OwnershipItem(
                $id,
                $organizerId,
                'organizer',
                $ownerId,
                'requested'
            ));

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willThrowException(new DocumentDoesNotExist());

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SentOwnershipRequestedMail($id));
    }

    /** @test */
    public function it_fails_when_owner_details_lookup_fails(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $ownerId = 'd6e21fa4-8d8d-4f23-b0cc-c63e34e43a01';
        $organizerId = 'd146a8cb-14c8-4364-9207-9d32d36f6959';
        $organizerName = 'Publiq VZW';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid($id), SentOwnershipRequestedMail::class)
            ->willReturn(false);

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn(new OwnershipItem(
                $id,
                $organizerId,
                'organizer',
                $ownerId,
                'requested'
            ));

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturn(new JsonDocument($organizerId, json_encode(['name' => ['nl' => $organizerName]])));

        $this->identityResolver
            ->expects($this->once())
            ->method('getUserById')
            ->with($ownerId)
            ->willReturn(null);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SentOwnershipRequestedMail($id));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Mailer\Command\AbstractSendOwnershipMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipAcceptedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRejectedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
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

class SendOwnershipMailCommandHandlerTest extends TestCase
{
    private const OWNERSHOP_ITEM_ID = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
    private const CREATOR_ID = '67bf10cf-2ffc-8010-ad97-b4dbb123be9c';
    private const REQUESTER_ID = 'd6e21fa4-8d8d-4f23-b0cc-c63e34e43a01';
    private const ORGANIZER_ID = 'd146a8cb-14c8-4364-9207-9d32d36f6959';
    /** @var Mailer|MockObject */
    private $mailer;

    /** @var MailsSentRepository|MockObject */
    private $mailsSentRepository;
    private SendOwnershipMailCommandHandler $commandHandler;
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

        $this->commandHandler = new SendOwnershipMailCommandHandler(
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

    /**
     * @test
     * @dataProvider eventToCommandProvider
     */
    public function it_can_sent_mail(AbstractSendOwnershipMail $command, string $sendToUuid, string $subject): void
    {
        $organizerName = 'Publiq VZW';
        $name = 'Grote smurf';
        $email = new EmailAddress('grotesmurf@publiq.be');

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid(self::OWNERSHOP_ITEM_ID), get_class($command))
            ->willReturn(false);

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('addMailSent')
            ->with(
                new Uuid(self::OWNERSHOP_ITEM_ID),
                $email,
                get_class($command)
            );

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::OWNERSHOP_ITEM_ID)
            ->willReturn(new OwnershipItem(
                self::OWNERSHOP_ITEM_ID,
                self::ORGANIZER_ID,
                'organizer',
                self::REQUESTER_ID,
                'requested'
            ));

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with(self::ORGANIZER_ID)
            ->willReturn(new JsonDocument(self::ORGANIZER_ID, json_encode(['creator' => self::CREATOR_ID, 'name' => ['nl' => $organizerName]])));

        $this->identityResolver
            ->expects($this->once())
            ->method('getUserById')
            ->with($sendToUuid)
            ->willReturn(new UserIdentityDetails($sendToUuid, $name, $email->toString()));

        $expectedParams = [
            'organisationName' => $organizerName,
            'firstName' => $name,
            'organisationUrl' => 'http://localhost/organizers/' . self::ORGANIZER_ID,
        ];

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function (string $type, array $params) use ($expectedParams) {
                $this->assertEquals($expectedParams, $params);
                switch ($type) {
                    case 'ownershipRequested.html.twig':
                    case 'approved.html.twig':
                    case 'rejected.html.twig':
                        return '<p>body</p>';
                    case 'ownershipRequested.txt.twig':
                    case 'approved.txt.twig':
                    case 'rejected.txt.twig':
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
                '<p>body</p>',
                'body'
            )
            ->willReturn(true);

        $this->commandHandler->handle($command);
    }

    public function eventToCommandProvider(): array
    {
        return [
            'OwnershipRequested' => [
                new SendOwnershipRequestedMail(self::OWNERSHOP_ITEM_ID),
                self::CREATOR_ID,
                'Beheers aanvraag voor organisatie Publiq VZW',
            ],
            'OwnershipApproved' => [
                new SendOwnershipAcceptedMail(self::OWNERSHOP_ITEM_ID),
                self::REQUESTER_ID,
                'Je bent nu beheerder van organisatie Publiq VZW!',
            ],
            'OwnershipRejected' => [
                new SendOwnershipRejectedMail(self::OWNERSHOP_ITEM_ID),
                self::REQUESTER_ID,
                'Je beheersaanvraag voor organisatie Publiq VZW is geweigerd',
            ],
        ];
    }

    /** @test */
    public function it_handles_mail_already_sent(): void
    {
        $id = self::OWNERSHOP_ITEM_ID;

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid($id), SendOwnershipRequestedMail::class)
            ->willReturn(true);

        $this->mailsSentRepository
            ->expects($this->never())
            ->method('addMailSent');

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->commandHandler->handle(new SendOwnershipRequestedMail($id));
    }

    /** @test */
    public function it_fails_when_it_cannot_find_ownership_request(): void
    {
        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid(self::OWNERSHOP_ITEM_ID), SendOwnershipRequestedMail::class)
            ->willReturn(false);

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::OWNERSHOP_ITEM_ID)
            ->willThrowException(OwnershipItemNotFound::byId(self::OWNERSHOP_ITEM_ID));

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SendOwnershipRequestedMail(self::OWNERSHOP_ITEM_ID));
    }

    /** @test */
    public function it_fails_when_organiser_is_not_found(): void
    {
        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid(self::OWNERSHOP_ITEM_ID), SendOwnershipRequestedMail::class)
            ->willReturn(false);

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::OWNERSHOP_ITEM_ID)
            ->willReturn(new OwnershipItem(
                self::OWNERSHOP_ITEM_ID,
                self::ORGANIZER_ID,
                'organizer',
                self::REQUESTER_ID,
                'requested'
            ));

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with(self::ORGANIZER_ID)
            ->willThrowException(new DocumentDoesNotExist());

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SendOwnershipRequestedMail(self::OWNERSHOP_ITEM_ID));
    }

    /** @test */
    public function it_fails_when_owner_details_lookup_fails(): void
    {
        $organizerName = 'Publiq VZW';

        $this->mailsSentRepository
            ->expects($this->once())
            ->method('isMailSent')
            ->with(new Uuid(self::OWNERSHOP_ITEM_ID), SendOwnershipRequestedMail::class)
            ->willReturn(false);

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::OWNERSHOP_ITEM_ID)
            ->willReturn(new OwnershipItem(
                self::OWNERSHOP_ITEM_ID,
                self::ORGANIZER_ID,
                'organizer',
                self::REQUESTER_ID,
                'requested'
            ));

        $this->organizerRepository
            ->expects($this->once())
            ->method('fetch')
            ->with(self::ORGANIZER_ID)
            ->willReturn(new JsonDocument(self::ORGANIZER_ID, json_encode(['creator' => self::CREATOR_ID, 'name' => ['nl' => $organizerName]])));

        $this->identityResolver
            ->expects($this->once())
            ->method('getUserById')
            ->with(self::CREATOR_ID)
            ->willReturn(null);

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SendOwnershipRequestedMail(self::OWNERSHOP_ITEM_ID));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Handler;

use CultuurNet\UDB3\Mailer\Command\AbstractSendOwnershipMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipAcceptedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRejectedMail;
use CultuurNet\UDB3\Mailer\Command\SendOwnershipRequestedMail;
use CultuurNet\UDB3\Mailer\Handler\Helper\OwnershipMailParamExtractor;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Mailer\MailsSentRepository;
use CultuurNet\UDB3\Mailer\Ownership\RecipientStrategy\RecipientStrategy;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\User\Recipients;
use CultuurNet\UDB3\User\UserIdentityDetails;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;

class SendOwnershipMailCommandHandlerTest extends TestCase
{
    private const OWNERSHIP_ITEM_ID = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
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
    /** @var TwigEnvironment|MockObject */
    private $twig;
    /** @var OwnershipMailParamExtractor|MockObject */
    private $ownershipMailParamExtractor;
    /** @var RecipientStrategy|MockObject */
    private $sendToOwnersAndCreatorOfOrganisation;
    /** @var LoggerInterface|MockObject */
    private $sendToOwnerOfOwnership;
    /** @var LoggerInterface|MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(Mailer::class);
        $this->mailsSentRepository = $this->createMock(MailsSentRepository::class);
        $this->ownershipSearchRepository = $this->createMock(OwnershipSearchRepository::class);
        $this->twig = $this->createMock(TwigEnvironment::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->ownershipMailParamExtractor = $this->createMock(OwnershipMailParamExtractor::class);
        $this->sendToOwnersAndCreatorOfOrganisation = $this->createMock(RecipientStrategy::class);
        $this->sendToOwnerOfOwnership = $this->createMock(RecipientStrategy::class);

        $this->commandHandler = new SendOwnershipMailCommandHandler(
            $this->mailer,
            $this->mailsSentRepository,
            $this->twig,
            $this->ownershipSearchRepository,
            $this->ownershipMailParamExtractor,
            $this->sendToOwnersAndCreatorOfOrganisation,
            $this->sendToOwnerOfOwnership,
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
            ->method('addMailSent')
            ->with(
                new Uuid(self::OWNERSHIP_ITEM_ID),
                $email,
                get_class($command)
            );

        $ownershipItem = new OwnershipItem(
            self::OWNERSHIP_ITEM_ID,
            self::ORGANIZER_ID,
            'organizer',
            self::REQUESTER_ID,
            'requested'
        );
        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::OWNERSHIP_ITEM_ID)
            ->willReturn($ownershipItem);

        $userIdentityDetails = new UserIdentityDetails($sendToUuid, $name, $email->toString());

        if ($command instanceof SendOwnershipRequestedMail) {
            $this->sendToOwnersAndCreatorOfOrganisation
                ->expects($this->once())
                ->method('getRecipients')
                ->with($ownershipItem)
                ->willReturn(new Recipients($userIdentityDetails));
        } else {
            $this->sendToOwnerOfOwnership
                ->expects($this->once())
                ->method('getRecipients')
                ->with($ownershipItem)
                ->willReturn(new Recipients($userIdentityDetails));
        }

        $this->ownershipMailParamExtractor
            ->expects($this->once())
            ->method('fetchParams')
            ->with($ownershipItem, $userIdentityDetails)
            ->willReturn([
                'organisationName' => $organizerName,
                'firstName' => 'Grote smurf',
                'organisationUrl' => 'http://localhost/organizers/' . self::ORGANIZER_ID . '/preview',
            ]);

        $expectedParams = [
            'organisationName' => $organizerName,
            'firstName' => $name,
            'organisationUrl' => 'http://localhost/organizers/' . self::ORGANIZER_ID . '/preview',
        ];

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function (string $type, array $params) use ($expectedParams) {
                $this->assertEquals($expectedParams, $params);
                switch ($type) {
                    case 'ownership/requested.html.twig':
                    case 'ownership/approved.html.twig':
                    case 'ownership/rejected.html.twig':
                        return '<p>body</p>';
                    case 'ownership/requested.txt.twig':
                    case 'ownership/approved.txt.twig':
                    case 'ownership/rejected.txt.twig':
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

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(sprintf('[ownership-mail] Mail "%s" sent to %s', $subject, $email->toString()));

        $this->commandHandler->handle($command);
    }

    public function eventToCommandProvider(): array
    {
        return [
            'OwnershipRequested' => [
                new SendOwnershipRequestedMail(self::OWNERSHIP_ITEM_ID),
                self::CREATOR_ID,
                'Beheers aanvraag voor organisatie Publiq VZW',
            ],
            'OwnershipApproved' => [
                new SendOwnershipAcceptedMail(self::OWNERSHIP_ITEM_ID),
                self::REQUESTER_ID,
                'Je bent nu beheerder van organisatie Publiq VZW!',
            ],
            'OwnershipRejected' => [
                new SendOwnershipRejectedMail(self::OWNERSHIP_ITEM_ID),
                self::REQUESTER_ID,
                'Je beheersaanvraag voor organisatie Publiq VZW is geweigerd',
            ],
        ];
    }

    /** @test */
    public function it_fails_when_it_cannot_find_ownership_request(): void
    {
        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::OWNERSHIP_ITEM_ID)
            ->willThrowException(OwnershipItemNotFound::byId(self::OWNERSHIP_ITEM_ID));

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SendOwnershipRequestedMail(self::OWNERSHIP_ITEM_ID));
    }

    /** @test */
    public function it_fails_when_organiser_is_not_found(): void
    {
        $ownershipItem = new OwnershipItem(
            self::OWNERSHIP_ITEM_ID,
            self::ORGANIZER_ID,
            'organizer',
            self::REQUESTER_ID,
            'requested'
        );
        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with(self::OWNERSHIP_ITEM_ID)
            ->willReturn($ownershipItem);

        $userIdentityDetails = new UserIdentityDetails(self::CREATOR_ID, 'Grote smurf', 'info@publiq.be');

        $this->sendToOwnersAndCreatorOfOrganisation
            ->expects($this->once())
            ->method('getRecipients')
            ->with($ownershipItem)
            ->willReturn(new Recipients($userIdentityDetails));

        $this->ownershipMailParamExtractor
            ->expects($this->once())
            ->method('fetchParams')
            ->with($ownershipItem, $userIdentityDetails)
            ->willThrowException(new DocumentDoesNotExist());

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->logger->expects($this->once())
            ->method('warning');

        $this->commandHandler->handle(new SendOwnershipRequestedMail(self::OWNERSHIP_ITEM_ID));
    }

    /** @test */
    public function it_fails_when_owner_details_lookup_fails(): void
    {
        $id = 'e6e1f3a0-3e5e-4b3e-8e3e-3f3e3e3e3e3e';
        $ownerId = 'd6e21fa4-8d8d-4f23-b0cc-c63e34e43a01';
        $organizerId = 'd146a8cb-14c8-4364-9207-9d32d36f6959';

        $ownershipItem = new OwnershipItem(
            $id,
            $organizerId,
            'organizer',
            $ownerId,
            'requested'
        );

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($ownershipItem);

        $this->ownershipMailParamExtractor
            ->expects($this->never())
            ->method('fetchParams');

        $this->sendToOwnersAndCreatorOfOrganisation
            ->expects($this->once())
            ->method('getRecipients')
            ->with($ownershipItem)
            ->willReturn(new Recipients());

        $this->mailer
            ->expects($this->never())
            ->method('send');

        $this->commandHandler->handle(new SendOwnershipRequestedMail($id));
    }

    /** @test */
    public function it_gives_a_warning_when_mail_failed_to_sent(): void
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
            ->expects($this->never())
            ->method('addMailSent');

        $ownershipItem = new OwnershipItem(
            $id,
            $organizerId,
            'organizer',
            $ownerId,
            'requested'
        );

        $this->ownershipSearchRepository
            ->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($ownershipItem);

        $this->ownershipMailParamExtractor
            ->expects($this->once())
            ->method('fetchParams')
            ->willReturn([
                'organisationName' => $organizerName,
                'firstName' => 'Grote smurf',
                'organisationUrl' => 'http://localhost/organizers/' . $organizerId . '/preview',
            ]);

        $this->sendToOwnersAndCreatorOfOrganisation
            ->expects($this->once())
            ->method('getRecipients')
            ->with($ownershipItem)
            ->willReturn(new Recipients(new UserIdentityDetails($ownerId, $name, $email->toString())));

        $expectedParams = [
            'organisationName' => $organizerName,
            'firstName' => $name,
            'organisationUrl' => 'http://localhost/organizers/' . $organizerId . '/preview',
        ];

        $this->twig->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function (string $type, array $params) use ($expectedParams) {
                $this->assertEquals($expectedParams, $params);
                switch ($type) {
                    case 'ownership/requested.html.twig':
                        return '<p>body</p>';
                    case 'ownership/requested.txt.twig':
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
            ->willReturn(false);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(sprintf('[ownership-mail] Mail "%s" failed to sent to %s', $subject, $email->toString()));

        $this->commandHandler->handle(new SendOwnershipRequestedMail($id));
    }
}

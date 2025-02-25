<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Mailer\Ownership;

use CultuurNet\UDB3\Broadway\Domain\DomainMessageSpecificationInterface;
use CultuurNet\UDB3\CommandHandling\ContextDecoratedCommandBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Mailer\Command\SentOwnershipMail;
use CultuurNet\UDB3\Mailer\Mailer;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\User\UserIdentityDetails;
use CultuurNet\UDB3\User\UserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;

class SendMailsForOwnershipTest extends TestCase
{
    private const DATE_TIME_VALUE = '2025-1-1T12:30:00+00:00';

    private SendMailsForOwnership $sendMailsForOwnership;

    /** @var DomainMessageSpecificationInterface|MockObject */
    private $domainMessageSpecification;
    /** @var Mailer|MockObject */

    private $organizerRepository;
    /** @var UserIdentityResolver|MockObject */
    private $identityResolver;
    /** @var ContextDecoratedCommandBus|MockObject */
    private $commandBus;
    /** @var TwigEnvironment|MockObject */
    private $twig;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(ContextDecoratedCommandBus::class);
        $this->twig = $this->createMock(TwigEnvironment::class);
        $this->domainMessageSpecification = $this->createMock(DomainMessageSpecificationInterface::class);
        $this->organizerRepository = $this->createMock(DocumentRepository::class);
        $this->identityResolver = $this->createMock(UserIdentityResolver::class);
        $this->sendMailsForOwnership = new SendMailsForOwnership(
            $this->commandBus,
            $this->domainMessageSpecification,
            $this->organizerRepository,
            $this->identityResolver,
            new CallableIriGenerator(
                fn (string $id) => 'http://localhost/organizers/' . $id
            ),
            $this->twig,
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
                        return '<p>Email content</p>';
                    case 'ownershipRequested.txt.twig':
                        return 'Email content';
                    default:
                        $this->fail(sprintf('Type %s is unexpected', $type));
                }
            });

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(
                new SentOwnershipMail(
                    new Uuid($id),
                    new EmailAddress($email),
                    'Beheers aanvraag voor organisatie ' . $organizerName,
                    '<p>Email content</p>',
                    'Email content',
                )
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

        $this->commandBus->expects($this->never())->method('dispatch');

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

        $this->commandBus->expects($this->never())->method('dispatch');

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

        $this->commandBus->expects($this->never())->method('dispatch');

        $this->sendMailsForOwnership->handle(
            $domainMessage
        );
    }
}

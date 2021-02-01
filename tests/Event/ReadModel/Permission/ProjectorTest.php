<?php

namespace CultuurNet\UDB3\Event\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\OwnerChanged;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionRepositoryInterface;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class ProjectorTest extends TestCase
{
    /**
     * @var PermissionRepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var Projector
     */
    private $projector;

    /**
     * @var CreatedByToUserIdResolverInterface|MockObject
     */
    private $userIdResolver;

    public function setUp()
    {
        $this->repository = $this->createMock(PermissionRepositoryInterface::class);
        $this->userIdResolver = $this->createMock(CreatedByToUserIdResolverInterface::class);

        $this->projector = new Projector(
            $this->repository,
            $this->userIdResolver
        );
    }

    /**
     * @test
     */
    public function it_adds_permission_to_the_user_identified_by_the_createdby_element_for_events_imported_from_udb2()
    {
        $cdbXml = file_get_contents(__DIR__ . '/../../samples/event_with_photo.cdbxml.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2');

        $payload = new EventImportedFromUDB2(
            'dcd1ef37-0608-4824-afe3-99124feda64b',
            $cdbXml,
            $cdbXmlNamespaceUri
        );
        $msg = DomainMessage::recordNow(
            'dcd1ef37-0608-4824-afe3-99124feda64b',
            1,
            new Metadata(),
            $payload
        );

        $userId = new StringLiteral('123');

        $this->userIdResolver->expects($this->once())
            ->method('resolveCreatedByToUserId')
            ->with(new StringLiteral('gentonfiles@gmail.com'))
            ->willReturn($userId);

        $this->repository->expects($this->once())
            ->method('markOfferEditableByUser')
            ->with(
                new StringLiteral('dcd1ef37-0608-4824-afe3-99124feda64b'),
                $userId
            );

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_does_not_add_any_permissions_for_events_imported_from_udb2_with_unresolvable_createdby_value()
    {
        $cdbXml = file_get_contents(__DIR__ . '/../../samples/event_with_photo.cdbxml.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2');

        $payload = new EventImportedFromUDB2(
            'dcd1ef37-0608-4824-afe3-99124feda64b',
            $cdbXml,
            $cdbXmlNamespaceUri
        );
        $msg = DomainMessage::recordNow(
            'dcd1ef37-0608-4824-afe3-99124feda64b',
            1,
            new Metadata(),
            $payload
        );

        $this->userIdResolver->expects($this->once())
            ->method('resolveCreatedByToUserId')
            ->with(new StringLiteral('gentonfiles@gmail.com'))
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('markOfferEditableByUser');

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_add_permission_to_the_user_that_created_an_event()
    {
        $userId = new StringLiteral('user-id');
        $eventId = new StringLiteral('event-id');

        $payload = new EventCreated(
            $eventId->toNative(),
            new Language('nl'),
            new Title('test 123'),
            new EventType('0.50.4.0.0', 'concert'),
            new LocationId('395fe7eb-9bac-4647-acae-316b6446a85e'),
            new Calendar(
                CalendarType::PERMANENT()
            )
        );

        $msg = DomainMessage::recordNow(
            $eventId->toNative(),
            1,
            new Metadata(
                ['user_id' => $userId->toNative()]
            ),
            $payload
        );

        $this->repository->expects($this->once())
            ->method('markOfferEditableByUser')
            ->with(
                $eventId,
                $userId
            );

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_add_permission_to_the_user_that_copied_an_event()
    {
        $userId = 'user-id';
        $eventId = 'event-id';
        $originalEventId = 'original-event-id';

        $payload = new EventCopied(
            $eventId,
            $originalEventId,
            new Calendar(CalendarType::PERMANENT())
        );

        $msg = DomainMessage::recordNow(
            $eventId,
            1,
            new Metadata(['user_id' => $userId]),
            $payload
        );

        $this->repository->expects($this->once())
            ->method('markOfferEditableByUser')
            ->with(
                $eventId,
                $userId
            );

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_moves_permission_to_a_new_user_if_the_owner_changed(): void
    {
        $eventId = '9a18a42f-d80d-4784-8c34-8b8b36dd6080';
        $newOwnerId = '20656964-10cd-4ca7-85f2-997137479900';
        $ownerChanged = new OwnerChanged($eventId, $newOwnerId);

        // Set user_id in the metadata to a nil uuid because it's most likely this event will be recorded when running
        // a CLI command, which is run as the system user (= nil uuid).
        $domainMessage = DomainMessage::recordNow(
            $eventId,
            1,
            new Metadata(['user_id' => '00000000-0000-0000-0000-000000000000']),
            $ownerChanged
        );

        $this->repository->expects($this->once())
            ->method('markOfferEditableByNewUser')
            ->with(
                new StringLiteral($eventId),
                new StringLiteral($newOwnerId)
            );

        $this->projector->handle($domainMessage);
    }
}

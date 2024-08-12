<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Events\OwnerChanged;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\SampleFiles;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectorTest extends TestCase
{
    /**
     * @var ResourceOwnerRepository&MockObject
     */
    private $repository;

    private Projector $projector;

    /**
     * @var CreatedByToUserIdResolverInterface&MockObject
     */
    private $userIdResolver;

    public function setUp(): void
    {
        $this->repository = $this->createMock(ResourceOwnerRepository::class);
        $this->userIdResolver = $this->createMock(CreatedByToUserIdResolverInterface::class);

        $this->projector = new Projector(
            $this->repository,
            $this->userIdResolver
        );
    }

    /**
     * @test
     */
    public function it_adds_permission_to_the_user_identified_by_the_createdby_element_for_places_imported_from_udb2_actor(): void
    {
        $cdbXml = SampleFiles::read(__DIR__ . '/../../actor.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2');

        $payload = new PlaceImportedFromUDB2(
            '318F2ACB-F612-6F75-0037C9C29F44087A',
            $cdbXml,
            $cdbXmlNamespaceUri
        );
        $msg = DomainMessage::recordNow(
            '318F2ACB-F612-6F75-0037C9C29F44087A',
            1,
            new Metadata(),
            $payload
        );

        $userId = 'dcd1e123-0608-4824-afe3-99124feda64b';

        $this->userIdResolver->expects($this->once())
            ->method('resolveCreatedByToUserId')
            ->with('cultuurnet001')
            ->willReturn($userId);

        $this->repository->expects($this->once())
            ->method('markResourceEditableByUser')
            ->with(
                '318F2ACB-F612-6F75-0037C9C29F44087A',
                $userId
            );

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_does_not_add_any_permissions_for_actor_places_imported_from_udb2_with_unresolvable_createdby_value(): void
    {
        $cdbXml = SampleFiles::read(__DIR__ . '/../../actor.xml');
        $cdbXmlNamespaceUri = \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2');

        $payload = new PlaceImportedFromUDB2(
            '318F2ACB-F612-6F75-0037C9C29F44087A',
            $cdbXml,
            $cdbXmlNamespaceUri
        );
        $msg = DomainMessage::recordNow(
            '318F2ACB-F612-6F75-0037C9C29F44087A',
            1,
            new Metadata(),
            $payload
        );

        $this->userIdResolver->expects($this->once())
            ->method('resolveCreatedByToUserId')
            ->with('cultuurnet001')
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('markResourceEditableByUser');

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_add_permission_to_the_user_that_created_a_place(): void
    {
        $userId = 'user-id';
        $placeId = 'place-id';

        $payload = new PlaceCreated(
            $placeId,
            new Language('en'),
            'test 123',
            new EventType('0.50.4.0.0', 'concert'),
            new Address(
                new Street('Kerkstraat 69'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $msg = DomainMessage::recordNow(
            $placeId,
            1,
            new Metadata(
                ['user_id' => $userId]
            ),
            $payload
        );

        $this->repository->expects($this->once())
            ->method('markResourceEditableByUser')
            ->with(
                $placeId,
                $userId
            );

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_moves_permission_to_a_new_user_if_the_owner_changed(): void
    {
        $placeId = '9a18a42f-d80d-4784-8c34-8b8b36dd6080';
        $newOwnerId = '20656964-10cd-4ca7-85f2-997137479900';
        $ownerChanged = new OwnerChanged($placeId, $newOwnerId);

        // Set user_id in the metadata to a nil uuid because it's most likely this event will be recorded when running
        // a CLI command, which is run as the system user (= nil uuid).
        $domainMessage = DomainMessage::recordNow(
            $placeId,
            1,
            new Metadata(['user_id' => '00000000-0000-0000-0000-000000000000']),
            $ownerChanged
        );

        $this->repository->expects($this->once())
            ->method('markResourceEditableByNewUser')
            ->with(
                $placeId,
                $newOwnerId
            );

        $this->projector->handle($domainMessage);
    }
}

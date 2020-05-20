<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Place\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionRepositoryInterface;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
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
    public function it_adds_permission_to_the_user_identified_by_the_createdby_element_for_places_imported_from_udb2_actor()
    {
        $cdbXml = file_get_contents(__DIR__ . '/../../actor.xml');
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

        $userId = new StringLiteral('123');

        $this->userIdResolver->expects($this->once())
            ->method('resolveCreatedByToUserId')
            ->with(new StringLiteral('cultuurnet001'))
            ->willReturn($userId);

        $this->repository->expects($this->once())
            ->method('markOfferEditableByUser')
            ->with(
                new StringLiteral('318F2ACB-F612-6F75-0037C9C29F44087A'),
                $userId
            );

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_does_not_add_any_permissions_for_actor_places_imported_from_udb2_with_unresolvable_createdby_value()
    {
        $cdbXml = file_get_contents(__DIR__ . '/../../actor.xml');
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
            ->with(new StringLiteral('cultuurnet001'))
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('markOfferEditableByUser');

        $this->projector->handle($msg);
    }

    /**
     * @test
     */
    public function it_add_permission_to_the_user_that_created_a_place()
    {
        $userId = new StringLiteral('user-id');
        $placeId = new StringLiteral('place-id');

        $payload = new PlaceCreated(
            $placeId->toNative(),
            new Language('en'),
            new Title('test 123'),
            new EventType('0.50.4.0.0', 'concert'),
            new Address(
                new Street('Kerkstraat 69'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $msg = DomainMessage::recordNow(
            $placeId->toNative(),
            1,
            new Metadata(
                ['user_id' => $userId->toNative()]
            ),
            $payload
        );

        $this->repository->expects($this->once())
            ->method('markOfferEditableByUser')
            ->with(
                $placeId,
                $userId
            );

        $this->projector->handle($msg);
    }
}

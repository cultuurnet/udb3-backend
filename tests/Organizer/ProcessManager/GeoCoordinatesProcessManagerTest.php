<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ProcessManager;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Event\GeoCoordinatesProcessManager as GeoCoordinatesProcessManagerAlias;
use CultuurNet\UDB3\Organizer\Commands\UpdateGeoCoordinatesFromAddress;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class GeoCoordinatesProcessManagerTest extends TestCase
{
    /**
     * @var CommandBusInterface|MockObject
     */
    private $commandBus;

    /**
     * @var GeoCoordinatesProcessManagerAlias
     */
    private $processManager;

    public function setUp()
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->processManager = new GeoCoordinatesProcessManager($this->commandBus);
    }

    /**
     * @test
     */
    public function it_dispatches_update_command_for_address_updated_event(): void
    {
        $address = $this->anAddress();
        $organizerId = $this->anUuid();

        $addressUpdatedEvent = new AddressUpdated(
            $organizerId,
            $address
        );

        $domainMessage = DomainMessage::recordNow(
            $this->anUuid(),
            0,
            new Metadata(),
            $addressUpdatedEvent
        );

        $expectedCommand = new UpdateGeoCoordinatesFromAddress(
            $organizerId,
            $address
        );

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($expectedCommand);

        $this->processManager->handle($domainMessage);
    }

    public function anUuid(): string
    {
        return 'e3604613-af01-4d2b-8cee-13ab61b89651';
    }

    public function anAddress(): Address
    {
        return new Address(
            new Street('Martelarenplein 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );
    }
}

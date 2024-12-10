<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\DomainMessage;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class CompositeDomainMessageEnricherTest extends TestCase
{
    /**
     * @var DomainMessageEnricherInterface&MockObject
     */
    private $eventCreatedEnricher;

    /**
     * @var DomainMessageEnricherInterface&MockObject
     */
    private $placeCreatedEnricher;

    private CompositeDomainMessageEnricher $compositeEnricher;

    public function setUp(): void
    {
        $this->eventCreatedEnricher = $this->createMock(DomainMessageEnricherInterface::class);
        $this->eventCreatedEnricher->method('supports')
            ->willReturnCallback(
                function (DomainMessage $domainMessage) {
                    $payload = $domainMessage->getPayload();
                    return $payload instanceof EventCreated;
                }
            );

        $this->placeCreatedEnricher = $this->createMock(DomainMessageEnricherInterface::class);
        $this->placeCreatedEnricher->method('supports')
            ->willReturnCallback(
                function (DomainMessage $domainMessage) {
                    $payload = $domainMessage->getPayload();
                    return $payload instanceof PlaceCreated;
                }
            );

        $this->compositeEnricher = (new CompositeDomainMessageEnricher())
            ->withEnricher($this->eventCreatedEnricher)
            ->withEnricher($this->placeCreatedEnricher);
    }

    /**
     * @test
     */
    public function it_only_supports_domain_messages_supported_by_its_injected_enrichers(): void
    {
        $eventCreatedDomainMessage = $this->createEventCreatedDomainMessage();
        $placeCreatedDomainMessage = $this->createPlaceCreatedDomainMessage();
        $organizerCreatedDomainMessage = $this->createOrganizerCreatedDomainMessage();
        $organizerCreatedWithUniqueWebsiteDomainMessage = $this->createOrganizerCreatedWithUniqueWebsiteDomainMessage();

        $this->assertTrue($this->compositeEnricher->supports($eventCreatedDomainMessage));
        $this->assertTrue($this->compositeEnricher->supports($placeCreatedDomainMessage));
        $this->assertFalse($this->compositeEnricher->supports($organizerCreatedDomainMessage));
        $this->assertFalse($this->compositeEnricher->supports($organizerCreatedWithUniqueWebsiteDomainMessage));
    }

    /**
     * @test
     */
    public function it_delegates_enrichment_of_supported_domain_messages(): void
    {
        $eventCreatedDomainMessage = $this->createEventCreatedDomainMessage();
        $placeCreatedDomainMessage = $this->createPlaceCreatedDomainMessage();
        $organizerCreatedDomainMessage = $this->createOrganizerCreatedDomainMessage();
        $organizerCreatedWithUniqueWebsiteDomainMessage = $this->createOrganizerCreatedWithUniqueWebsiteDomainMessage();

        $enrichedEventCreatedDomainMessage = clone $eventCreatedDomainMessage;
        /** @phpstan-ignore-next-line */
        $enrichedEventCreatedDomainMessage->extraProperty = true;

        $enrichedPlaceCreatedDomainMessage = clone $placeCreatedDomainMessage;
        /** @phpstan-ignore-next-line */
        $enrichedPlaceCreatedDomainMessage->extraProperty = true;

        $this->eventCreatedEnricher->expects($this->once())
            ->method('enrich')
            ->with($eventCreatedDomainMessage)
            ->willReturn($enrichedEventCreatedDomainMessage);

        $this->placeCreatedEnricher->expects($this->once())
            ->method('enrich')
            ->with($placeCreatedDomainMessage)
            ->willReturn($enrichedPlaceCreatedDomainMessage);

        $this->assertEquals(
            $enrichedEventCreatedDomainMessage,
            $this->compositeEnricher->enrich($eventCreatedDomainMessage)
        );

        $this->assertEquals(
            $enrichedPlaceCreatedDomainMessage,
            $this->compositeEnricher->enrich($placeCreatedDomainMessage)
        );

        $this->assertEquals(
            $organizerCreatedDomainMessage,
            $this->compositeEnricher->enrich($organizerCreatedDomainMessage)
        );

        $this->assertEquals(
            $organizerCreatedDomainMessage,
            $this->compositeEnricher->enrich($organizerCreatedDomainMessage)
        );

        $this->assertEquals(
            $organizerCreatedWithUniqueWebsiteDomainMessage,
            $this->compositeEnricher->enrich($organizerCreatedWithUniqueWebsiteDomainMessage)
        );
    }

    private function createEventCreatedDomainMessage(): DomainMessage
    {
        return new DomainMessage(
            UUID::uuid4()->toString(),
            0,
            new Metadata(),
            new EventCreated(
                '97d50997-2f60-47f2-9861-05be747038fa',
                new Language('nl'),
                'test title',
                new Category(new CategoryID('0.0.1'), new CategoryLabel('label'), CategoryDomain::eventType()),
                new LocationId('8bec7ce3-25d0-4677-926f-ac20df8898f1'),
                new Calendar(CalendarType::permanent())
            ),
            DateTime::now()
        );
    }

    private function createPlaceCreatedDomainMessage(): DomainMessage
    {
        return new DomainMessage(
            UUID::uuid4()->toString(),
            0,
            new Metadata(),
            new PlaceCreated(
                'fd9e986d-6a23-470c-bf0c-4ad40aa4515e',
                new Language('nl'),
                'test title',
                new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
                new Address(
                    new Street('street'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    new CountryCode('BE')
                ),
                new Calendar(CalendarType::permanent())
            ),
            DateTime::now()
        );
    }

    private function createOrganizerCreatedDomainMessage(): DomainMessage
    {
        return new DomainMessage(
            UUID::uuid4()->toString(),
            0,
            new Metadata(),
            new OrganizerCreated(
                'fd9e986d-6a23-470c-bf0c-4ad40aa4515e',
                'test title',
                null,
                null,
                null,
                null,
                [],
                [],
                []
            ),
            DateTime::now()
        );
    }

    private function createOrganizerCreatedWithUniqueWebsiteDomainMessage(): DomainMessage
    {
        return new DomainMessage(
            UUID::uuid4()->toString(),
            0,
            new Metadata(),
            new OrganizerCreatedWithUniqueWebsite(
                'fd9e986d-6a23-470c-bf0c-4ad40aa4515e',
                'nl',
                'https://www.publiq.be',
                'test title'
            ),
            DateTime::now()
        );
    }
}

<?php

namespace CultuurNet\UDB3\EventListener;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\DomainMessage\DomainMessageEnricherInterface;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rhumsaa\Uuid\Uuid;
use ValueObjects\Geography\Country;

class EnrichingEventListenerDecoratorTest extends TestCase
{
    /**
     * @var DomainMessageEnricherInterface|MockObject
     */
    private $enricher;

    /**
     * @var EventListenerInterface|MockObject
     */
    private $decoratee;

    /**
     * @var EnrichingEventListenerDecorator
     */
    private $enrichingDecorator;

    public function setUp(): void
    {
        $this->enricher = $this->createMock(DomainMessageEnricherInterface::class);
        $this->decoratee = $this->createMock(EventListenerInterface::class);
        $this->enrichingDecorator = new EnrichingEventListenerDecorator($this->decoratee, $this->enricher);
    }

    /**
     * @test
     */
    public function it_enriches_supported_domain_messages_before_delegating_them_to_the_decoratee(): void
    {
        $supportedDomainMessage = new DomainMessage(
            Uuid::uuid4(),
            0,
            new Metadata(),
            new EventCreated(
                '97d50997-2f60-47f2-9861-05be747038fa',
                new Language('nl'),
                new Title('test title'),
                new EventType('0.0.1', 'label'),
                new LocationId('8bec7ce3-25d0-4677-926f-ac20df8898f1'),
                new Calendar(CalendarType::PERMANENT())
            ),
            DateTime::now()
        );

        $otherDomainMessage = new DomainMessage(
            Uuid::uuid4(),
            0,
            new Metadata(),
            new PlaceCreated(
                'fd9e986d-6a23-470c-bf0c-4ad40aa4515e',
                new Language('nl'),
                new Title('test title'),
                new EventType('0.0.1', 'label'),
                new Address(
                    new Street('street'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                ),
                new Calendar(CalendarType::PERMANENT())
            ),
            DateTime::now()
        );

        $enrichedDomainMessage = clone $supportedDomainMessage;
        /** @phpstan-ignore-next-line */
        $enrichedDomainMessage->extraProperty = true;

        $this->enricher->method('supports')
            ->willReturnCallback(
                function (DomainMessage $domainMessage) use ($supportedDomainMessage) {
                    return $domainMessage === $supportedDomainMessage;
                }
            );

        $this->enricher->expects($this->once())
            ->method('enrich')
            ->with($supportedDomainMessage)
            ->willReturn($enrichedDomainMessage);

        $this->decoratee->expects($this->exactly(2))
            ->method('handle')
            ->withConsecutive(
                [$enrichedDomainMessage],
                [$otherDomainMessage]
            );

        $this->enrichingDecorator->handle($supportedDomainMessage);
        $this->enrichingDecorator->handle($otherDomainMessage);
    }
}

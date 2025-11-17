<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Commands\UpdateUiTPASPrices;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Event\Event\PricesUpdated;
use CultuurNet\UDB3\UiTPAS\Event\Place\PlaceCardSystemsUpdated;
use CultuurNet\UDB3\UiTPAS\Label\UiTPASLabelsRepository;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EventProcessManagerTest extends TestCase
{
    private EventProcessManager $eventProcessManager;

    /**
     * @var object[]
     */
    private array $tracedCommands;

    private array $warningLogs;

    private array $infoLogs;

    public function setUp(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $uitpasLabelsRepository = $this->createMock(UiTPASLabelsRepository::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->eventProcessManager = new EventProcessManager(
            $commandBus,
            $uitpasLabelsRepository,
            $logger
        );

        $uitpasLabels = [
            'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa' => new Label(new LabelName('Paspartoe')),
            'ebd91df0-8ed7-4522-8401-ef5508ad1426' => new Label(new LabelName('UiTPAS')),
            'f23ccb75-190a-4814-945e-c95e83101cc5' => new Label(new LabelName('UiTPAS Gent')),
            '98ce6fbc-fb68-4efc-b8c7-95763cb967dd' => new Label(new LabelName('UiTPAS Oostende')),
            '68f849c0-bf55-4f73-b0f4-e0683bf0c807' => new Label(new LabelName('UiTPAS regio Aalst')),
            'cd6200cc-5b9d-43fd-9638-f6cc27f1c9b8' => new Label(new LabelName('UiTPAS Dender')),
            'd9cf96b6-1256-4760-b66b-1c31152d7db4' => new Label(new LabelName('UiTPAS Zuidwest')),
            'aaf3a58e-2aac-45b3-a9e9-3f3ebf467681' => new Label(new LabelName('UiTPAS Mechelen')),
            '47256d4c-47e8-4046-b9bb-acb166920f76' => new Label(new LabelName('UiTPAS Kempen')),
            '54b5273e-5e0b-4c1e-b33f-93eca55eb472' =>new Label(new LabelName('UiTPAS Maasmechelen')),
        ];

        $uitpasLabelsRepository->expects($this->any())
            ->method('loadAll')
            ->willReturn($uitpasLabels);

        $this->tracedCommands = [];

        $commandBus->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(
                function ($command): void {
                    $this->tracedCommands[] = $command;
                }
            );

        $logger->expects($this->any())
            ->method('warning')
            ->willReturnCallback(
                function ($msg): void {
                    $this->warningLogs[] = $msg;
                }
            );

        $logger->expects($this->any())
            ->method('info')
            ->willReturnCallback(
                function ($msg): void {
                    $this->infoLogs[] = $msg;
                }
            );
    }

    /**
     * @test
     */
    public function it_should_remove_every_uitpas_label_from_an_event_if_it_has_no_card_systems_after_an_update(): void
    {
        $eventId = new Id('cbee7413-ac1e-4dfb-8004-34767eafb8b7');

        $cardSystemsUpdated = new EventCardSystemsUpdated($eventId, []);

        $domainMessage = DomainMessage::recordNow(
            'cbee7413-ac1e-4dfb-8004-34767eafb8b7',
            7,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $expectedCommands = [
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'Paspartoe'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Gent'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Oostende'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS regio Aalst'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Dender'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Zuidwest'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Mechelen'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Kempen'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Maasmechelen'),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_add_uitpas_labels_for_active_card_systems_to_an_updated_event_with_card_systems(): void
    {
        $eventId = new Id('cbee7413-ac1e-4dfb-8004-34767eafb8b7');
        $cardSystems = [
            'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa' => new CardSystem(
                new Id('c73d78b7-95a7-45b3-bde5-5b2ec7b13afa'),
                'Mock CS Paspartoe'
            ),
            'f23ccb75-190a-4814-945e-c95e83101cc5' => new CardSystem(
                new Id('f23ccb75-190a-4814-945e-c95e83101cc5'),
                'Mock CS UiTPAS Gent'
            ),
            '98ce6fbc-fb68-4efc-b8c7-95763cb967dd' => new CardSystem(
                new Id('98ce6fbc-fb68-4efc-b8c7-95763cb967dd'),
                'Mock CS UiTPAS Oostende'
            ),
        ];

        $cardSystemsUpdated = new EventCardSystemsUpdated($eventId, $cardSystems);

        $domainMessage = DomainMessage::recordNow(
            $eventId->toNative(),
            8,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $expectedCommands = [
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS regio Aalst'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Dender'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Zuidwest'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Mechelen'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Kempen'),
            new RemoveLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', 'UiTPAS Maasmechelen'),
            new AddLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label(new LabelName('Paspartoe'), true)),
            new AddLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label(new LabelName('UiTPAS Gent'), true)),
            new AddLabel('cbee7413-ac1e-4dfb-8004-34767eafb8b7', new Label(new LabelName('UiTPAS Oostende'), true)),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_log_a_warning_if_no_label_can_be_found_for_an_active_card_system_on_an_event(): void
    {
        $eventId = new Id('cbee7413-ac1e-4dfb-8004-34767eafb8b7');
        $cardSystems = [7 => new CardSystem(new Id('7'), 'Mock CS')];

        $cardSystemsUpdated = new EventCardSystemsUpdated($eventId, $cardSystems);

        $domainMessage = DomainMessage::recordNow(
            $eventId->toNative(),
            8,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $this->eventProcessManager->handle($domainMessage);

        $this->assertContains(
            'Handling updated card systems message for event cbee7413-ac1e-4dfb-8004-34767eafb8b7',
            $this->infoLogs
        );

        $this->assertContains(
            'Could not find UiTPAS label for card system 7',
            $this->warningLogs
        );
    }

    /**
     * @test
     */
    public function it_should_remove_every_uitpas_label_from_a_place_if_it_has_no_card_systems_after_an_update(): void
    {
        $placeId = new Id('b99aa687-e965-4a88-936c-a568c586d979');

        $cardSystemsUpdated = new PlaceCardSystemsUpdated($placeId, []);

        $domainMessage = DomainMessage::recordNow(
            $placeId->toNative(),
            7,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $expectedCommands = [
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'Paspartoe'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Gent'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Oostende'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS regio Aalst'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Dender'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Zuidwest'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Mechelen'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Kempen'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Maasmechelen'),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_add_uitpas_labels_for_active_card_systems_to_an_updated_place_with_card_systems(): void
    {
        $placeId = new Id('b99aa687-e965-4a88-936c-a568c586d979');
        $cardSystems = [
            'c73d78b7-95a7-45b3-bde5-5b2ec7b13afa' => new CardSystem(
                new Id('c73d78b7-95a7-45b3-bde5-5b2ec7b13afa'),
                'Mock CS Paspartoe'
            ),
            'f23ccb75-190a-4814-945e-c95e83101cc5' => new CardSystem(
                new Id('f23ccb75-190a-4814-945e-c95e83101cc5'),
                'Mock CS UiTPAS Gent'
            ),
            '98ce6fbc-fb68-4efc-b8c7-95763cb967dd' => new CardSystem(
                new Id('98ce6fbc-fb68-4efc-b8c7-95763cb967dd'),
                'Mock CS UiTPAS Oostende'
            ),
        ];

        $cardSystemsUpdated = new PlaceCardSystemsUpdated($placeId, $cardSystems);

        $domainMessage = DomainMessage::recordNow(
            $placeId->toNative(),
            8,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $expectedCommands = [
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS regio Aalst'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Dender'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Zuidwest'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Mechelen'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Kempen'),
            new RemoveLabel('b99aa687-e965-4a88-936c-a568c586d979', 'UiTPAS Maasmechelen'),
            new AddLabel('b99aa687-e965-4a88-936c-a568c586d979', new Label(new LabelName('Paspartoe'), true)),
            new AddLabel('b99aa687-e965-4a88-936c-a568c586d979', new Label(new LabelName('UiTPAS Gent'), true)),
            new AddLabel('b99aa687-e965-4a88-936c-a568c586d979', new Label(new LabelName('UiTPAS Oostende'), true)),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }

    /**
     * @test
     */
    public function it_should_log_a_warning_if_no_label_can_be_found_for_an_active_card_system_on_a_place(): void
    {
        $placeId = new Id('b99aa687-e965-4a88-936c-a568c586d979');
        $cardSystems = [7 => new CardSystem(new Id('7'), 'Mock CS')];

        $cardSystemsUpdated = new PlaceCardSystemsUpdated($placeId, $cardSystems);

        $domainMessage = DomainMessage::recordNow(
            $placeId->toNative(),
            8,
            new Metadata([]),
            $cardSystemsUpdated
        );

        $this->eventProcessManager->handle($domainMessage);

        $this->assertContains(
            'Handling updated card systems message for place b99aa687-e965-4a88-936c-a568c586d979',
            $this->infoLogs
        );

        $this->assertContains(
            'Could not find UiTPAS label for card system 7',
            $this->warningLogs
        );
    }

    /**
     * @test
     */
    public function it_handles_prices_updated(): void
    {
        $eventId = new Id('cbee7413-ac1e-4dfb-8004-34767eafb8b7');

        $tariffs = new Tariffs(
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Tariff 1')
                ),
                new Money(
                    199,
                    new Currency('EUR')
                )
            ),
            new Tariff(
                new TranslatedTariffName(
                    new Language('nl'),
                    new TariffName('Tariff 2')
                ),
                new Money(
                    299,
                    new Currency('EUR')
                )
            )
        );

        $pricesUpdated = new PricesUpdated('cbee7413-ac1e-4dfb-8004-34767eafb8b7', $tariffs);

        $domainMessage = DomainMessage::recordNow(
            $eventId->toNative(),
            8,
            new Metadata([]),
            $pricesUpdated
        );

        $expectedCommands = [
            new UpdateUiTPASPrices('cbee7413-ac1e-4dfb-8004-34767eafb8b7', $tariffs),
        ];

        $this->eventProcessManager->handle($domainMessage);

        $this->assertEquals($expectedCommands, $this->tracedCommands);
    }
}

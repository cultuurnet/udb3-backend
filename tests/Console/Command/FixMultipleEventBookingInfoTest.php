<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingDateRange;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class FixMultipleEventBookingInfoTest extends TestCase
{
    private const EVENT_ID = '4d6f7e2a-1234-4b9f-8cb5-6ebd71445307';
    private const URL = 'https://example.com/tickets';
    private const STARTS = '2021-05-17T22:00:00+00:00';
    private const ENDS = '2021-09-17T22:00:00+00:00';

    private TraceableCommandBus $commandBus;

    private InMemoryDocumentRepository $eventDocumentRepository;

    private FixMultipleEventBookingInfo $command;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->eventDocumentRepository = new InMemoryDocumentRepository();

        $this->command = new FixMultipleEventBookingInfo(
            $this->commandBus,
            $this->eventDocumentRepository,
            new NullLogger()
        );
    }

    public function test_it_copies_the_sub_event_url_group_to_the_top_level_for_a_single_calendar_type(): void
    {
        $this->saveEvent([
            '@id' => 'https://io.uitdatabank.test/event/' . self::EVENT_ID,
            'calendarType' => 'single',
            'bookingInfo' => [
                'phone' => '016 12 34 56',
                'email' => 'info@example.com',
            ],
            'subEvent' => [
                [
                    'bookingInfo' => $this->subEventBookingInfo(),
                ],
            ],
        ]);

        $this->runCommand();

        $this->assertDispatchedBookingInfo($this->expectedBookingInfo());
    }

    public function test_it_copies_the_shared_sub_event_url_group_to_the_top_level_for_a_multiple_calendar_type(): void
    {
        $this->saveEvent([
            '@id' => 'https://io.uitdatabank.test/event/' . self::EVENT_ID,
            'calendarType' => 'multiple',
            'bookingInfo' => [
                'phone' => '016 12 34 56',
                'email' => 'info@example.com',
            ],
            'subEvent' => [
                ['bookingInfo' => $this->subEventBookingInfo()],
                ['bookingInfo' => $this->subEventBookingInfo()],
            ],
        ]);

        $this->runCommand();

        $this->assertDispatchedBookingInfo($this->expectedBookingInfo());
    }

    public function test_it_never_overwrites_a_filled_in_top_level_availability_window(): void
    {
        $topLevelStarts = '2030-01-01T00:00:00+00:00';
        $topLevelEnds = '2030-02-01T00:00:00+00:00';

        $this->saveEvent([
            '@id' => 'https://io.uitdatabank.test/event/' . self::EVENT_ID,
            'calendarType' => 'multiple',
            'bookingInfo' => [
                'phone' => '016 12 34 56',
                'email' => 'info@example.com',
                // url is empty, but the availability window is already set at the top level.
                'availabilityStarts' => $topLevelStarts,
                'availabilityEnds' => $topLevelEnds,
            ],
            'subEvent' => [
                ['bookingInfo' => $this->subEventBookingInfo()],
                ['bookingInfo' => $this->subEventBookingInfo()],
            ],
        ]);

        $this->runCommand();

        // The url and urlLabel are filled from the sub-event, but the existing top-level availability window is kept.
        $this->assertDispatchedBookingInfo(
            new BookingInfo(
                new WebsiteLink(
                    new Url(self::URL),
                    new TranslatedWebsiteLabel(new Language('nl'), new WebsiteLabel('Reserveer plaatsen'))
                ),
                new TelephoneNumber('016 12 34 56'),
                new EmailAddress('info@example.com'),
                new BookingDateRange(
                    DateTimeFactory::fromISO8601($topLevelStarts),
                    DateTimeFactory::fromISO8601($topLevelEnds)
                )
            )
        );
    }

    /**
     * The url, its label and its availability window all travel together from the sub-event.
     *
     * @return array<string, mixed>
     */
    private function subEventBookingInfo(): array
    {
        return [
            'url' => self::URL,
            'urlLabel' => ['nl' => 'Reserveer plaatsen'],
            'availabilityStarts' => self::STARTS,
            'availabilityEnds' => self::ENDS,
        ];
    }

    private function expectedBookingInfo(): BookingInfo
    {
        return new BookingInfo(
            new WebsiteLink(
                new Url(self::URL),
                new TranslatedWebsiteLabel(new Language('nl'), new WebsiteLabel('Reserveer plaatsen'))
            ),
            new TelephoneNumber('016 12 34 56'),
            new EmailAddress('info@example.com'),
            new BookingDateRange(
                DateTimeFactory::fromISO8601(self::STARTS),
                DateTimeFactory::fromISO8601(self::ENDS)
            )
        );
    }

    /**
     * @param array<string, mixed> $event
     */
    private function saveEvent(array $event): void
    {
        $this->eventDocumentRepository->save(new JsonDocument(self::EVENT_ID, Json::encode($event)));
    }

    private function runCommand(): void
    {
        $idsFile = tempnam(sys_get_temp_dir(), 'ids');
        file_put_contents($idsFile, self::EVENT_ID . PHP_EOL);

        $output = new BufferedOutput();
        $returnCode = $this->command->run(new ArrayInput(['file' => $idsFile]), $output);

        $this->assertSame(0, $returnCode, $output->fetch());
    }

    private function assertDispatchedBookingInfo(BookingInfo $expected): void
    {
        $commands = $this->commandBus->getRecordedCommands();

        $this->assertCount(1, $commands);
        $this->assertInstanceOf(UpdateBookingInfo::class, $commands[0]);
        $this->assertSame(self::EVENT_ID, $commands[0]->getItemId());

        $bookingInfo = $commands[0]->getBookingInfo();

        // Phone and email must be preserved from the top level, never overwritten.
        $this->assertNotNull($bookingInfo->getTelephoneNumber());
        $this->assertSame(
            $expected->getTelephoneNumber()->toString(),
            $bookingInfo->getTelephoneNumber()->toString()
        );
        $this->assertNotNull($bookingInfo->getEmailAddress());
        $this->assertSame(
            $expected->getEmailAddress()->toString(),
            $bookingInfo->getEmailAddress()->toString()
        );

        // sameAs() compares the full normalized bookingInfo, so this also covers url, urlLabel and the availability
        // window that control how/when the link is rendered.
        $this->assertTrue($bookingInfo->sameAs($expected));
    }
}

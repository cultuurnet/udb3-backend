<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifier;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultiple;
use CultuurNet\UDB3\Offer\Commands\AddLabelToQuery;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class BulkLabelCommandHandlerTest extends TestCase
{
    private ResultsGeneratorInterface&MockObject $resultGenerator;

    private LoggerInterface&MockObject $logger;

    private BulkLabelCommandHandler $commandHandler;

    private string $query;

    private Label $label;

    /**
     * @var IriOfferIdentifier[]
     */
    private array $offerIdentifiers;

    /**
     * @var ItemIdentifier[]
     */
    private array $itemIdentifiers;

    private CommandBus&MockObject $commandBus;

    protected function setUp(): void
    {
        $this->resultGenerator = $this->createMock(ResultsGeneratorInterface::class);
        $this->commandBus = $this->createMock(CommandBus::class);

        $this->commandHandler = new BulkLabelCommandHandler(
            $this->resultGenerator,
            $this->commandBus
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->commandHandler->setLogger($this->logger);

        $this->query = 'city:leuven';
        $this->label = new Label(new LabelName('foo'));

        $this->offerIdentifiers = [
            1 => new IriOfferIdentifier(
                new Url('http://du.de/event/1'),
                '1',
                OfferType::event()
            ),
            2 => new IriOfferIdentifier(
                new Url('http://du.de/place/2'),
                '2',
                OfferType::place()
            ),
        ];

        $this->itemIdentifiers = [
            1 => new ItemIdentifier(
                new Url('http://du.de/event/1'),
                '1',
                ItemType::event()
            ),
            2 => new ItemIdentifier(
                new Url('http://du.de/place/2'),
                '2',
                ItemType::place()
            ),
        ];
    }

    /**
     * @test
     */
    public function it_can_label_all_offer_results_from_a_query(): void
    {
        $addLabelToQuery = new AddLabelToQuery(
            $this->query,
            $this->label
        );

        $this->resultGenerator->expects($this->once())
            ->method('search')
            ->with($this->query)
            ->willReturnCallback(
                function (): \Traversable {
                    yield from $this->itemIdentifiers;
                }
            );

        $this->expectEventAndPlaceToBeLabelledWith($this->label);

        $this->commandHandler->handle($addLabelToQuery);
    }

    /**
     * @test
     */
    public function it_can_label_all_offers_from_a_selection(): void
    {
        $addLabelToMultiple = new AddLabelToMultiple(
            OfferIdentifierCollection::fromArray($this->offerIdentifiers),
            $this->label
        );

        $this->expectEventAndPlaceToBeLabelledWith($this->label);

        $this->commandHandler->handle($addLabelToMultiple);
    }

    /**
     * @test
     * @dataProvider exceptionDataProvider
     */
    public function it_logs_an_error_when_an_error_occurred_and_continues_labelling(
        \Exception $exception,
        string $exceptionClassName,
        string $message
    ): void {
        // One label action should fail.
        // Make sure the other offer is still labelled.
        $this->commandBus->method('dispatch')->withConsecutive(
            [new AddLabel($this->offerIdentifiers[1]->getId(), $this->label)],
            [new AddLabel($this->offerIdentifiers[2]->getId(), $this->label)]
        )->will(
            $this->onConsecutiveCalls(
                $this->throwException($exception),
                false
            )
        );

        // Make sure we log the occur.
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'bulk_label_command_exception',
                [
                    'iri' => 'http://du.de/event/1',
                    'command' => AddLabelToMultiple::class,
                    'exception' => $exceptionClassName,
                    'message' => $message,
                ]
            );

        $this->commandHandler->handle(
            new AddLabelToMultiple(
                OfferIdentifierCollection::fromArray($this->offerIdentifiers),
                $this->label
            )
        );
    }

    public function exceptionDataProvider(): array
    {
        return [
            [
                new \Exception('Something went awfully wrong'),
                \Exception::class,
                'Something went awfully wrong',
            ],
        ];
    }


    private function expectEventAndPlaceToBeLabelledWith(Label $label): void
    {
        $this->commandBus->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [new AddLabel($this->offerIdentifiers[1]->getId(), $label)],
                [new AddLabel($this->offerIdentifiers[2]->getId(), $label)]
            );
    }
}

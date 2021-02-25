<?php

namespace CultuurNet\UDB3\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultiple;
use CultuurNet\UDB3\Offer\Commands\AddLabelToQuery;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ValueObjects\Web\Url;

class BulkLabelCommandHandlerTest extends TestCase
{
    /**
     * @var ResultsGeneratorInterface|MockObject
     */
    private $resultGenerator;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var BulkLabelCommandHandler
     */
    private $commandHandler;

    /**
     * @var string
     */
    private $query;

    /**
     * @var Label
     */
    private $label;

    /**
     * @var IriOfferIdentifier[]
     */
    private $offerIdentifiers;

    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

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
        $this->label = new Label('foo');

        $this->offerIdentifiers = [
            1 => new IriOfferIdentifier(
                Url::fromNative('http://du.de/event/1'),
                '1',
                OfferType::EVENT()
            ),
            2 => new IriOfferIdentifier(
                Url::fromNative('http://du.de/place/2'),
                '2',
                OfferType::PLACE()
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
                function () {
                    yield from $this->offerIdentifiers;
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
        $this->commandBus->expects($this->at(0))
            ->method('dispatch')
            ->with(new AddLabel($this->offerIdentifiers[1]->getId(), $this->label))
            ->willThrowException($exception);

        // Make sure the other offer is still labelled.
        $this->commandBus->expects($this->at(1))
            ->method('dispatch')
            ->with(new AddLabel($this->offerIdentifiers[2]->getId(), $this->label));

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

    /**
     * @param Label $label
     */
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

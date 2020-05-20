<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Label;
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
     * @var ExternalOfferEditingServiceInterface|MockObject
     */
    private $externalOfferEditingService;

    public function setUp()
    {
        $this->resultGenerator = $this->createMock(ResultsGeneratorInterface::class);
        $this->externalOfferEditingService = $this->createMock(ExternalOfferEditingServiceInterface::class);

        $this->commandHandler = new BulkLabelCommandHandler(
            $this->resultGenerator,
            $this->externalOfferEditingService
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
    public function it_can_label_all_offer_results_from_a_query()
    {
        $addLabelToQuery = new AddLabelToQuery(
            $this->query,
            $this->label
        );

        $this->resultGenerator->expects($this->once())
            ->method('search')
            ->with($this->query)
            ->willReturn($this->offerIdentifiers);

        $this->expectEventAndPlaceToBeLabelledWith($this->label);

        $this->commandHandler->handle($addLabelToQuery);
    }

    /**
     * @test
     */
    public function it_can_label_all_offers_from_a_selection()
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
     *
     * @param \Exception $exception
     * @param string $exceptionClassName
     * @param string $message
     */
    public function it_logs_an_error_when_an_error_occurred_and_continues_labelling(
        \Exception $exception,
        $exceptionClassName,
        $message
    ) {
        // One label action should fail.
        $this->externalOfferEditingService->expects($this->at(0))
            ->method('addLabel')
            ->with(
                $this->offerIdentifiers[1],
                $this->label
            )
            ->willThrowException($exception);

        // Make sure the other offer is still labelled.
        $this->externalOfferEditingService->expects($this->at(1))
            ->method('addLabel')
            ->with(
                $this->offerIdentifiers[2],
                $this->label
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

    /**
     * @return array
     */
    public function exceptionDataProvider()
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
    private function expectEventAndPlaceToBeLabelledWith(Label $label)
    {
        $this->externalOfferEditingService->expects($this->exactly(2))
            ->method('addLabel')
            ->withConsecutive(
                [
                    $this->offerIdentifiers[1],
                    $label,
                ],
                [
                    $this->offerIdentifiers[2],
                    $label,
                ]
            );
    }
}

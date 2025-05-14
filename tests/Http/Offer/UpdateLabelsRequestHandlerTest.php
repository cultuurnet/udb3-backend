<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use PHPUnit\Framework\TestCase;

class UpdateLabelsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private UpdateLabelsRequestHandler $updateLabelsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = new TraceableCommandBus();

        $this->updateLabelsRequestHandler = new UpdateLabelsRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_adding_labels_to_an_offer(): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'event')
            ->withRouteParameter('offerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->withJsonBodyFromArray(['labels' => ['label1', 'label2']])
            ->build('PUT');

        $response = $this->updateLabelsRequestHandler->handle($updateLabelsRequest);

        $this->assertEquals(
            [
                new ImportLabels(
                    'd2a039e9-f4d6-4080-ae33-a106b5d3d47b',
                    new Labels(
                        new Label(new LabelName('label1')),
                        new Label(new LabelName('label2'))
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(new NoContentResponse(), $response);
    }
}

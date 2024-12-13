<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Commands\AddLabelToQuery;
use PHPUnit\Framework\TestCase;

final class AddLabelToQueryRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private AddLabelToQueryRequestHandler $addLabelToQueryRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->addLabelToQueryRequestHandler = new AddLabelToQueryRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_adding_label_via_a_query(): void
    {
        $addLabelToQueryRequest = $this->psr7RequestBuilder
            ->withJsonBodyFromArray([
                'query' => 'Dansvoorstellingen',
                'label' => 'Dansje',
            ])
            ->build('POST');

        $response = $this->addLabelToQueryRequestHandler->handle($addLabelToQueryRequest);

        $this->assertEquals(
            [
                new AddLabelToQuery(
                    'Dansvoorstellingen',
                    new Label(
                        new LabelName('Dansje')
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new JsonResponse(['commandId' => Uuid::NIL]),
            $response
        );
    }
}

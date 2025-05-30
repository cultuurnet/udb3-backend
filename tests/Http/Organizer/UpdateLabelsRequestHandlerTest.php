<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Organizer\Commands\ReplaceLabels;
use PHPUnit\Framework\TestCase;

class UpdateLabelsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

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
    public function it_handles_adding_labels_to_an_organizer(): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->withJsonBodyFromArray(['labels' => ['label1', 'label2']])
            ->build('PUT');

        $response = $this->updateLabelsRequestHandler->handle($updateLabelsRequest);

        $this->assertEquals(
            [
                new ReplaceLabels(
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

    /**
     * @test
     */
    public function it_allows_empty_label_list(): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->withJsonBodyFromArray(['labels' => []])
            ->build('PUT');

        $response = $this->updateLabelsRequestHandler->handle($updateLabelsRequest);

        $this->assertEquals(
            [
                new ReplaceLabels(
                    'd2a039e9-f4d6-4080-ae33-a106b5d3d47b',
                    new Labels()
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(new NoContentResponse(), $response);
    }

    /**
     * @test
     */
    public function it_throws_if_body_is_missing(): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateLabelsRequestHandler->handle($updateLabelsRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_labels_are_missing(): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->withJsonBodyFromArray(['wrong' => ['label1', 'label2']])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (labels) are missing'),
            ),
            fn () => $this->updateLabelsRequestHandler->handle($updateLabelsRequest)
        );
    }
}

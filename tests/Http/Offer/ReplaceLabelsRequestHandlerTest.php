<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

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
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use PHPUnit\Framework\TestCase;

class ReplaceLabelsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private ReplaceLabelsRequestHandler $updateLabelsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = new TraceableCommandBus();

        $this->updateLabelsRequestHandler = new ReplaceLabelsRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeProvider
     */
    public function it_handles_adding_labels_to_an_offer(string $offerType): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
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

    /**
     * @test
     * @dataProvider offerTypeProvider
     */
    public function it_throws_if_body_is_missing(string $offerType): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->updateLabelsRequestHandler->handle($updateLabelsRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeProvider
     */
    public function it_throws_if_labels_is_missing(string $offerType): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->withJsonBodyFromArray(['wrong' => ['label1', 'label2']])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'The required properties (labels) are missing'),
            ),
            fn () => $this->updateLabelsRequestHandler->handle($updateLabelsRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeProvider
     */
    public function it_throws_if_no_labels_are_provided(string $offerType): void
    {
        $updateLabelsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b')
            ->withJsonBodyFromArray(['labels' => []])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/labels', 'Array should have at least 1 items, 0 found'),
            ),
            fn () => $this->updateLabelsRequestHandler->handle($updateLabelsRequest)
        );
    }

    public function offerTypeProvider(): array
    {
        return [
            'events' => ['events'],
            'places' => ['places'],
        ];
    }
}

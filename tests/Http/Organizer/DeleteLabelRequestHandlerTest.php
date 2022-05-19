<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use PHPUnit\Framework\TestCase;

class DeleteLabelRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private DeleteLabelRequestHandler $deleteLabelRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->deleteLabelRequestHandler = new DeleteLabelRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_label(): void
    {
        $removeLabelRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('labelName', 'MyLabel')
            ->build('DELETE');

        $this->deleteLabelRequestHandler->handle($removeLabelRequest);

        $this->assertEquals(
            [
                new RemoveLabel(
                    'a088f396-ac96-45c4-b6b2-e2b6afe8af07',
                    new Label(new LabelName('MyLabel'))
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_requires_a_valid_label_name(): void
    {
        $removeLabelRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('labelName', 'Invalid;Label')
            ->build('DELETE');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('The label should match pattern: ^[^;]{2,255}$'),
            fn () => $this->deleteLabelRequestHandler->handle($removeLabelRequest)
        );
    }
}

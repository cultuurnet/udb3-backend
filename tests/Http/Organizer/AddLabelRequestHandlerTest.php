<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Organizer\Commands\AddLabel;
use PHPUnit\Framework\TestCase;

class AddLabelRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private AddLabelRequestHandler $addLabelRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->addLabelRequestHandler = new AddLabelRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_adding_a_label(): void
    {
        $addLabelRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('labelName', 'MyLabel')
            ->build('PUT');

        $this->addLabelRequestHandler->handle($addLabelRequest);

        $this->assertEquals(
            [
                new AddLabel(
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
        $addLabelRequest = $this->psr7RequestBuilder
            ->withRouteParameter('organizerId', 'a088f396-ac96-45c4-b6b2-e2b6afe8af07')
            ->withRouteParameter('labelName', 'Invalid;Label')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::pathParameterInvalid('The label should match pattern: ^[^;]{2,255}$'),
            fn () => $this->addLabelRequestHandler->handle($addLabelRequest)
        );
    }
}

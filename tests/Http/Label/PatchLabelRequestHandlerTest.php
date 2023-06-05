<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Label\Commands\AbstractCommand;
use CultuurNet\UDB3\Label\Commands\ExcludeLabel;
use CultuurNet\UDB3\Label\Commands\IncludeLabel;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;

final class PatchLabelRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $traceableCommandBus;

    private PatchLabelRequestHandler $patchLabelRequestHandler;

    protected function setUp(): void
    {
        $this->traceableCommandBus = new TraceableCommandBus();

        $this->patchLabelRequestHandler = new PatchLabelRequestHandler($this->traceableCommandBus, );
    }

    /**
     * @test
     * @dataProvider patchLabelDataProvider
     */
    public function it_can_patch_labels(CommandType $commandType, AbstractCommand $command): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('labelId', '9714108c-dddc-4105-a736-2e32632999f4')
            ->withJsonBodyFromArray([
                'command' => $commandType->toString(),
            ])
            ->build('PATCH');

        $this->traceableCommandBus->record();
        $response = $this->patchLabelRequestHandler->handle($request);

        $this->assertJsonResponse(new NoContentResponse(), $response);
        $this->assertEquals([$command], $this->traceableCommandBus->getRecordedCommands());
    }

    public function patchLabelDataProvider(): array
    {
        return [
            'makeVisible' => [
                'command' => CommandType::makeVisible(),
                'expectedCommand' => new MakeVisible(new UUID('9714108c-dddc-4105-a736-2e32632999f4')),
            ],
            'makeInvisible' => [
                'command' => CommandType::makeInvisible(),
                'expectedCommand' => new MakeInvisible(new UUID('9714108c-dddc-4105-a736-2e32632999f4')),
            ],
            'makePublic' => [
                'command' => CommandType::makePublic(),
                'expectedCommand' => new MakePublic(new UUID('9714108c-dddc-4105-a736-2e32632999f4')),
            ],
            'makePrivate' => [
                'command' => CommandType::makePrivate(),
                'expectedCommand' => new MakePrivate(new UUID('9714108c-dddc-4105-a736-2e32632999f4')),
            ],
            'include' => [
                'command' => CommandType::include(),
                'expectedCommand' => new IncludeLabel(new UUID('9714108c-dddc-4105-a736-2e32632999f4')),
            ],
            'exclude' => [
                'command' => CommandType::exclude(),
                'expectedCommand' => new ExcludeLabel(new UUID('9714108c-dddc-4105-a736-2e32632999f4')),
            ],
        ];
    }
}

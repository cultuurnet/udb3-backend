<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\UuidGenerator\Testing\MockUuidGenerator;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

final class CreateLabelRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private TraceableCommandBus $commandBus;

    private CreateLabelRequestHandler $createLabelRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->createLabelRequestHandler = new CreateLabelRequestHandler(
            $this->commandBus,
            new MockUuidGenerator('9714108c-dddc-4105-a736-2e32632999f4')
        );
    }

    /**
     * @test
     */
    public function it_can_create_a_label(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray([
                'name' => 'test-label-name',
                'visibility' => 'invisible',
                'privacy' => 'private',
            ])
            ->build('POST');

        $this->commandBus->record();
        $response = $this->createLabelRequestHandler->handle($request);

        $this->assertJsonResponse(
            new JsonResponse(['uuid' => '9714108c-dddc-4105-a736-2e32632999f4']),
            $response
        );
        $this->assertEquals(
            [
                new Create(
                    new Uuid('9714108c-dddc-4105-a736-2e32632999f4'),
                    new LabelName('test-label-name'),
                    Visibility::invisible(),
                    Privacy::private()
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }
}

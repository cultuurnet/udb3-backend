<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Offer\Commands\AddLabelToQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

class CommandDeserializerControllerTest extends TestCase
{
    /**
     * @var DeserializerInterface|MockObject
     */
    private $deserializer;

    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

    /**
     * @var CommandDeserializerController
     */
    private $controller;

    public function setUp()
    {
        $this->deserializer = $this->createMock(DeserializerInterface::class);
        $this->commandBus = $this->createMock(CommandBus::class);

        $this->controller = new CommandDeserializerController(
            $this->deserializer,
            $this->commandBus
        );
    }

    /**
     * @test
     */
    public function it_deserializes_a_command_and_dispatches_it_on_the_command_bus()
    {
        $json = new StringLiteral('{"foo": "bar"}');
        $command = new AddLabelToQuery('foo:bar', new Label('foo'));

        $request = new Request([], [], [], [], [], [], $json->toNative());

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with($json)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($command) {
                $command->setAsyncCommandId('i-command-id');
            });

        $response = $this->controller->handle($request);

        $content = $response->getContent();
        $this->assertEquals('{"commandId":"i-command-id"}', $content);
    }
}

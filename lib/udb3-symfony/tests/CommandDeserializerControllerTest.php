<?php

namespace CultuurNet\UDB3\Symfony;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DeserializerInterface;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;

class CommandDeserializerControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DeserializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deserializer;

    /**
     * @var CommandBusInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandBus;

    /**
     * @var CommandDeserializerController
     */
    private $controller;

    public function setUp()
    {
        $this->deserializer = $this->createMock(DeserializerInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);

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
        $command = new stdClass();

        $request = new Request([], [], [], [], [], [], $json->toNative());

        $this->deserializer->expects($this->once())
            ->method('deserialize')
            ->with($json)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willReturn('i-command-id');

        $response = $this->controller->handle($request);

        $content = $response->getContent();
        $this->assertEquals('{"commandId":"i-command-id"}', $content);
    }
}

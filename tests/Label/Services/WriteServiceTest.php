<?php

namespace CultuurNet\UDB3\Label\Services;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class WriteServiceTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var Create
     */
    private $create;

    /**
     * @var CommandBusInterface|MockObject
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    private $uuidGenerator;

    /**
     * @var WriteService
     */
    private $writeService;

    protected function setUp()
    {
        $this->uuid = new UUID();

        $this->create = new Create(
            $this->uuid,
            new LabelName('labelName'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->commandBus = $this->createMock(CommandBusInterface::class);

        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->uuidGenerator->method('generate')
            ->willReturn($this->create->getUuid()->toNative());

        $this->writeService = new WriteService(
            $this->commandBus,
            $this->uuidGenerator
        );
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_create_command_for_create()
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->create);

        $uuid = $this->writeService->create(
            $this->create->getName(),
            $this->create->getVisibility(),
            $this->create->getPrivacy()
        );

        $this->assertEquals($this->uuid, $uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_visible_command_for_make_visible()
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakeVisible($this->uuid));

        $this->writeService->makeVisible($this->uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_invisible_command_for_make_invisible()
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakeInvisible($this->uuid));

        $this->writeService->makeInvisible($this->uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_public_command_for_make_public()
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakePublic($this->uuid));

        $this->writeService->makePublic($this->uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_private_command_for_make_private()
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakePrivate($this->uuid));

        $this->writeService->makePrivate($this->uuid);
    }
}

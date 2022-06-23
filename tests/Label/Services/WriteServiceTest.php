<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Services;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Label\Commands\Create;
use CultuurNet\UDB3\Label\Commands\MakeInvisible;
use CultuurNet\UDB3\Label\Commands\MakePrivate;
use CultuurNet\UDB3\Label\Commands\MakePublic;
use CultuurNet\UDB3\Label\Commands\MakeVisible;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WriteServiceTest extends TestCase
{
    private UUID $uuid;

    private Create $create;

    /**
     * @var CommandBus|MockObject
     */
    private $commandBus;

    private WriteService $writeService;

    protected function setUp(): void
    {
        $this->uuid = new UUID('91a6cfb3-f556-48cd-91ef-b0675b827728');

        $this->create = new Create(
            $this->uuid,
            new LabelName('labelName'),
            Visibility::INVISIBLE(),
            Privacy::PRIVACY_PRIVATE()
        );

        $this->commandBus = $this->createMock(CommandBus::class);

        $uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $uuidGenerator->method('generate')
            ->willReturn($this->create->getUuid()->toString());

        $this->writeService = new WriteService(
            $this->commandBus,
            $uuidGenerator
        );
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_create_command_for_create(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->create);

        $uuid = $this->writeService->create(
            new LegacyLabelName($this->create->getName()->toString()),
            $this->create->getVisibility(),
            $this->create->getPrivacy()
        );

        $this->assertEquals($this->uuid, $uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_visible_command_for_make_visible(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakeVisible($this->uuid));

        $this->writeService->makeVisible($this->uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_invisible_command_for_make_invisible(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakeInvisible($this->uuid));

        $this->writeService->makeInvisible($this->uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_public_command_for_make_public(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakePublic($this->uuid));

        $this->writeService->makePublic($this->uuid);
    }

    /**
     * @test
     */
    public function it_calls_dispatch_with_make_private_command_for_make_private(): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(new MakePrivate($this->uuid));

        $this->writeService->makePrivate($this->uuid);
    }
}

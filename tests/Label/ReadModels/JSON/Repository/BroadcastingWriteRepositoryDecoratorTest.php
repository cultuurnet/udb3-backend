<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BroadcastingWriteRepositoryDecoratorTest extends TestCase
{
    private BroadcastingWriteRepositoryDecorator $broadcastingWriteRepositoryDecorator;

    /**
     * @var EventBus&MockObject
     */
    private $eventBus;

    /**
     * @var WriteRepositoryInterface&MockObject
     */
    private $writeRepository;

    public function setUp(): void
    {
        $this->writeRepository = $this->createMock(WriteRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->broadcastingWriteRepositoryDecorator = new BroadcastingWriteRepositoryDecorator(
            $this->writeRepository,
            $this->eventBus
        );
    }

    /**
     * @test
     */
    public function it_does_not_broadcast_on_save(): void
    {
        $uuid = new UUID('eea246d1-4f50-4879-8f52-42867ed51670');
        $name = 'labelName';
        $visibility = new Visibility('invisible');
        $privacy = new Privacy('private');

        $this->writeRepository
            ->expects($this->once())
            ->method('save')
            ->with(
                $uuid,
                $name,
                $visibility,
                $privacy
            );

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $this->broadcastingWriteRepositoryDecorator->save(
            $uuid,
            $name,
            $visibility,
            $privacy
        );
    }

    /**
     * @test
     */
    public function it_does_broadcast_on_update_private(): void
    {
        $uuid = new UUID('17bcae0c-ac05-4da9-9883-421b5a8fc666');

        $this->writeRepository
            ->expects($this->once())
            ->method('updatePrivate')
            ->with(
                $uuid
            );

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        $this->broadcastingWriteRepositoryDecorator->updatePrivate(
            $uuid
        );
    }

    /**
     * @test
     */
    public function it_does_broadcast_on_update_public(): void
    {
        $uuid = new UUID('bae99c42-7a71-4c3e-8532-e2f879092c7a');

        $this->writeRepository
            ->expects($this->once())
            ->method('updatePublic')
            ->with(
                $uuid
            );

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        $this->broadcastingWriteRepositoryDecorator->updatePublic(
            $uuid
        );
    }

    /**
     * @test
     */
    public function it_does_broadcast_on_update_visible(): void
    {
        $uuid = new UUID('5691c5f0-280a-47c2-b3d6-faede6d74b2f');

        $this->writeRepository
            ->expects($this->once())
            ->method('updateVisible')
            ->with(
                $uuid
            );

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        $this->broadcastingWriteRepositoryDecorator->updateVisible(
            $uuid
        );
    }

    /**
     * @test
     */
    public function it_does_broadcast_on_update_invisible(): void
    {
        $uuid = new UUID('df94b58d-ed66-4d86-a9ad-4945b77f3d1e');

        $this->writeRepository
            ->expects($this->once())
            ->method('updateInvisible')
            ->with(
                $uuid
            );

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        $this->broadcastingWriteRepositoryDecorator->updateInvisible(
            $uuid
        );
    }
}

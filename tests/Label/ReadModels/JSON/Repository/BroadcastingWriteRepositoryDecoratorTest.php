<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class BroadcastingWriteRepositoryDecoratorTest extends TestCase
{
    /**
     * @var BroadcastingWriteRepositoryDecorator
     */
    private $broadcastingWriteRepositoryDecorator;

    /**
     * @var EventBusInterface|MockObject
     */
    private $eventBus;

    /**
     * @var WriteRepositoryInterface|MockObject
     */
    private $writeRepository;

    public function setUp()
    {
        $this->writeRepository = $this->createMock(WriteRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);

        $this->broadcastingWriteRepositoryDecorator = new BroadcastingWriteRepositoryDecorator(
            $this->writeRepository,
            $this->eventBus
        );
    }

    /**
     * @test
     */
    public function it_does_not_broadcast_on_save()
    {
        $uuid = new UUID();
        $name = new StringLiteral('labelName');
        $visibility = Visibility::getByName('INVISIBLE');
        $privacy = Privacy::getByName('PRIVACY_PRIVATE');

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
    public function it_does_not_broadcast_on_update_count_increment()
    {
        $uuid = new UUID();

        $this->writeRepository
            ->expects($this->once())
            ->method('updateCountIncrement')
            ->with(
                $uuid
            );

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $this->broadcastingWriteRepositoryDecorator->updateCountIncrement(
            $uuid
        );
    }

    /**
     * @test
     */
    public function it_does_not_broadcast_on_update_count_decrement()
    {
        $uuid = new UUID();

        $this->writeRepository
            ->expects($this->once())
            ->method('updateCountDecrement')
            ->with(
                $uuid
            );

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $this->broadcastingWriteRepositoryDecorator->updateCountDecrement(
            $uuid
        );
    }

    /**
     * @test
     */
    public function it_does_broadcast_on_update_private()
    {
        $uuid = new UUID();

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
    public function it_does_broadcast_on_update_public()
    {
        $uuid = new UUID();

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
    public function it_does_broadcast_on_update_visible()
    {
        $uuid = new UUID();

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
    public function it_does_broadcast_on_update_invisible()
    {
        $uuid = new UUID();

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

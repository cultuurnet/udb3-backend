<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BroadcastingWriteRepositoryDecoratorTest extends TestCase
{
    /**
     * @var BroadcastingWriteRepositoryDecorator
     */
    private $broadcastingWriteRepositoryDecorator;

    /**
     * @var EventBus|MockObject
     */
    private $eventBus;

    /**
     * @var WriteRepositoryInterface|MockObject
     */
    private $writeRepository;

    public function setUp()
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
    public function it_does_not_broadcast_on_save()
    {
        $uuid = new UUID('eea246d1-4f50-4879-8f52-42867ed51670');
        $name = new LabelName('labelName');
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
    public function it_does_not_broadcast_on_update_count_increment()
    {
        $uuid = new UUID('963d50b4-62f5-43cc-a028-fdfdc0280bdd');

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
        $uuid = new UUID('34198f6d-e782-4e94-9593-8e31e2e2913a');

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
    public function it_does_broadcast_on_update_public()
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
    public function it_does_broadcast_on_update_visible()
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
    public function it_does_broadcast_on_update_invisible()
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

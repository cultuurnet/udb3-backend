<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\LabelAdded as LabelAddedToEvent;
use CultuurNet\UDB3\Event\Events\LabelRemoved as LabelRemovedFromEvent;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\Events\AbstractEvent;
use CultuurNet\UDB3\Label\Events\CopyCreated;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelEvent;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelAdded as LabelAddedToPlace;
use CultuurNet\UDB3\Place\Events\LabelRemoved as LabelRemovedFromPlace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class ProjectorTest extends TestCase
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var UUID
     */
    private $unknownId;

    /**
     * @var LabelName
     */
    private $labelName;

    /**
     * @var LabelName
     */
    private $unknownLabelName;

    /**
     * @var WriteRepositoryInterface|MockObject
     */
    private $writeRepository;

    /**
     * @var ReadRepositoryInterface|MockObject
     */
    private $readRepository;

    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var Projector
     */
    private $projector;

    protected function setUp()
    {
        $this->uuid = new UUID('EC1697B7-7E2B-4462-A901-EC20E2A0AAFC');
        $this->unknownId = new UUID('ACFCFE56-3D16-48FB-A053-FAA9950720DC');

        $this->labelName = new LabelName('labelName');
        $this->unknownLabelName = new LabelName('unknownLabelName');

        $this->writeRepository = $this->createMock(WriteRepositoryInterface::class);

        $this->readRepository = $this->createMock(ReadRepositoryInterface::class);

        $this->entity = new Entity(
            $this->uuid,
            $this->labelName,
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PRIVATE(),
            new UUID()
        );

        $uuidMap = [
            [$this->uuid, $this->entity],
            [$this->unknownId, null],
        ];

        $this->readRepository->method('getByUuid')
            ->will($this->returnValueMap($uuidMap));

        $this->projector = new Projector(
            $this->writeRepository,
            $this->readRepository
        );
    }

    /**
     * @test
     */
    public function it_handles_created_when_uuid_unique()
    {
        $created = new Created(
            $this->unknownId,
            $this->labelName,
            $this->entity->getVisibility(),
            $this->entity->getPrivacy()
        );

        $this->writeRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->unknownId,
                $this->entity->getName(),
                $this->entity->getVisibility(),
                $this->entity->getPrivacy()
            );

        $domainMessage = $this->createDomainMessage($this->unknownId, $created);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_created_when_uuid_not_unique()
    {
        $created = new Created(
            $this->uuid,
            $this->labelName,
            $this->entity->getVisibility(),
            $this->entity->getPrivacy()
        );

        $this->writeRepository->expects($this->never())
            ->method('save');

        $domainMessage = $this->createDomainMessage($this->unknownId, $created);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_copy_created_when_uuid_unique()
    {
        $copyCreated = new CopyCreated(
            $this->unknownId,
            $this->labelName,
            $this->entity->getVisibility(),
            $this->entity->getPrivacy(),
            $this->entity->getParentUuid()
        );

        $this->writeRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->unknownId,
                $this->entity->getName(),
                $this->entity->getVisibility(),
                $this->entity->getPrivacy(),
                $this->entity->getParentUuid()
            );

        $domainMessage = $this->createDomainMessage(
            $this->unknownId,
            $copyCreated
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_copy_created_when_uuid_not_unique()
    {
        $copyCreated = new CopyCreated(
            $this->uuid,
            $this->labelName,
            $this->entity->getVisibility(),
            $this->entity->getPrivacy(),
            $this->entity->getParentUuid()
        );

        $this->writeRepository->expects($this->never())
            ->method('save');

        $domainMessage = $this->createDomainMessage(
            $this->unknownId,
            $copyCreated
        );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_made_visible()
    {
        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            new MadeVisible($this->uuid, $this->labelName)
        );

        $this->writeRepository->expects($this->once())
            ->method('updateVisible')
            ->with($this->uuid);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_made_invisible()
    {
        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            new MadeInvisible($this->uuid, $this->labelName)
        );

        $this->writeRepository->expects($this->once())
            ->method('updateInvisible')
            ->with($this->uuid);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_made_public()
    {
        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            new MadePublic($this->uuid, $this->labelName)
        );

        $this->writeRepository->expects($this->once())
            ->method('updatePublic')
            ->with($this->uuid);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_made_private()
    {
        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            new MadePrivate($this->uuid, $this->labelName)
        );

        $this->writeRepository->expects($this->once())
            ->method('updatePrivate')
            ->with($this->uuid);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_label_added_to_event()
    {
        $labelAdded = new LabelAddedToEvent(
            'itemId',
            new Label('labelName')
        );

        $this->handleAdding($labelAdded);
    }

    /**
     * @test
     */
    public function it_handles_label_removed_from_event()
    {
        $labelRemoved = new LabelRemovedFromEvent(
            'itemId',
            new Label('labelName')
        );

        $this->handleDeleting($labelRemoved);
    }

    /**
     * @test
     */
    public function it_handles_label_added_to_place()
    {
        $labelAdded = new LabelAddedToPlace(
            'itemId',
            new Label('labelName')
        );

        $this->handleAdding($labelAdded);
    }

    /**
     * @test
     */
    public function it_handles_label_removed_from_place()
    {
        $labelRemoved = new LabelRemovedFromPlace(
            'itemId',
            new Label('labelName')
        );

        $this->handleDeleting($labelRemoved);
    }

    /**
     * @param string $id
     * @param AbstractEvent|AbstractLabelEvent $payload
     * @return DomainMessage
     */
    private function createDomainMessage($id, $payload)
    {
        return new DomainMessage(
            $id,
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }

    /**
     * @param AbstractLabelAdded $labelAdded
     */
    private function handleAdding(AbstractLabelAdded $labelAdded)
    {
        $this->handleLabelMovement($labelAdded, 'updateCountIncrement');
    }

    /**
     * @param AbstractLabelRemoved $labelRemoved
     */
    private function handleDeleting(AbstractLabelRemoved $labelRemoved)
    {
        $this->handleLabelMovement($labelRemoved, 'updateCountDecrement');
    }

    /**
     * @param AbstractLabelEvent $labelEvent
     * @param string $expectedMethod
     */
    private function handleLabelMovement(
        AbstractLabelEvent $labelEvent,
        $expectedMethod
    ) {
        $domainMessage = $this->createDomainMessage(
            $labelEvent->getItemId(),
            $labelEvent
        );

        $this->readRepository->expects($this->once())
            ->method('getByName')
            ->with(new StringLiteral($this->labelName->toNative()))
            ->willReturn($this->entity);

        $this->writeRepository->expects($this->once())
            ->method($expectedMethod)
            ->with($this->uuid);

        $this->projector->handle($domainMessage);
    }
}

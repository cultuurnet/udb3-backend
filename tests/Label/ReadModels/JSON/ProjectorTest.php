<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\LabelAdded as LabelAddedToEvent;
use CultuurNet\UDB3\Event\Events\LabelRemoved as LabelRemovedFromEvent;
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
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelEvent;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelAdded as LabelAddedToPlace;
use CultuurNet\UDB3\Place\Events\LabelRemoved as LabelRemovedFromPlace;
use CultuurNet\UDB3\StringLiteral;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProjectorTest extends TestCase
{
    private UUID $uuid;

    private UUID $unknownId;

    private string $labelName;

    private string $unknownLabelName;

    /**
     * @var WriteRepositoryInterface|MockObject
     */
    private $writeRepository;

    private Entity $entity;

    private Projector $projector;

    protected function setUp(): void
    {
        $this->uuid = new UUID('EC1697B7-7E2B-4462-A901-EC20E2A0AAFC');
        $this->unknownId = new UUID('ACFCFE56-3D16-48FB-A053-FAA9950720DC');

        $this->labelName = 'labelName';
        $this->unknownLabelName = 'unknownLabelName';

        $this->writeRepository = $this->createMock(WriteRepositoryInterface::class);

        $this->entity = new Entity(
            $this->uuid,
            new StringLiteral($this->labelName),
            Visibility::VISIBLE(),
            Privacy::PRIVACY_PRIVATE(),
            new UUID('e7dd8377-b697-4ba1-a688-164daaf961b5')
        );

        $uuidMap = [
            [$this->uuid, $this->entity],
            [$this->unknownId, null],
        ];

        $readRepository = $this->createMock(ReadRepositoryInterface::class);

        $readRepository->method('getByUuid')
            ->willReturnMap($uuidMap);

        $readRepository->method('getByName')
            ->willReturnCallback(function (string $value) {
                if ($value === $this->labelName) {
                    return $this->entity;
                }
                return null;
            });

        $this->projector = new Projector(
            $this->writeRepository,
            $readRepository
        );
    }

    /**
     * @test
     */
    public function it_handles_created_when_uuid_and_name_unique(): void
    {
        $created = new Created(
            $this->unknownId,
            $this->unknownLabelName,
            $this->entity->getVisibility(),
            $this->entity->getPrivacy()
        );

        $this->writeRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->unknownId,
                new StringLiteral($this->unknownLabelName),
                $this->entity->getVisibility(),
                $this->entity->getPrivacy()
            );

        $domainMessage = $this->createDomainMessage($this->unknownId, $created);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_handle_created_when_uuid_not_unique(): void
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
    public function it_does_not_handle_created_when_name_not_unique(): void
    {
        $created = new Created(
            $this->unknownId,
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
    public function it_handles_copy_created_when_uuid_and_name_are_unique(): void
    {
        $copyCreated = new CopyCreated(
            $this->unknownId,
            $this->unknownLabelName,
            $this->entity->getVisibility(),
            $this->entity->getPrivacy(),
            $this->entity->getParentUuid()
        );

        $this->writeRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->unknownId,
                new StringLiteral($this->unknownLabelName),
                $this->entity->getVisibility(),
                $this->entity->getPrivacy(),
                $this->entity->isExcluded(),
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
    public function it_does_not_handle_copy_created_when_uuid_not_unique(): void
    {
        $copyCreated = new CopyCreated(
            $this->uuid,
            $this->unknownLabelName,
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
    public function it_does_not_handle_copy_created_when_name_not_unique(): void
    {
        $copyCreated = new CopyCreated(
            $this->unknownId,
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
    public function it_handles_made_visible(): void
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
    public function it_handles_made_invisible(): void
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
    public function it_handles_made_public(): void
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
    public function it_handles_made_private(): void
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
    public function it_handles_label_added_to_event(): void
    {
        $labelAdded = new LabelAddedToEvent(
            '350bd67a-814a-4be0-acc8-f92395830e94',
            $this->labelName
        );

        $this->handleAdding($labelAdded);
    }

    /**
     * @test
     */
    public function it_handles_label_removed_from_event(): void
    {
        $labelRemoved = new LabelRemovedFromEvent(
            '350bd67a-814a-4be0-acc8-f92395830e94',
            $this->labelName
        );

        $this->handleDeleting($labelRemoved);
    }

    /**
     * @test
     */
    public function it_handles_label_added_to_place(): void
    {
        $labelAdded = new LabelAddedToPlace(
            '350bd67a-814a-4be0-acc8-f92395830e94',
            $this->labelName
        );

        $this->handleAdding($labelAdded);
    }

    /**
     * @test
     */
    public function it_handles_label_removed_from_place(): void
    {
        $labelRemoved = new LabelRemovedFromPlace(
            '350bd67a-814a-4be0-acc8-f92395830e94',
            $this->labelName
        );

        $this->handleDeleting($labelRemoved);
    }

    /**
     * @param AbstractEvent|AbstractLabelEvent $payload
     */
    private function createDomainMessage(UUID $id, $payload): DomainMessage
    {
        return new DomainMessage(
            $id->toString(),
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }


    private function handleAdding(AbstractLabelAdded $labelAdded): void
    {
        $this->handleLabelMovement($labelAdded, 'updateCountIncrement');
    }


    private function handleDeleting(AbstractLabelRemoved $labelRemoved): void
    {
        $this->handleLabelMovement($labelRemoved, 'updateCountDecrement');
    }

    private function handleLabelMovement(
        AbstractLabelEvent $labelEvent,
        string $expectedMethod
    ): void {
        $domainMessage = $this->createDomainMessage(
            new UUID($labelEvent->getItemId()),
            $labelEvent
        );

        $this->writeRepository->expects($this->once())
            ->method($expectedMethod)
            ->with($this->uuid);

        $this->projector->handle($domainMessage);
    }
}

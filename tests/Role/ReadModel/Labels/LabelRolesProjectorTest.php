<?php

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Label\Events\Created as LabelCreated;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class LabelRolesProjectorTest extends TestCase
{

    /**
     * @var LabelRolesProjector
     */
    private $labelRolesProjector;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    private $labelRolesRepository;

    public function setUp()
    {
        $this->labelRolesRepository = $this->createMock(DocumentRepositoryInterface::class);

        $this->labelRolesProjector = new LabelRolesProjector(
            $this->labelRolesRepository
        );
    }

    /**
     * @test
     */
    public function it_creates_projection_with_empty_list_of_roles_on_label_created_event()
    {
        $labelCreated = new LabelCreated(
            new UUID(),
            new LabelName('labelName'),
            Visibility::getByName('INVISIBLE'),
            Privacy::getByName('PRIVACY_PRIVATE')
        );

        $domainMessage = $this->createDomainMessage(
            $labelCreated->getUuid(),
            $labelCreated
        );

        $jsonDocument = $this->createEmptyJsonDocument($labelCreated->getUuid());

        $this->labelRolesRepository
            ->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_with_role_id_on_label_added_event()
    {
        $labelAdded = new LabelAdded(
            new UUID(),
            new UUID()
        );

        $domainMessage = $this->createDomainMessage(
            $labelAdded->getUuid(),
            $labelAdded
        );

        $jsonDocument = $this->createEmptyJsonDocument(
            $labelAdded->getLabelId()
        );

        $this->mockLabelRolesGet($labelAdded->getLabelId(), $jsonDocument);

        $jsonDocument = $this->createJsonDocument(
            $labelAdded->getLabelId(),
            $labelAdded->getUuid()
        );

        $this->labelRolesRepository
            ->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_role_id_from_projection_on_label_removed_event()
    {
        $labelRemoved = new LabelRemoved(
            new UUID(),
            new UUID()
        );

        $domainMessage = $this->createDomainMessage(
            $labelRemoved->getUuid(),
            $labelRemoved
        );

        $jsonDocument = $this->createJsonDocument(
            $labelRemoved->getLabelId(),
            $labelRemoved->getUuid()
        );

        $this->mockLabelRolesGet($labelRemoved->getLabelId(), $jsonDocument);

        $jsonDocument = $this->createEmptyJsonDocument(
            $labelRemoved->getLabelId()
        );

        $this->labelRolesRepository
            ->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @param UUID $uuid
     * @param SerializableInterface $payload
     * @return DomainMessage
     */
    private function createDomainMessage(
        UUID $uuid,
        SerializableInterface $payload
    ) {
        return new DomainMessage(
            $uuid,
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }

    /**
     * @param UUID $uuid
     * @return JsonDocument
     */
    private function createEmptyJsonDocument(UUID $uuid)
    {
        return new JsonDocument(
            $uuid,
            json_encode([])
        );
    }

    /**
     * @param UUID $labelId
     * @param UUID $roleId
     * @return JsonDocument
     */
    public function createJsonDocument(UUID $labelId, UUID $roleId)
    {
        return new JsonDocument(
            $labelId,
            json_encode([$roleId->toNative() => $roleId->toNative()])
        );
    }

    /**
     * @param \ValueObjects\Identity\UUID $labelId
     * @param \CultuurNet\UDB3\ReadModel\JsonDocument $jsonDocument
     */
    private function mockLabelRolesGet(UUID $labelId, JsonDocument $jsonDocument)
    {
        $this->labelRolesRepository
            ->method('get')
            ->with($labelId)
            ->willReturn($jsonDocument);
    }
}

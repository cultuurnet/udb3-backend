<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Events\Created as LabelCreated;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelRolesProjectorTest extends TestCase
{
    private LabelRolesProjector $labelRolesProjector;

    private DocumentRepository&MockObject $labelRolesRepository;

    public function setUp(): void
    {
        $this->labelRolesRepository = $this->createMock(DocumentRepository::class);

        $this->labelRolesProjector = new LabelRolesProjector(
            $this->labelRolesRepository
        );
    }

    /**
     * @test
     */
    public function it_creates_projection_with_empty_list_of_roles_on_label_created_event(): void
    {
        $labelCreated = new LabelCreated(
            new Uuid('32574fe8-e752-49dd-9dc1-6856372f5f2f'),
            'labelName',
            new Visibility('invisible'),
            new Privacy('private')
        );

        $domainMessage = $this->createDomainMessage(
            new Uuid($labelCreated->getUuid()->toString()),
            $labelCreated
        );

        $jsonDocument = $this->createEmptyJsonDocument(new Uuid($labelCreated->getUuid()->toString()));

        $this->labelRolesRepository
            ->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_with_role_id_on_label_added_event(): void
    {
        $labelAdded = new LabelAdded(
            new Uuid('99ddb83f-5e5c-4204-8e6b-cb5c6ebb668d'),
            new Uuid('78d772c8-6c52-490f-b1b2-0948776dea8e')
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
    public function it_removes_role_id_from_projection_on_label_removed_event(): void
    {
        $labelRemoved = new LabelRemoved(
            new Uuid('ba67ffc1-52a2-4065-817f-e0505c2736c0'),
            new Uuid('7bdb9166-c934-4c20-a9eb-c66e3db5c80a')
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

    private function createDomainMessage(
        Uuid $uuid,
        Serializable $payload
    ): DomainMessage {
        return new DomainMessage(
            $uuid->toString(),
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }

    private function createEmptyJsonDocument(Uuid $uuid): JsonDocument
    {
        return new JsonDocument(
            $uuid->toString(),
            Json::encode([])
        );
    }

    public function createJsonDocument(Uuid $labelId, Uuid $roleId): JsonDocument
    {
        return new JsonDocument(
            $labelId->toString(),
            Json::encode([$roleId->toString() => $roleId->toString()])
        );
    }

    private function mockLabelRolesGet(Uuid $labelId, JsonDocument $jsonDocument): void
    {
        $this->labelRolesRepository
            ->method('fetch')
            ->with($labelId->toString())
            ->willReturn($jsonDocument);
    }
}

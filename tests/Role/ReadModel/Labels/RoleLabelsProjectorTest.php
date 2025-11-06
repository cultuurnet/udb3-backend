<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Labels;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\Events\LabelDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleLabelsProjectorTest extends TestCase
{
    private ReadRepositoryInterface&MockObject $labelJsonRepository;

    private DocumentRepository&MockObject $labelRolesRepository;

    private RoleLabelsProjector $roleLabelsProjector;

    private DocumentRepository&MockObject $roleLabelsRepository;

    public function setUp(): void
    {
        $this->roleLabelsRepository = $this->createMock(DocumentRepository::class);
        $this->labelJsonRepository = $this->createMock(ReadRepositoryInterface::class);
        $this->labelRolesRepository = $this->createMock(DocumentRepository::class);

        $this->roleLabelsProjector = new RoleLabelsProjector(
            $this->roleLabelsRepository,
            $this->labelJsonRepository,
            $this->labelRolesRepository
        );
    }

    /**
     * @test
     */
    public function it_creates_projection_with_empty_list_of_labels_on_role_created_event(): void
    {
        $roleCreated = new RoleCreated(
            new Uuid('ce35c40f-4d86-4057-bbc0-6cd3fb12e65c'),
            'roleName'
        );

        $domainMessage = $this->createDomainMessage(
            $roleCreated->getUuid(),
            $roleCreated
        );

        $jsonDocument = $this->createEmptyJsonDocument($roleCreated->getUuid());
        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_projection_on_role_deleted_event(): void
    {
        $roleDeleted = new RoleDeleted(
            new Uuid('50acf32b-6b72-424e-abde-a84e7c974af3')
        );

        $domainMessage = $this->createDomainMessage(
            $roleDeleted->getUuid(),
            $roleDeleted
        );

        $this->roleLabelsRepository->expects($this->once())
            ->method('remove')
            ->with($roleDeleted->getUuid()->toString());

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projection_with_label_details_on_label_added_event(): void
    {
        $labelAdded = new LabelAdded(
            new Uuid('4e1dd8ec-670a-492f-ae1e-0a107d120898'),
            new Uuid('d3bae399-af72-4ea9-9f44-5aa7dfd6446f')
        );

        $domainMessage = $this->createDomainMessage(
            $labelAdded->getUuid(),
            $labelAdded
        );

        $labelEntity = $this->createLabelEntity($labelAdded->getLabelId());

        $this->mockLabelJsonGet(
            $labelAdded->getLabelId(),
            $labelEntity
        );

        $jsonDocument = $this->createEmptyJsonDocument($labelAdded->getUuid());

        $this->mockRoleLabelsFetch(
            $labelAdded->getUuid(),
            $jsonDocument
        );

        $expectedJsonDocument = $this->createJsonDocument(
            $labelAdded->getUuid(),
            $labelAdded->getLabelId()
        );

        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($expectedJsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_removes_label_details_from_projection_on_label_removed_event(): void
    {
        $labelRemoved = new LabelRemoved(
            new Uuid('935fe4ab-7560-407d-b8bc-ae3fc7f97f46'),
            new Uuid('5f9b02f7-896d-4a98-855b-56ab4fc4c018')
        );

        $domainMessage = $this->createDomainMessage(
            $labelRemoved->getUuid(),
            $labelRemoved
        );

        $labelEntity = $this->createLabelEntity(
            $labelRemoved->getLabelId()
        );

        $this->mockLabelJsonGet(
            $labelRemoved->getLabelId(),
            $labelEntity
        );

        $jsonDocument = $this->createJsonDocument(
            $labelRemoved->getUuid(),
            $labelRemoved->getLabelId()
        );

        $this->mockRoleLabelsFetch(
            $labelRemoved->getUuid(),
            $jsonDocument
        );

        $expectedJsonDocument = $this->createEmptyJsonDocument(
            $labelRemoved->getUuid()
        );

        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($expectedJsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_projections_with_label_details_on_label_details_projected_to_json_ld(): void
    {
        $labelProjected = new LabelDetailsProjectedToJSONLD(
            new Uuid('6ef7028c-a5e6-454d-8732-75cbdc481508')
        );

        $roleId = new Uuid('7133b129-8ab9-44d5-b94d-1e9a849e9661');

        $domainMessage = $this->createDomainMessage(
            new Uuid($labelProjected->getUuid()->toString()),
            $labelProjected
        );

        $jsonDocument = new JsonDocument(
            $labelProjected->getUuid()->toString(),
            Json::encode([$roleId->toString() => $roleId->toString()])
        );

        $this->labelRolesRepository
            ->method('fetch')
            ->with($labelProjected->getUuid()->toString())
            ->willReturn($jsonDocument);

        $jsonDocument = $this->createJsonDocument($roleId, new Uuid($labelProjected->getUuid()->toString()));

        $this->mockRoleLabelsFetch($roleId, $jsonDocument);

        $labelEntity = $this->createLabelEntity(
            new Uuid($labelProjected->getUuid()->toString())
        );

        $this->mockLabelJsonGet(new Uuid($labelProjected->getUuid()->toString()), $labelEntity);


        $this->roleLabelsRepository->expects($this->once())
            ->method('save')
            ->with($jsonDocument);

        $this->roleLabelsProjector->handle($domainMessage);
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

    public function createJsonDocument(Uuid $uuid, Uuid $labelId): JsonDocument
    {
        return new JsonDocument(
            $uuid->toString(),
            Json::encode([$labelId->toString() => $this->createLabelEntity($labelId)])
        );
    }

    public function createLabelEntity(Uuid $uuid): Entity
    {
        return new Entity(
            new Uuid($uuid->toString()),
            'labelName',
            new Visibility('invisible'),
            new Privacy('private')
        );
    }

    private function mockRoleLabelsFetch(Uuid $uuid, JsonDocument $jsonDocument): void
    {
        $this->roleLabelsRepository
            ->method('fetch')
            ->with($uuid->toString())
            ->willReturn($jsonDocument);
    }


    private function mockLabelJsonGet(Uuid $uuid, Entity $entity): void
    {
        $this->labelJsonRepository
            ->method('getByUuid')
            ->with(new Uuid($uuid->toString()))
            ->willReturn(
                $entity
            );
    }
}

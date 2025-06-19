<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelRolesProjectorTest extends TestCase
{
    private LabelRolesWriteRepositoryInterface&MockObject $labelRolesWriteRepository;

    private LabelRolesProjector $labelRolesProjector;

    protected function setUp(): void
    {
        $this->labelRolesWriteRepository = $this->createMock(
            LabelRolesWriteRepositoryInterface::class
        );

        $this->labelRolesProjector = new LabelRolesProjector(
            $this->labelRolesWriteRepository
        );
    }

    /**
     * @test
     */
    public function it_handles_label_added_to_role_event(): void
    {
        $labelAdded = new LabelAdded(
            new Uuid('4f7eb061-109e-42af-9e51-96efc3b862dd'),
            new Uuid('02a6ffe1-6f7e-4cd0-82ee-796c21781c66')
        );
        $domainMessage = $this->createDomainMessage($labelAdded);

        $this->labelRolesWriteRepository->expects($this->once())
            ->method('insertLabelRole')
            ->with(
                $labelAdded->getLabelId(),
                $labelAdded->getUuid()
            );

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_label_removed_from_role_event(): void
    {
        $labelRemoved = new LabelRemoved(
            new Uuid('9efd4336-b892-4d49-a631-91c0b744d630'),
            new Uuid('ee158abf-94b6-44b7-b709-99ec57938ede')
        );
        $domainMessage = $this->createDomainMessage($labelRemoved);

        $this->labelRolesWriteRepository->expects($this->once())
            ->method('removeLabelRole')
            ->with(
                $labelRemoved->getLabelId(),
                $labelRemoved->getUuid()
            );

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_role_deleted(): void
    {
        $roleDeleted = new RoleDeleted(new Uuid('b951a2c0-6a5b-4867-8888-e53c3152d5fa'));
        $domainMessage = $this->createDomainMessage($roleDeleted);

        $this->labelRolesWriteRepository->expects($this->once())
            ->method('removeRole')
            ->with($roleDeleted->getUuid());

        $this->labelRolesProjector->handle($domainMessage);
    }

    private function createDomainMessage(Serializable $payload): DomainMessage
    {
        return new DomainMessage(
            'id',
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }
}

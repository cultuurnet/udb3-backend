<?php

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;

class LabelRolesProjectorTest extends TestCase
{
    /**
     * @var LabelRolesWriteRepositoryInterface|MockObject
     */
    private $labelRolesWriteRepository;

    /**
     * @var LabelRolesProjector
     */
    private $labelRolesProjector;

    protected function setUp()
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
    public function it_handles_label_added_to_role_event()
    {
        $labelAdded = new LabelAdded(new UUID(), new UUID());
        $domainMessage = $this->createDomainMessage($labelAdded);

        $this->labelRolesWriteRepository->expects($this->once())
            ->method('insertLabelRole')
            ->with($labelAdded->getLabelId(), $labelAdded->getUuid());

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_label_removed_from_role_event()
    {
        $labelRemoved = new LabelRemoved(new UUID(), new UUID());
        $domainMessage = $this->createDomainMessage($labelRemoved);

        $this->labelRolesWriteRepository->expects($this->once())
            ->method('removeLabelRole')
            ->with($labelRemoved->getLabelId(), $labelRemoved->getUuid());

        $this->labelRolesProjector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_role_deleted()
    {
        $roleDeleted = new RoleDeleted(new UUID());
        $domainMessage = $this->createDomainMessage($roleDeleted);

        $this->labelRolesWriteRepository->expects($this->once())
            ->method('removeRole')
            ->with($roleDeleted->getUuid());

        $this->labelRolesProjector->handle($domainMessage);
    }

    private function createDomainMessage(SerializableInterface $payload)
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

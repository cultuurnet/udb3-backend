<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Label\Events\AbstractEvent;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\Excluded;
use CultuurNet\UDB3\Label\Events\Included;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Offer\Events\AbstractLabelEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectorTest extends TestCase
{
    private Uuid $uuid;

    private Uuid $unknownId;

    private string $labelName;

    private string $unknownLabelName;

    private WriteRepositoryInterface&MockObject $writeRepository;

    private Entity $entity;

    private Projector $projector;

    protected function setUp(): void
    {
        $this->uuid = new Uuid('EC1697B7-7E2B-4462-A901-EC20E2A0AAFC');
        $this->unknownId = new Uuid('ACFCFE56-3D16-48FB-A053-FAA9950720DC');

        $this->labelName = 'labelName';
        $this->unknownLabelName = 'unknownLabelName';

        $this->writeRepository = $this->createMock(WriteRepositoryInterface::class);

        $this->entity = new Entity(
            $this->uuid,
            $this->labelName,
            Visibility::visible(),
            Privacy::private()
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
                $this->unknownLabelName,
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
    public function it_handles_including(): void
    {
        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            new Included($this->uuid)
        );

        $this->writeRepository->expects($this->once())
            ->method('updateIncluded')
            ->with($this->uuid);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_excluded(): void
    {
        $domainMessage = $this->createDomainMessage(
            $this->uuid,
            new Excluded($this->uuid)
        );

        $this->writeRepository->expects($this->once())
            ->method('updateExcluded')
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
     * @param AbstractEvent|AbstractLabelEvent|Included|Excluded $payload
     */
    private function createDomainMessage(Uuid $id, $payload): DomainMessage
    {
        return new DomainMessage(
            $id->toString(),
            0,
            new Metadata(),
            $payload,
            BroadwayDateTime::now()
        );
    }
}

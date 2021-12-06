<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Label\Events\CopyCreated;
use CultuurNet\UDB3\Label\Events\Created;
use CultuurNet\UDB3\Label\Events\MadeInvisible;
use CultuurNet\UDB3\Label\Events\MadePrivate;
use CultuurNet\UDB3\Label\Events\MadePublic;
use CultuurNet\UDB3\Label\Events\MadeVisible;
use CultuurNet\UDB3\Label\ReadModels\AbstractProjector;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\LabelsImportedEventInterface;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class Projector extends AbstractProjector
{
    private WriteRepositoryInterface $writeRepository;

    private ReadRepositoryInterface $readRepository;

    public function __construct(
        WriteRepositoryInterface $writeRepository,
        ReadRepositoryInterface $readRepository
    ) {
        $this->writeRepository = $writeRepository;
        $this->readRepository = $readRepository;
    }

    public function applyCreated(Created $created): void
    {
        $labelWithSameUuid = $this->readRepository->getByUuid($created->getUuid());
        $labelWithSameName = $this->readRepository->getByName($created->getName());

        if ($labelWithSameUuid ||  $labelWithSameName) {
            return;
        }
        $this->writeRepository->save(
            $created->getUuid(),
            $created->getName(),
            $created->getVisibility(),
            $created->getPrivacy()
        );
    }

    public function applyCopyCreated(CopyCreated $copyCreated): void
    {
        $labelWithSameUuid = $this->readRepository->getByUuid($copyCreated->getUuid());
        $labelWithSameName = $this->readRepository->getByName($copyCreated->getName());

        if ($labelWithSameUuid ||  $labelWithSameName) {
            return;
        }

        $this->writeRepository->save(
            $copyCreated->getUuid(),
            $copyCreated->getName(),
            $copyCreated->getVisibility(),
            $copyCreated->getPrivacy(),
            $copyCreated->getParentUuid()
        );
    }

    public function applyMadeVisible(MadeVisible $madeVisible): void
    {
        $this->writeRepository->updateVisible($madeVisible->getUuid());
    }

    public function applyMadeInvisible(MadeInvisible $madeInvisible): void
    {
        $this->writeRepository->updateInvisible($madeInvisible->getUuid());
    }

    public function applyMadePublic(MadePublic $madePublic): void
    {
        $this->writeRepository->updatePublic($madePublic->getUuid());
    }

    public function applyMadePrivate(MadePrivate $madePrivate): void
    {
        $this->writeRepository->updatePrivate($madePrivate->getUuid());
    }

    public function applyLabelAdded(LabelEventInterface $labelAdded, Metadata $metadata): void
    {
        $uuid = $this->getUuid($labelAdded);

        if ($uuid) {
            $this->writeRepository->updateCountIncrement($uuid);
        }
    }

    public function applyLabelRemoved(LabelEventInterface $labelRemoved, Metadata $metadata): void
    {
        $uuid = $this->getUuid($labelRemoved);

        if ($uuid) {
            $this->writeRepository->updateCountDecrement($uuid);
        }
    }

    public function applyLabelsImported(LabelsImportedEventInterface $labelsImported, Metadata $metadata): void
    {
        // This projector does not handle this event, but it is part of abstract projector.
    }

    private function getUuid(LabelEventInterface $labelEvent): ?UUID
    {
        $uuid = null;

        $entity = $this->readRepository->getByName(
            new StringLiteral($labelEvent->getLabelName())
        );

        if ($entity !== null) {
            $uuid = $entity->getUuid();
        }

        return $uuid;
    }
}

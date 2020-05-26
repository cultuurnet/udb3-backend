<?php

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
    /**
     * @var WriteRepositoryInterface
     */
    private $writeRepository;

    /**
     * @var ReadRepositoryInterface
     */
    private $readRepository;

    /**
     * Projector constructor.
     * @param WriteRepositoryInterface $writeRepository
     * @param ReadRepositoryInterface $readRepository
     */
    public function __construct(
        WriteRepositoryInterface $writeRepository,
        ReadRepositoryInterface $readRepository
    ) {
        $this->writeRepository = $writeRepository;
        $this->readRepository = $readRepository;
    }

    /**
     * @param Created $created
     */
    public function applyCreated(Created $created)
    {
        $entity = $this->readRepository->getByUuid($created->getUuid());

        if (is_null($entity)) {
            $this->writeRepository->save(
                $created->getUuid(),
                $created->getName(),
                $created->getVisibility(),
                $created->getPrivacy()
            );
        }
    }

    /**
     * @param CopyCreated $copyCreated
     */
    public function applyCopyCreated(CopyCreated $copyCreated)
    {
        $entity = $this->readRepository->getByUuid($copyCreated->getUuid());

        if (is_null($entity)) {
            $this->writeRepository->save(
                $copyCreated->getUuid(),
                $copyCreated->getName(),
                $copyCreated->getVisibility(),
                $copyCreated->getPrivacy(),
                $copyCreated->getParentUuid()
            );
        }
    }

    /**
     * @param MadeVisible $madeVisible
     */
    public function applyMadeVisible(MadeVisible $madeVisible)
    {
        $this->writeRepository->updateVisible($madeVisible->getUuid());
    }

    /**
     * @param MadeInvisible $madeInvisible
     */
    public function applyMadeInvisible(MadeInvisible $madeInvisible)
    {
        $this->writeRepository->updateInvisible($madeInvisible->getUuid());
    }

    /**
     * @param MadePublic $madePublic
     */
    public function applyMadePublic(MadePublic $madePublic)
    {
        $this->writeRepository->updatePublic($madePublic->getUuid());
    }

    /**
     * @param MadePrivate $madePrivate
     */
    public function applyMadePrivate(MadePrivate $madePrivate)
    {
        $this->writeRepository->updatePrivate($madePrivate->getUuid());
    }

    /**
     * @inheritdoc
     */
    public function applyLabelAdded(LabelEventInterface $labelAdded, Metadata $metadata)
    {
        $uuid = $this->getUuid($labelAdded);

        if ($uuid) {
            $this->writeRepository->updateCountIncrement($uuid);
        }
    }

    /**
     * @inheritdoc
     */
    public function applyLabelRemoved(LabelEventInterface $labelRemoved, Metadata $metadata)
    {
        $uuid = $this->getUuid($labelRemoved);

        if ($uuid) {
            $this->writeRepository->updateCountDecrement($uuid);
        }
    }

    /**
     * @inheritdoc
     */
    public function applyLabelsImported(LabelsImportedEventInterface $labelsImported, Metadata $metadata)
    {
        // This projector does not handle this event, but it is part of abstract projector.
    }

    /**
     * @param LabelEventInterface $labelEvent
     * @return UUID|null
     */
    private function getUuid(LabelEventInterface $labelEvent)
    {
        $uuid = null;

        $name = new StringLiteral((string) $labelEvent->getLabel());

        $entity = $this->readRepository->getByName($name);
        if ($entity !== null) {
            $uuid = $entity->getUuid();
        }

        return $uuid;
    }
}

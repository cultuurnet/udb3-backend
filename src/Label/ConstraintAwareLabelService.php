<?php

namespace CultuurNet\UDB3\Label;

use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;

class ConstraintAwareLabelService implements LabelServiceInterface
{
    /**
     * @var Repository
     */
    private $labelRepository;

    /**
     * @var UuidGeneratorInterface
     */
    private $uuidGenerator;

    public function __construct(
        Repository $labelRepository,
        UuidGeneratorInterface $uuidGenerator
    ) {
        $this->labelRepository = $labelRepository;
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * @inheritdoc
     */
    public function createLabelAggregateIfNew(LabelName $labelName, bool $visible): ?UUID
    {
        try {
            $labelAggregate = Label::create(
                new UUID($this->uuidGenerator->generate()),
                $labelName,
                $visible ? Visibility::VISIBLE() : Visibility::INVISIBLE(),
                Privacy::PRIVACY_PUBLIC()
            );

            $this->labelRepository->save($labelAggregate);

            return UUID::fromNative($labelAggregate->getAggregateRootId());
        } catch (UniqueConstraintException $exception) {
            return null;
        }
    }
}

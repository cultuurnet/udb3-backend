<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

class ConstraintAwareLabelService implements LabelServiceInterface
{
    private Repository $labelRepository;

    private UuidGeneratorInterface $uuidGenerator;

    public function __construct(
        Repository $labelRepository,
        UuidGeneratorInterface $uuidGenerator
    ) {
        $this->labelRepository = $labelRepository;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function createLabelAggregateIfNew(LabelName $labelName, bool $visible): ?UUID
    {
        try {
            $labelAggregate = Label::create(
                new UUID($this->uuidGenerator->generate()),
                $labelName->toString(),
                $visible ? Visibility::VISIBLE() : Visibility::INVISIBLE(),
                Privacy::PRIVACY_PUBLIC()
            );

            $this->labelRepository->save($labelAggregate);

            return new UUID($labelAggregate->getAggregateRootId());
        } catch (UniqueConstraintException $exception) {
            return null;
        }
    }
}

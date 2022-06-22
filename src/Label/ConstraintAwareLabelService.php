<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueConstraintException;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

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
    public function createLabelAggregateIfNew(LegacyLabelName $labelName, bool $visible): ?UUID
    {
        try {
            $labelAggregate = Label::create(
                new UUID($this->uuidGenerator->generate()),
                new LabelName($labelName->toNative()),
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

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

abstract class AbstractEvent implements Serializable
{
    public const UUID = 'uuid';
    public const NAME = 'name';

    /**
     * @var UUID
     */
    private $uuid;

    private LabelName $name;

    public function __construct(UUID $uuid, LabelName $name)
    {
        $this->uuid = $uuid;
        $this->name = $name;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    public function getName(): LabelName
    {
        return $this->name;
    }

    public function serialize(): array
    {
        return [
            self::UUID => $this->getUuid()->toString(),
            self::NAME => $this->getName()->toString(),
        ];
    }
}

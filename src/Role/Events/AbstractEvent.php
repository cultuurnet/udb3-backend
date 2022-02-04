<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

abstract class AbstractEvent implements Serializable
{
    public const UUID = 'uuid';

    /**
     * @var UUID
     */
    private $uuid;

    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): UUID
    {
        return $this->uuid;
    }

    /**
     * @inheritdoc
     */
    public function serialize(): array
    {
        return ['uuid' => $this->getUuid()->toString()];
    }
}

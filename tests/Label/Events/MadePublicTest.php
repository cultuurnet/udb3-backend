<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class MadePublicTestAbstract extends AbstractExtendsTest
{
    /**
     * @inheritdoc
     */
    public function createEvent(UUID $uuid, LabelName $name)
    {
        return new MadePublic($uuid, $name);
    }

    /**
     * @inheritdoc
     */
    public function deserialize(array $array)
    {
        return MadePublic::deserialize(
            [
                'uuid' => $this->uuid->toString(),
                'name' => $this->name->toNative(),
            ]
        );
    }
}

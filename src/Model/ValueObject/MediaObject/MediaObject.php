<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

class MediaObject
{
    private Uuid $id;

    private MediaObjectType $type;

    public function __construct(
        Uuid $id,
        MediaObjectType $type
    ) {
        $this->id = $id;
        $this->type = $type;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): MediaObjectType
    {
        return $this->type;
    }
}

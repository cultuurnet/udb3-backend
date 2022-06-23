<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;

class CreateCopy extends Create
{
    private UUID $parentUuid;

    public function __construct(
        UUID $uuid,
        LabelName $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid
    ) {
        parent::__construct($uuid, $name, $visibility, $privacy);

        $this->parentUuid = $parentUuid;
    }

    public function getParentUuid(): UUID
    {
        return $this->parentUuid;
    }
}

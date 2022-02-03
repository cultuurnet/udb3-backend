<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class CreateCopy extends Create
{
    /**
     * @var UUID
     */
    private $parentUuid;

    /**
     * CreateCopy constructor.
     */
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

    /**
     * @return UUID
     */
    public function getParentUuid()
    {
        return $this->parentUuid;
    }
}

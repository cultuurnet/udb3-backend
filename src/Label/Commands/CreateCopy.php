<?php

namespace CultuurNet\UDB3\Label\Commands;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use ValueObjects\Identity\UUID;

class CreateCopy extends Create
{
    /**
     * @var UUID
     */
    private $parentUuid;

    /**
     * CreateCopy constructor.
     * @param UUID $uuid
     * @param LabelName $name
     * @param Visibility $visibility
     * @param Privacy $privacy
     * @param UUID $parentUuid
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

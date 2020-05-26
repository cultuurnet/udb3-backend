<?php

namespace CultuurNet\UDB3\Role\Commands;

use ValueObjects\Identity\UUID;

abstract class AbstractLabelCommand extends AbstractCommand
{
    /**
     * @var UUID
     */
    private $labelId;

    /**
     * @param UUID $uuid
     * @param UUID $labelId
     */
    public function __construct(
        UUID $uuid,
        UUID $labelId
    ) {
        parent::__construct($uuid);
        $this->labelId = $labelId;
    }

    /**
     * @return UUID
     */
    public function getLabelId()
    {
        return $this->labelId;
    }
}

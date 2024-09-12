<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

abstract class AbstractLabelCommand extends AbstractCommand
{
    private UUID $labelId;

    public function __construct(
        UUID $uuid,
        UUID $labelId
    ) {
        parent::__construct($uuid);
        $this->labelId = $labelId;
    }

    public function getLabelId(): UUID
    {
        return $this->labelId;
    }
}

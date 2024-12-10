<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

abstract class AbstractLabelCommand extends AbstractCommand
{
    private Uuid $labelId;

    public function __construct(
        Uuid $uuid,
        Uuid $labelId
    ) {
        parent::__construct($uuid);
        $this->labelId = $labelId;
    }

    public function getLabelId(): Uuid
    {
        return $this->labelId;
    }
}

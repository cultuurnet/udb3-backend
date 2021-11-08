<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateAudience extends AbstractCommand
{
    private Audience $audience;

    public function __construct(
        string $itemId,
        Audience $audience
    ) {
        parent::__construct($itemId);

        $this->audience = $audience;
    }

    public function getAudience(): Audience
    {
        return $this->audience;
    }
}

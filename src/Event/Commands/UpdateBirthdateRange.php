<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

final class UpdateBirthdateRange extends AbstractCommand
{
    public function __construct(string $itemId, public readonly BirthdateRange $birthdateRange)
    {
        parent::__construct($itemId);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthYearRange;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

final class UpdateBirthYearRange extends AbstractCommand
{
    public function __construct(string $itemId, public readonly BirthYearRange $birthYearRange)
    {
        parent::__construct($itemId);
    }
}

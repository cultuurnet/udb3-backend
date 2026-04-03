<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

final class UpdateDeparturePlaces extends AbstractCommand
{
    public function __construct(string $itemId, public readonly Urls $departurePlaces)
    {
        parent::__construct($itemId);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

final class DeleteFaqItem extends AbstractCommand
{
    public function __construct(string $itemId, public readonly string $faqItemId)
    {
        parent::__construct($itemId);
    }
}

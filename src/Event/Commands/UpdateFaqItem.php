<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaqItem;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

final class UpdateFaqItem extends AbstractCommand
{
    public function __construct(string $itemId, public readonly TranslatedFaqItem $faqItem)
    {
        parent::__construct($itemId);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Faq\Faqs;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

final class UpdateFaqs extends AbstractCommand
{
    public function __construct(string $itemId, public readonly Faqs $faqItems)
    {
        parent::__construct($itemId);
    }
}

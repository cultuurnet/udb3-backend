<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Faq\TranslatedFaqItem;
use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateFaqItem extends AbstractCommand
{
    private TranslatedFaqItem $faqItem;

    public function __construct(string $itemId, TranslatedFaqItem $faqItem)
    {
        parent::__construct($itemId);
        $this->faqItem = $faqItem;
    }

    public function getFaqItem(): TranslatedFaqItem
    {
        return $this->faqItem;
    }
}

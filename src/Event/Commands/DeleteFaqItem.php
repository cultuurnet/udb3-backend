<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

final class DeleteFaqItem extends AbstractCommand
{
    private string $faqItemId;

    public function __construct(string $itemId, string $faqItemId)
    {
        parent::__construct($itemId);
        $this->faqItemId = $faqItemId;
    }

    public function getFaqItemId(): string
    {
        return $this->faqItemId;
    }
}

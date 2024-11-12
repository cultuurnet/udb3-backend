<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

abstract class AbstractTranslatePropertyCommand extends AbstractCommand
{
    protected Language $language;

    public function __construct(string $itemId, Language $language)
    {
        parent::__construct($itemId);
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}

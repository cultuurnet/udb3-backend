<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class DeleteDescription extends AbstractCommand
{
    private Language $language;

    public function __construct(string $offerId, Language $language)
    {
        parent::__construct($offerId);

        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}

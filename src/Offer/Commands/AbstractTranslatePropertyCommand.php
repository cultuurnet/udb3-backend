<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Language;

abstract class AbstractTranslatePropertyCommand extends AbstractCommand
{
    /**
     * @var Language
     */
    protected $language;

    public function __construct($itemId, Language $language)
    {
        parent::__construct($itemId);
        $this->language = $language;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }
}

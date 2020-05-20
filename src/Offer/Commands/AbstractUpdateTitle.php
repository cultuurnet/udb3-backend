<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Language;
use ValueObjects\StringLiteral\StringLiteral;

abstract class AbstractUpdateTitle extends AbstractTranslatePropertyCommand
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @param string $itemId
     * @param Language $language
     * @param StringLiteral $title
     */
    public function __construct($itemId, Language $language, StringLiteral $title)
    {
        parent::__construct($itemId, $language);
        $this->title = $title;
    }

    /**
     * @return StringLiteral
     */
    public function getTitle()
    {
        return $this->title;
    }
}

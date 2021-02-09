<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;

abstract class AbstractUpdateTitle extends AbstractTranslatePropertyCommand
{
    /**
     * @var Title
     */
    protected $title;

    public function __construct(string $itemId, Language $language, Title $title)
    {
        parent::__construct($itemId, $language);
        $this->title = $title;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }
}

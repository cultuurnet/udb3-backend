<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;

abstract class AbstractUpdateDescription extends AbstractCommand
{
    protected Description $description;

    protected Language $language;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, Language $language, Description $description)
    {
        parent::__construct($itemId);
        $this->description = $description;
        $this->language = $language;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }
}

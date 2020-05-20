<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;

abstract class AbstractUpdateDescription extends AbstractCommand
{
    /**
     * Description to be added.
     * @var Description
     */
    protected $description;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @param string $itemId
     * @param Description $description
     * @param Language $language
     */
    public function __construct($itemId, Language $language, Description $description)
    {
        parent::__construct($itemId);
        $this->description = $description;
        $this->language = $language;
    }

    /**
     * @return Description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }
}

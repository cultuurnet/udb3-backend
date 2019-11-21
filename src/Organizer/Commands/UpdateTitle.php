<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;

class UpdateTitle extends AbstractUpdateOrganizerCommand
{
    /**
     * @var Title
     */
    private $title;

    /**
     * @var Language
     */
    private $language;

    /**
     * UpdateTitle constructor.
     * @param string $organizerId
     * @param Title $title
     * @param Language $language
     */
    public function __construct(
        $organizerId,
        Title $title,
        Language $language
    ) {
        parent::__construct($organizerId);
        $this->title = $title;
        $this->language = $language;
    }

    /**
     * @return Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }
}

<?php

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

class CreateOrganizer extends AbstractOrganizerCommand
{
    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @var Url
     */
    private $website;

    /**
     * @var Title
     */
    private $title;

    /**
     * @param string $id
     * @param Language $mainLanguage
     * @param Url $website
     * @param Title $title
     */
    public function __construct(
        $id,
        Language $mainLanguage,
        Url $website,
        Title $title
    ) {
        parent::__construct($id);

        $this->website = $website;
        $this->mainLanguage = $mainLanguage;
        $this->title = $title;
    }

    /**
     * @return Language
     */
    public function getMainLanguage()
    {
        return $this->mainLanguage;
    }

    /**
     * @return Url
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return Title
     */
    public function getTitle()
    {
        return $this->title;
    }
}

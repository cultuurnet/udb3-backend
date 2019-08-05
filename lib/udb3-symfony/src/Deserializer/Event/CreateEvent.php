<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Event;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;

class CreateEvent extends MajorInfo
{
    /**
     * @var Language
     */
    private $mainLanguage;

    /**
     * @param Language $mainLanguage
     * @param Title $title
     * @param EventType $type
     * @param LocationId $location
     * @param Calendar $calendar
     * @param Theme|null $theme
     */
    public function __construct(
        Language $mainLanguage,
        Title $title,
        EventType $type,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    ) {
        parent::__construct(
            $title,
            $type,
            $location,
            $calendar,
            $theme
        );

        $this->mainLanguage = $mainLanguage;
    }

    /**
     * @return Language
     */
    public function getMainLanguage()
    {
        return $this->mainLanguage;
    }
}

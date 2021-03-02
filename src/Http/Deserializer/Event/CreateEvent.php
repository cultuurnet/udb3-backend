<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

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

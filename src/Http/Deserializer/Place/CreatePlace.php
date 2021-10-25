<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;

class CreatePlace extends MajorInfo
{
    /**
     * @var Language
     */
    private $mainLanguage;


    public function __construct(
        Language $mainLanguage,
        Title $title,
        EventType $type,
        Address $address,
        Calendar $calendar
    ) {
        parent::__construct(
            $title,
            $type,
            $address,
            $calendar
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

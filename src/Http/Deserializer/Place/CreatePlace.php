<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

class CreatePlace extends MajorInfo
{
    private Language $mainLanguage;

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

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }
}

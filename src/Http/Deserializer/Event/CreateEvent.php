<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

class CreateEvent extends MajorInfo
{
    private Language $mainLanguage;

    public function __construct(
        Language $mainLanguage,
        Title $title,
        Category $type,
        LocationId $location,
        Calendar $calendar,
        Category $theme = null
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

    public function getMainLanguage(): Language
    {
        return $this->mainLanguage;
    }
}

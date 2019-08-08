<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\OpeningHour;

interface CalendarJSONParserInterface
{
    /**
     * @param mixed $data
     *
     * @return \DateTimeInterface|null
     */
    public function getStartDate($data);

    /**
     * @param mixed $data
     *
     * @return \DateTimeInterface|null
     */
    public function getEndDate($data);

    /**
     * @param mixed $data
     *
     * @return TimeSpan[]
     */
    public function getTimeSpans($data);

    /**
     * @param mixed $data
     *
     * @return OpeningHour[]
     */
    public function getOpeningHours($data);
}

<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use ValueObjects\StringLiteral\StringLiteral;

class CalendarJSONDeserializer extends JSONDeserializer
{
    /**
     * @var CalendarJSONParserInterface
     */
    private $calendarJSONParser;

    /**
     * @var DataValidatorInterface
     */
    private $calendarDataValidator;

    public function __construct(
        CalendarJSONParserInterface $calendarJSONParser,
        DataValidatorInterface $calendarDataValidator
    ) {
        parent::__construct(true);

        $this->calendarJSONParser = $calendarJSONParser;
        $this->calendarDataValidator = $calendarDataValidator;
    }

    /**
     * @param StringLiteral $data
     * @return Calendar
     */
    public function deserialize(StringLiteral $data)
    {
        $data = (array) parent::deserialize($data);

        $this->calendarDataValidator->validate($data);

        // There are 6 possible options in 2 categories.
        //
        // The categories are:
        // 1. Weekly recurring with focus on opening hours
        // 2. Time spans with focus on start and end time
        //
        // The options inside the 'Weekly' category are:
        // 1. Fully empty => permanent
        // 2. Opening hours => permanent + opening hours
        // 3. Start and end time => periodic
        // 4. Start and end time + opening hours => periodic + opening hours
        //
        // The options inside the 'Time span' category are:
        // 1. Just one time span => single
        // 2. Multiple time spans => multiple

        $calendar = new Calendar(
            $this->getCalendarType($data),
            $this->getStartDate($data),
            $this->getEndDate($data),
            $this->calendarJSONParser->getTimestamps($data),
            $this->calendarJSONParser->getOpeningHours($data)
        );

        $status = $this->calendarJSONParser->getStatus($data);
        if ($status !== null) {
            $calendar = $calendar->withStatus($status);
        }

        return $calendar;
    }

    /**
     * @param array $data
     *
     * @return CalendarType
     */
    private function getCalendarType(array $data)
    {
        if (count($this->calendarJSONParser->getTimestamps($data)) > 1) {
            return CalendarType::MULTIPLE();
        }

        if (count($this->calendarJSONParser->getTimestamps($data)) == 1) {
            return CalendarType::SINGLE();
        }

        if ($this->calendarJSONParser->getStartDate($data) &&
            $this->calendarJSONParser->getEndDate($data)) {
            return CalendarType::PERIODIC();
        }

        return CalendarType::PERMANENT();
    }

    /**
     * @param array $data
     *
     * @return \DateTimeInterface|null
     */
    private function getStartDate(array $data)
    {
        $timestamps = $this->calendarJSONParser->getTimestamps($data);
        if (count($timestamps)) {
            return null;
        }

        if ($this->calendarJSONParser->getStartDate($data)) {
            return $this->calendarJSONParser->getStartDate($data);
        }

        return null;
    }

    /**
     * @param array $data
     *
     * @return \DateTimeInterface|null
     */
    private function getEndDate(array $data)
    {
        $timestamps = $this->calendarJSONParser->getTimestamps($data);
        if (count($timestamps)) {
            return null;
        }

        if ($this->calendarJSONParser->getEndDate($data)) {
            return $this->calendarJSONParser->getEndDate($data);
        }

        return null;
    }
}

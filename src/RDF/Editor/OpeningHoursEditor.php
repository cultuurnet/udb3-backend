<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHourNormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHoursNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarWithOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\ResourceFactory;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class OpeningHoursEditor
{
    private const TYPE_BESCHIKBAARHEID = 'cp:Beschikbaarheid';
    private const TYPE_OPENING_HOURS_SPECIFICATION = 'schema:OpeningHoursSpecification';

    private const PROPERTY_BESCHIKBAARHEID = 'cpa:beschikbaarheid';
    private const PROPERTY_HOURSAVAILABLE = 'schema:hoursAvailable';
    private const PROPERTY_OPENS = 'schema:opens';
    private const PROPERTY_CLOSES = 'schema:closes';
    private const PROPERTY_DAY_OF_WEEK = 'schema:dayOfWeek';

    public function setOpeningHours(Resource $resource, Calendar $calendar, ResourceFactory $resourceFactory): self
    {
        if (!$calendar instanceof CalendarWithOpeningHours) {
            return $this;
        }

        $openingHours = $calendar->getOpeningHours();
        if ($openingHours->isAlwaysOpen()) {
            return $this;
        }

        $beschikbaarheid = $resourceFactory->create($resource, self::TYPE_BESCHIKBAARHEID, (new OpeningHoursNormalizer())->normalize($openingHours));

        /** @var OpeningHour $openingHour */
        foreach ($openingHours as $openingHour) {
            $hoursAvailable = $resourceFactory->create($resource, self::TYPE_OPENING_HOURS_SPECIFICATION, (new OpeningHourNormalizer())->normalize($openingHour));

            $hoursAvailable->addLiteral(self::PROPERTY_OPENS, $this->timeToLiteral($openingHour->getOpeningTime()));
            $hoursAvailable->addLiteral(self::PROPERTY_CLOSES, $this->timeToLiteral($openingHour->getClosingTime()));

            /** @var Day $dayOfWeek */
            foreach ($openingHour->getDays() as $dayOfWeek) {
                $hoursAvailable->addResource(
                    self::PROPERTY_DAY_OF_WEEK,
                    $hoursAvailable->getGraph()->resource('schema:' . ucfirst($dayOfWeek->toString()))
                );
            }

            $beschikbaarheid->add(self::PROPERTY_HOURSAVAILABLE, $hoursAvailable);
        }

        $resource->add(self::PROPERTY_BESCHIKBAARHEID, $beschikbaarheid);

        return $this;
    }

    private function timeToLiteral(Time $time): Literal
    {
        $openingTime = new \DateTime();
        $openingTime->setTime($time->getHour()->toInteger(), $time->getMinute()->toInteger());

        return new Literal($openingTime->format('H:i'));
    }
}

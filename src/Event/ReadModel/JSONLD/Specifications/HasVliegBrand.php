<?php

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD\Specifications;

class HasVliegBrand implements EventSpecificationInterface
{
    public function isSatisfiedBy($eventLd)
    {
        $hasAppropriateAge = false;

        if (property_exists($eventLd, 'typicalAgeRange')) {
            $ageRange = $eventLd->typicalAgeRange;
            $ageFrom = explode('-', $ageRange)[0];

            $hasAppropriateAge = is_numeric($ageFrom) && ($ageFrom <= 13);
        }

        return $hasAppropriateAge;
    }
}

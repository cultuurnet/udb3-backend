<?php

namespace CultuurNet\UDB3\Place;

interface PlaceServiceInterface
{
    /**
     * @param string $organizerId
     * @return string[]
     */
    public function placesOrganizedByOrganizer($organizerId);
}

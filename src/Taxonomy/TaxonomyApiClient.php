<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

interface TaxonomyApiClient
{
    public function getPlaceTypes(): array;
    public function getPlaceFacilities(): array;
    public function getEventTypes(): array;
    public function getEventThemes(): array;
    public function getEventFacilities(): array;
}

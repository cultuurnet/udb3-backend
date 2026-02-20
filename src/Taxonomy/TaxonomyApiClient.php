<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;

interface TaxonomyApiClient
{
    public function getPlaceTypes(): Categories;

    public function getPlaceFacilities(): Categories;

    public function getEventTypes(): Categories;

    public function getEventThemes(): Categories;

    public function getEventFacilities(): Categories;

    public function getNativeMapping(): array;
}

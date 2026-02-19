<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;

interface TaxonomyApiClient
{
    public function getMapping(): array;

    /**
     * @return  Category[]
     */
    public function getEventTypes(): array;

    /**
     * @return  Category[]
     */
    public function getEventThemes(): array;

    /**
     * @return  Category[]
     */
    public function getEventFacilities(): array;

    /**
     * @return  Category[]
     */
    public function getPlaceTypes(): array;

    /**
     * @return  Category[]
     */
    public function getPlaceFacilities(): array;
}

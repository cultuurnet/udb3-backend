<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;

interface TaxonomyApiClient
{
    /**
     * @return  Category[]
     */
    public function getPlaceTypes(): array;

    /**
     * @return  Category[]
     */
    public function getPlaceFacilities(): array;

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

    public function getNativeMapping(): array;
}

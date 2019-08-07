<?php

namespace CultuurNet\UDB3\UDB2\Actor\Specification;

class QualifiesAsOrganizerSpecification implements ActorSpecificationInterface
{
    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(\CultureFeed_Cdb_Item_Actor $actor)
    {
        $categories = $actor->getCategories();
        return
            $categories instanceof \CultureFeed_Cdb_Data_CategoryList &&
            $categories->hasCategory('8.11.0.0.0');
    }
}

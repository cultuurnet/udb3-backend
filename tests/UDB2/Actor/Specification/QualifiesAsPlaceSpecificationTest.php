<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Actor\Specification;

use PHPUnit\Framework\TestCase;

class QualifiesAsPlaceSpecificationTest extends TestCase
{
    /**
     * @var QualifiesAsPlaceSpecification
     */
    private $specification;

    public function setUp()
    {
        $this->specification = new QualifiesAsPlaceSpecification();
    }

    /**
     * @test
     */
    public function it_is_satisified_by_actors_with_location_actortype_category()
    {
        $actor = new \CultureFeed_Cdb_Item_Actor();

        $this->assertFalse(
            $this->specification->isSatisfiedBy($actor)
        );

        $categories = new \CultureFeed_Cdb_Data_CategoryList();
        $actor->setCategories($categories);

        $categories->add(
            new \CultureFeed_Cdb_Data_Category(
                \CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_ACTOR_TYPE,
                '8.11.0.0.0',
                'Organisator(en)'
            )
        );

        $this->assertFalse(
            $this->specification->isSatisfiedBy($actor)
        );

        $categories->add(
            new \CultureFeed_Cdb_Data_Category(
                \CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_ACTOR_TYPE,
                '8.15.0.0.0',
                'Locatie'
            )
        );


        $this->assertTrue(
            $this->specification->isSatisfiedBy($actor)
        );
    }
}

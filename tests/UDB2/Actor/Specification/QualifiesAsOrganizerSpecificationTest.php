<?php

namespace CultuurNet\UDB3\UDB2\Actor\Specification;

use PHPUnit\Framework\TestCase;

class QualifiesAsOrganizerSpecificationTest extends TestCase
{
    /**
     * @var ActorSpecificationInterface
     */
    private $qualifiesAsOrganizerSpecification;

    public function setUp()
    {
        $this->qualifiesAsOrganizerSpecification = new QualifiesAsOrganizerSpecification();
    }

    /**
     * @test
     */
    public function it_is_satisified_by_actors_with_organizer_actortype_category()
    {
        $actor = new \CultureFeed_Cdb_Item_Actor();

        $this->assertFalse(
            $this->qualifiesAsOrganizerSpecification->isSatisfiedBy($actor)
        );

        $categories = new \CultureFeed_Cdb_Data_CategoryList();
        $actor->setCategories($categories);

        $categories->add(
            new \CultureFeed_Cdb_Data_Category(
                \CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_ACTOR_TYPE,
                '8.15.0.0.0',
                'Locatie'
            )
        );

        $this->assertFalse(
            $this->qualifiesAsOrganizerSpecification->isSatisfiedBy($actor)
        );

        $categories->add(
            new \CultureFeed_Cdb_Data_Category(
                \CultureFeed_Cdb_Data_Category::CATEGORY_TYPE_ACTOR_TYPE,
                '8.11.0.0.0',
                'Organisator(en)'
            )
        );

        $this->assertTrue(
            $this->qualifiesAsOrganizerSpecification->isSatisfiedBy($actor)
        );
    }
}

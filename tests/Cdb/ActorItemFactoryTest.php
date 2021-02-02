<?php

namespace CultuurNet\UDB3\Cdb;

use PHPUnit\Framework\TestCase;

class ActorItemFactoryTest extends TestCase
{
    /**
     * @var ActorItemFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new ActorItemFactory(
            \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
        );
    }

    /**
     * @test
     */
    public function it_creates_an_actor_object_from_cdbxml()
    {
        $expected = new \CultureFeed_Cdb_Item_Actor();
        $expected->setCdbId('404EE8DE-E828-9C07-FE7D12DC4EB24480');

        $nlDetail = new \CultureFeed_Cdb_Data_ActorDetail();
        $nlDetail->setLanguage('nl');
        $nlDetail->setTitle('DE Studio');

        $details = new \CultureFeed_Cdb_Data_ActorDetailList();
        $details->add($nlDetail);
        $expected->setDetails($details);

        $categoryList = new \CultureFeed_Cdb_Data_CategoryList();
        $categoryList->add(
            new \CultureFeed_Cdb_Data_Category(
                'actortype',
                '8.11.0.0.0',
                'Organisator(en)'
            )
        );
        $expected->setCategories($categoryList);

        $cdbXml = file_get_contents(__DIR__ . '/samples/actor.xml');

        $this->assertEquals(
            $expected,
            $this->factory->createFromCdbXml($cdbXml)
        );
    }
}

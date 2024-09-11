<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

class ActorItemFactoryTest extends TestCase
{
    private ActorItemFactory $factory;

    public function setUp(): void
    {
        $this->factory = new ActorItemFactory(
            \CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
        );
    }

    /**
     * @test
     */
    public function it_creates_an_actor_object_from_cdbxml(): void
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

        $cdbXml = SampleFiles::read(__DIR__ . '/samples/actor.xml');

        $this->assertEquals(
            $expected,
            $this->factory->createFromCdbXml($cdbXml)
        );
    }
}

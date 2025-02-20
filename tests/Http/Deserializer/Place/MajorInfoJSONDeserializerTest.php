<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Place;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

final class MajorInfoJSONDeserializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_major_info(): void
    {
        $majorInfoAsJson = SampleFiles::read(__DIR__ . '/../samples/place-major-info.json');

        $majorInfoJSONDeserializer = new MajorInfoJSONDeserializer();

        $majorInfo = $majorInfoJSONDeserializer->deserialize($majorInfoAsJson);

        $expectedAddress = new Address(
            new Street('Kerkstraat 1'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );

        $this->assertEquals(new Title('Test place'), $majorInfo->getTitle());
        $this->assertEquals(
            new Category(new CategoryID('3CuHvenJ+EGkcvhXLg9Ykg'), new CategoryLabel('Archeologische Site'), CategoryDomain::eventType()),
            $majorInfo->getType()
        );
        $this->assertEquals($expectedAddress, $majorInfo->getAddress());
        $this->assertEquals(new PermanentCalendar(new OpeningHours()), $majorInfo->getCalendar());
    }
}

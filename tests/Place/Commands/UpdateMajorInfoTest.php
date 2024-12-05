<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Calendar\Calendar;
use PHPUnit\Framework\TestCase;

class UpdateMajorInfoTest extends TestCase
{
    protected UpdateMajorInfo $updateMajorInfo;

    public function setUp(): void
    {
        $this->updateMajorInfo = new UpdateMajorInfo(
            'id',
            new Title('title'),
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new Address(
                new Street('Bondgenotenlaan'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(CalendarType::permanent())
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values(): void
    {
        $expectedId = 'id';
        $expectedTitle = new Title('title');
        $expectedEventType = new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType());
        $expectedAddress = new Address(
            new Street('Bondgenotenlaan'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );
        $expectedCalendar = new Calendar(CalendarType::permanent());

        $this->assertEquals($expectedId, $this->updateMajorInfo->getItemId());
        $this->assertEquals($expectedTitle, $this->updateMajorInfo->getTitle());
        $this->assertEquals($expectedEventType, $this->updateMajorInfo->getEventType());
        $this->assertEquals($expectedAddress, $this->updateMajorInfo->getAddress());
        $this->assertEquals($expectedCalendar, $this->updateMajorInfo->getCalendar());
    }
}

<?php

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class UpdateMajorInfoTest extends TestCase
{
    /**
     * @var UpdateMajorInfo
     */
    protected $updateMajorInfo;

    public function setUp()
    {
        $this->updateMajorInfo = new UpdateMajorInfo(
            'id',
            new Title('title'),
            new EventType('bar_id', 'bar'),
            new Address(
                new Street('Bondgenotenlaan'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('themeid', 'theme_label')
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values()
    {
        $expectedId = 'id';
        $expectedTitle = new Title('title');
        $expectedEventType = new EventType('bar_id', 'bar');
        $expectedAddress = new Address(
            new Street('Bondgenotenlaan'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            Country::fromNative('BE')
        );
        $expectedCalendar = new Calendar(CalendarType::PERMANENT());
        $expectedTheme = new Theme('themeid', 'theme_label');

        $this->assertEquals($expectedId, $this->updateMajorInfo->getItemId());
        $this->assertEquals($expectedTitle, $this->updateMajorInfo->getTitle());
        $this->assertEquals($expectedEventType, $this->updateMajorInfo->getEventType());
        $this->assertEquals($expectedAddress, $this->updateMajorInfo->getAddress());
        $this->assertEquals($expectedCalendar, $this->updateMajorInfo->getCalendar());
        $this->assertEquals($expectedTheme, $this->updateMajorInfo->getTheme());
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;

class UpdateMajorInfoTest extends TestCase
{
    private UpdateMajorInfo $updateMajorInfo;

    public function setUp(): void
    {
        $this->updateMajorInfo = new UpdateMajorInfo(
            'id',
            new Title('title'),
            new EventType('bar_id', 'bar'),
            new Address(
                new Street('Bondgenotenlaan'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );
    }

    /**
     * @test
     */
    public function it_returns_the_correct_property_values(): void
    {
        $expectedId = 'id';
        $expectedTitle = new Title('title');
        $expectedEventType = new EventType('bar_id', 'bar');
        $expectedAddress = new Address(
            new Street('Bondgenotenlaan'),
            new PostalCode('3000'),
            new Locality('Leuven'),
            new CountryCode('BE')
        );
        $expectedCalendar = new Calendar(CalendarType::PERMANENT());

        $this->assertEquals($expectedId, $this->updateMajorInfo->getItemId());
        $this->assertEquals($expectedTitle, $this->updateMajorInfo->getTitle());
        $this->assertEquals($expectedEventType, $this->updateMajorInfo->getEventType());
        $this->assertEquals($expectedAddress, $this->updateMajorInfo->getAddress());
        $this->assertEquals($expectedCalendar, $this->updateMajorInfo->getCalendar());
    }
}

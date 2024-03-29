<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\TestCase;

class UpdateMajorInfoTest extends TestCase
{
    protected UpdateMajorInfo $updateMajorInfo;

    public function setUp(): void
    {
        $this->updateMajorInfo = new UpdateMajorInfo(
            'id',
            new Title('title'),
            new EventType('bar_id', 'bar'),
            new LocationId('335be568-aaf0-4147-80b6-9267daafe23b'),
            new Calendar(
                CalendarType::PERMANENT()
            ),
            new Theme('themeid', 'theme_label')
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
        $expectedLocation = new LocationId('335be568-aaf0-4147-80b6-9267daafe23b');
        $expectedCalendar = new Calendar(
            CalendarType::PERMANENT()
        );
        $expectedTheme = new Theme('themeid', 'theme_label');

        $this->assertEquals($expectedId, $this->updateMajorInfo->getItemId());
        $this->assertEquals($expectedTitle, $this->updateMajorInfo->getTitle());
        $this->assertEquals($expectedEventType, $this->updateMajorInfo->getEventType());
        $this->assertEquals($expectedLocation, $this->updateMajorInfo->getLocation());
        $this->assertEquals($expectedCalendar, $this->updateMajorInfo->getCalendar());
        $this->assertEquals($expectedTheme, $this->updateMajorInfo->getTheme());
    }
}

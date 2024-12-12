<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
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
            new LocationId('335be568-aaf0-4147-80b6-9267daafe23b'),
            new PermanentCalendar(new OpeningHours()),
            new Category(new CategoryID('1.8.3.5.0'), new CategoryLabel('Amusementsmuziek'), CategoryDomain::theme())
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
        $expectedLocation = new LocationId('335be568-aaf0-4147-80b6-9267daafe23b');
        $expectedCalendar = new PermanentCalendar(new OpeningHours());
        $expectedTheme = new Category(new CategoryID('1.8.3.5.0'), new CategoryLabel('Amusementsmuziek'), CategoryDomain::theme());

        $this->assertEquals($expectedId, $this->updateMajorInfo->getItemId());
        $this->assertEquals($expectedTitle, $this->updateMajorInfo->getTitle());
        $this->assertEquals($expectedEventType, $this->updateMajorInfo->getEventType());
        $this->assertEquals($expectedLocation, $this->updateMajorInfo->getLocation());
        $this->assertEquals($expectedCalendar, $this->updateMajorInfo->getCalendar());
        $this->assertEquals($expectedTheme, $this->updateMajorInfo->getTheme());
    }
}

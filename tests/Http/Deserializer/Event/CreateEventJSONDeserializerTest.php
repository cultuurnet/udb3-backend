<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Event;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

final class CreateEventJSONDeserializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_serialize_create_event_with_main_language(): void
    {
        $createEventAsJson = SampleFiles::read(__DIR__ . '/../samples/event-create-with-main-language.json');

        $createEventJSONDeserializer = new CreateEventJSONDeserializer();

        $createEvent = $createEventJSONDeserializer->deserialize($createEventAsJson);

        $expectedLocation = new LocationId('28cf728d-441b-4912-b3b0-f03df0d22491');

        $this->assertEquals(new Language('en'), $createEvent->getMainLanguage());
        $this->assertEquals(new Title('talking title'), $createEvent->getTitle());
        $this->assertEquals(
            new Category(new CategoryID('0.17.0.0.0'), new CategoryLabel('Route'), CategoryDomain::eventType()),
            $createEvent->getType()
        );
        $this->assertEquals($expectedLocation, $createEvent->getLocation());
        $this->assertEquals(
            new PermanentCalendar(new OpeningHours()),
            $createEvent->getCalendar()
        );
    }
}

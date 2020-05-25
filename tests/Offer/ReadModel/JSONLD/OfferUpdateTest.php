<?php

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class OfferUpdateTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_remove_all_existing_opening_hours_when_we_update_the_calendar()
    {
        $calendar = new Calendar(CalendarType::PERMANENT());

        $initialDocument = new JsonDocument(
            'some_id',
            json_encode([
                'name' => [
                    'nl' => 'heyo!',
                ],
                'terms' => [
                    [
                        'id' => '0.50.4.0.0',
                        'label' => 'concert',
                        'domain' => 'eventtype',
                    ],
                ],
                'openingHours' => [
                    ['something' => 'that looks like opening hours'],
                ],
                'calendar' => [
                    'calendarType' => 'permanent',
                ],
            ])
        );

        $expectedDocument = new JsonDocument(
            'some_id',
            json_encode([
                'name' => [
                    'nl' => 'heyo!',
                ],
                'terms' => [
                    [
                        'id' => '0.50.4.0.0',
                        'label' => 'concert',
                        'domain' => 'eventtype',
                    ],
                ],
                'calendar' => [
                    'calendarType' => 'permanent',
                ],
                'calendarType' => 'permanent',
            ])
        );


        $calendarUpdate = OfferUpdate::calendar($calendar);
        $updatedDocument = $initialDocument->apply($calendarUpdate);

        $this->assertEquals($expectedDocument, $updatedDocument);
    }
}

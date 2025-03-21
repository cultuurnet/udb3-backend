<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class OfferUpdateTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_remove_all_existing_opening_hours_when_we_update_the_calendar(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $initialDocument = new JsonDocument(
            'some_id',
            Json::encode([
                'name' => [
                    'nl' => 'heyo!',
                ],
                'terms' => [
                    [
                        'id' => '0.50.4.0.0',
                        'label' => 'Concert',
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
            Json::encode([
                'name' => [
                    'nl' => 'heyo!',
                ],
                'terms' => [
                    [
                        'id' => '0.50.4.0.0',
                        'label' => 'Concert',
                        'domain' => 'eventtype',
                    ],
                ],
                'calendar' => [
                    'calendarType' => 'permanent',
                ],
                'calendarType' => 'permanent',
                'status' => [
                    'type' => 'Available',
                ],
                'bookingAvailability' => [
                    'type' => 'Available',
                ],
            ])
        );


        $calendarUpdate = OfferUpdate::calendar($calendar);
        $updatedDocument = $initialDocument->apply($calendarUpdate);

        $this->assertEquals($expectedDocument, $updatedDocument);
    }
}

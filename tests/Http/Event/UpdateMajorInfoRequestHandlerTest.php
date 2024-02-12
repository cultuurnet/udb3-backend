<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Event\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use PHPUnit\Framework\TestCase;

class UpdateMajorInfoRequestHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function it_handles_update_major_info(): void
    {
        $commandBus = new TraceableCommandBus();
        $commandBus->record();

        $updateMajorInfoRequestHandler = new UpdateMajorInfoRequestHandler($commandBus);

        $updateMajorInfoData = [
            'name' => 'Updated title',
            'location' => [
                'id' => 'place_id',
            ],
            'type' => [
                'id' => '0.17.0.0.0',
                'label' => 'Route',
            ],
            'calendar' => [
                'type' => 'permanent',
            ],
        ];

        $updateMajorInfoRequestHandler->handle(
            (new Psr7RequestBuilder())
                ->withJsonBodyFromArray($updateMajorInfoData)
                ->withRouteParameter('eventId', 'event_id')
                ->build('PUT')
        );

        $this->assertEquals(
            [new UpdateMajorInfo(
                'event_id',
                new Title('Updated title'),
                new EventType('0.17.0.0.0', 'Route'),
                new LocationId('place_id'),
                new Calendar(CalendarType::PERMANENT())
            )],
            $commandBus->getRecordedCommands()
        );
    }
}

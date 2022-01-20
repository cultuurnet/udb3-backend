<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Place\Commands\UpdateMajorInfo;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode;

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
            'type' => [
                'id' => 'OyaPaf64AEmEAYXHeLMAtA',
                'label' => 'Zaal of expohal',
            ],
            'address' => [
                'addressCountry' => 'BE',
                'addressLocality' => 'Leuven',
                'postalCode' => '3000',
                'streetAddress' => 'Bondgenotenlaan 1',
            ],
            'calendar' => [
                'type' => 'permanent',
            ],
        ];

        $updateMajorInfoRequestHandler->handle(
            (new Psr7RequestBuilder())
                ->withJsonBodyFromArray($updateMajorInfoData)
                ->withRouteParameter('placeId', 'place_id')
                ->build('PUT')
        );

        $this->assertEquals(
            [new UpdateMajorInfo(
                'place_id',
                new Title('Updated title'),
                new EventType('OyaPaf64AEmEAYXHeLMAtA', 'Zaal of expohal'),
                new Address(
                    new Street('Bondgenotenlaan 1'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Calendar(CalendarType::PERMANENT())
            )],
            $commandBus->getRecordedCommands()
        );
    }
}

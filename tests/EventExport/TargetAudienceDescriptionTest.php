<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;
use stdClass;

final class TargetAudienceDescriptionTest extends TestCase
{
    /**
     * @test
     * @dataProvider eventsAndDoelgroep
     */
    public function it_describes_the_audience_of_an_event(stdClass $event, ?string $expected): void
    {
        $this->assertSame($expected, TargetAudienceDescription::fromEvent($event));
    }

    public function eventsAndDoelgroep(): array
    {
        $childrenOnly = 'voor kinderen alleen';
        $withGuardian = 'voor kinderen samen met hun familie of een andere begeleider';

        return [
            'an event only for children' => [
                'event' => $this->event(['childrenOnly' => true]),
                'expected' => $childrenOnly,
            ],
            'an event only for children keeps saying so whatever its age range' => [
                'event' => $this->event(['childrenOnly' => true, 'typicalAgeRange' => '18-99']),
                'expected' => $childrenOnly,
            ],
            'an age range reaching below twelve' => [
                'event' => $this->event(['typicalAgeRange' => '6-12']),
                'expected' => $withGuardian,
            ],
            'an age range of the youngest children' => [
                'event' => $this->event(['typicalAgeRange' => '0-5']),
                'expected' => $withGuardian,
            ],
            'an age range starting just below twelve' => [
                'event' => $this->event(['typicalAgeRange' => '11-18']),
                'expected' => $withGuardian,
            ],
            'an age range starting exactly at twelve is not for children' => [
                'event' => $this->event(['typicalAgeRange' => '12-18']),
                'expected' => null,
            ],
            'an age range for adults' => [
                'event' => $this->event(['typicalAgeRange' => '18-99']),
                'expected' => null,
            ],
            'an all ages event' => [
                'event' => $this->event(['typicalAgeRange' => '-']),
                'expected' => null,
            ],
            'an all ages event written as 0-' => [
                'event' => $this->event(['typicalAgeRange' => '0-']),
                'expected' => null,
            ],
            'childrenOnly false falls back to the age range' => [
                'event' => $this->event(['childrenOnly' => false, 'typicalAgeRange' => '6-12']),
                'expected' => $withGuardian,
            ],
            'an age range that is not a range' => [
                'event' => $this->event(['typicalAgeRange' => 'zes tot twaalf']),
                'expected' => null,
            ],
            'neither a flag nor an age range' => [
                'event' => $this->event([]),
                'expected' => null,
            ],
        ];
    }

    private function event(array $properties): stdClass
    {
        return Json::decode(Json::encode((object) $properties));
    }
}

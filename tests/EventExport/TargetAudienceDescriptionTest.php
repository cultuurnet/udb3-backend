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
            'a birthdate range of children at the start of the event' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '2015-01-01', 'to' => '2015-12-31'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => $withGuardian,
            ],
            'a birthdate range of adults at the start of the event' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '1990-01-01', 'to' => '1990-12-31'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => null,
            ],
            'the youngest turning twelve the day before the event' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '2010-01-01', 'to' => '2014-05-31'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => null,
            ],
            'the youngest turning twelve the day after the event' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '2010-01-01', 'to' => '2014-06-02'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => $withGuardian,
            ],
            'an audience not born yet when the event starts' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '2030-01-01', 'to' => '2030-12-31'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => $withGuardian,
            ],
            'a permanent event counts towards the day it became available' => [
                'event' => $this->event([
                    'calendarType' => 'permanent',
                    'birthdateRange' => ['from' => '2015-01-01', 'to' => '2015-12-31'],
                    'availableFrom' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => $withGuardian,
            ],
            'a permanent event whose audience has grown up by the day it became available' => [
                'event' => $this->event([
                    'calendarType' => 'permanent',
                    'birthdateRange' => ['from' => '1990-01-01', 'to' => '1990-12-31'],
                    'availableFrom' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => null,
            ],
            'a start date wins from an available from' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '2015-01-01', 'to' => '2015-12-31'],
                    'startDate' => '2040-06-01T10:00:00+02:00',
                    'availableFrom' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => null,
            ],
            'an unreadable start date gives way to the available from' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '2015-01-01', 'to' => '2015-12-31'],
                    'startDate' => '500 BC',
                    'availableFrom' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => $withGuardian,
            ],
            'a birthdate range without a day to count towards' => [
                'event' => $this->event([
                    'birthdateRange' => ['from' => '2015-01-01', 'to' => '2015-12-31'],
                ]),
                'expected' => null,
            ],
            'a specific age range wins from a birthdate range' => [
                'event' => $this->event([
                    'typicalAgeRange' => '18-99',
                    'birthdateRange' => ['from' => '2015-01-01', 'to' => '2015-12-31'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => null,
            ],
            'an all ages range gives way to a birthdate range' => [
                'event' => $this->event([
                    'typicalAgeRange' => '-',
                    'birthdateRange' => ['from' => '2015-01-01', 'to' => '2015-12-31'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => $withGuardian,
            ],
            'an event only for children keeps saying so whatever its birthdate range' => [
                'event' => $this->event([
                    'childrenOnly' => true,
                    'birthdateRange' => ['from' => '1990-01-01', 'to' => '1990-12-31'],
                    'startDate' => '2026-06-01T10:00:00+02:00',
                ]),
                'expected' => $childrenOnly,
            ],
        ];
    }

    private function event(array $properties): stdClass
    {
        return Json::decode(Json::encode((object) $properties));
    }
}

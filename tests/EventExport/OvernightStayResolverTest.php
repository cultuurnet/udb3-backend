<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;

final class OvernightStayResolverTest extends TestCase
{
    private const CONCERT_TERM_ID = '0.50.4.0.0';

    /**
     * @test
     * @dataProvider eventsAndOvernightStay
     */
    public function it_summarises_the_occurrences_of_an_event(array $event, ?bool $expected): void
    {
        $this->assertSame($expected, OvernightStayResolver::forEvent(Json::decode(Json::encode((object) $event))));
    }

    public function eventsAndOvernightStay(): array
    {
        return [
            'a camp with an overnight stay' => [
                'event' => [
                    'terms' => [self::eventType(EventTypeResolver::CAMP_OR_VACATION_TERM_ID)],
                    'subEvent' => [['hasOvernightStay' => true]],
                ],
                'expected' => true,
            ],
            'a camp of which only one occurrence has an overnight stay' => [
                'event' => [
                    'terms' => [self::eventType(EventTypeResolver::CAMP_OR_VACATION_TERM_ID)],
                    'subEvent' => [[], ['hasOvernightStay' => true], []],
                ],
                'expected' => true,
            ],
            'a camp without an overnight stay' => [
                'event' => [
                    'terms' => [self::eventType(EventTypeResolver::CAMP_OR_VACATION_TERM_ID)],
                    'subEvent' => [[], []],
                ],
                'expected' => false,
            ],
            'a camp without occurrences' => [
                'event' => ['terms' => [self::eventType(EventTypeResolver::CAMP_OR_VACATION_TERM_ID)]],
                'expected' => false,
            ],
            'an event type that can never have an overnight stay' => [
                'event' => [
                    'terms' => [self::eventType(self::CONCERT_TERM_ID)],
                    'subEvent' => [['hasOvernightStay' => true]],
                ],
                'expected' => null,
            ],
            'an event without an event type' => [
                'event' => ['subEvent' => [['hasOvernightStay' => true]]],
                'expected' => null,
            ],
            'an event without terms at all' => [
                'event' => [],
                'expected' => null,
            ],
        ];
    }

    private static function eventType(string $id): array
    {
        return ['id' => $id, 'domain' => 'eventtype', 'label' => 'Type'];
    }
}

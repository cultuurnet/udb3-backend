<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;

final class OvernightStayTest extends TestCase
{
    private const CAMP = [['id' => '0.57.0.0.0', 'label' => 'Kamp of vakantie', 'domain' => 'eventtype']];

    private const CONCERT = [['id' => '0.50.4.0.0', 'label' => 'Concert', 'domain' => 'eventtype']];

    /**
     * @test
     */
    public function it_reports_an_overnight_stay_on_a_camp(): void
    {
        $this->assertTrue(
            OvernightStay::forEvent(
                $this->event(['terms' => self::CAMP, 'subEvent' => [['id' => 0, 'hasOvernightStay' => true]]])
            )
        );
    }

    /**
     * @test
     */
    public function it_reports_an_overnight_stay_on_any_occurrence_of_a_camp(): void
    {
        $this->assertTrue(
            OvernightStay::forEvent(
                $this->event(
                    [
                        'terms' => self::CAMP,
                        'subEvent' => [['id' => 0], ['id' => 1, 'hasOvernightStay' => true]],
                    ]
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_reports_no_overnight_stay_on_a_camp_without_one(): void
    {
        $this->assertFalse(
            OvernightStay::forEvent(
                $this->event(['terms' => self::CAMP, 'subEvent' => [['id' => 0]]])
            )
        );
    }

    /**
     * @test
     */
    public function it_reports_no_overnight_stay_on_a_camp_without_occurrences(): void
    {
        $this->assertFalse(OvernightStay::forEvent($this->event(['terms' => self::CAMP])));
    }

    /**
     * @test
     */
    public function it_does_not_apply_to_another_event_type(): void
    {
        $this->assertNull(
            OvernightStay::forEvent(
                $this->event(
                    ['terms' => self::CONCERT, 'subEvent' => [['id' => 0, 'hasOvernightStay' => true]]]
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_only_looks_at_the_event_type_term(): void
    {
        $this->assertNull(
            OvernightStay::forEvent(
                $this->event(
                    [
                        'terms' => [['id' => '0.57.0.0.0', 'label' => 'Kamp of vakantie', 'domain' => 'theme']],
                        'subEvent' => [['id' => 0, 'hasOvernightStay' => true]],
                    ]
                )
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_apply_to_an_event_without_terms(): void
    {
        $this->assertNull(OvernightStay::forEvent($this->event([])));
    }

    private function event(array $properties): \stdClass
    {
        return Json::decode(Json::encode((object) $properties));
    }
}

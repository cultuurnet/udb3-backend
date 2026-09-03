<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use PHPUnit\Framework\TestCase;

final class SubEventUpdateTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_no_childcare_time_range_by_default(): void
    {
        $this->assertNull((new SubEventUpdate(0))->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_can_set_a_childcare_time_range(): void
    {
        $range = new TimeImmutableRange(Time::fromString('15:00'), Time::fromString('23:00'));
        $update = (new SubEventUpdate(0))->withChildcareTimeRange($range);

        $this->assertSame($range, $update->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_can_set_childcare_time_range_to_null_to_clear_it(): void
    {
        $update = (new SubEventUpdate(0))
            ->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('15:00'), Time::fromString('23:00')))
            ->withChildcareTimeRange(null);

        $this->assertNull($update->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_setting_childcare_time_range(): void
    {
        $original = new SubEventUpdate(0);
        $updated = $original->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('15:00'), Time::fromString('23:00')));

        $this->assertNotSame($original, $updated);
        $this->assertNull($original->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_has_no_overnight_stay_value_by_default(): void
    {
        $this->assertNull((new SubEventUpdate(0))->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_can_set_overnight_stay_to_true(): void
    {
        $update = (new SubEventUpdate(0))->withHasOvernightStay(true);

        $this->assertTrue($update->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_can_set_overnight_stay_to_false(): void
    {
        $update = (new SubEventUpdate(0))->withHasOvernightStay(false);

        $this->assertFalse($update->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_can_set_overnight_stay_to_null_to_leave_it_unchanged(): void
    {
        $update = (new SubEventUpdate(0))->withHasOvernightStay(true)->withHasOvernightStay(null);

        $this->assertNull($update->getHasOvernightStay());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_setting_overnight_stay(): void
    {
        $original = new SubEventUpdate(0);
        $updated = $original->withHasOvernightStay(true);

        $this->assertNotSame($original, $updated);
        $this->assertNull($original->getHasOvernightStay());
    }
}

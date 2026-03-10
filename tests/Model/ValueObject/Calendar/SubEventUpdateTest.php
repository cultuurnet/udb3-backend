<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

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
        $range = new TimeImmutableRange('15:00', '23:00');
        $update = (new SubEventUpdate(0))->withChildcareTimeRange($range);

        $this->assertSame($range, $update->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_can_set_childcare_time_range_to_null_to_clear_it(): void
    {
        $update = (new SubEventUpdate(0))
            ->withChildcareTimeRange(new TimeImmutableRange('15:00', '23:00'))
            ->withChildcareTimeRange(null);

        $this->assertNull($update->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_setting_childcare_time_range(): void
    {
        $original = new SubEventUpdate(0);
        $updated = $original->withChildcareTimeRange(new TimeImmutableRange('15:00', '23:00'));

        $this->assertNotSame($original, $updated);
        $this->assertNull($original->getChildcareTimeRange());
    }
}

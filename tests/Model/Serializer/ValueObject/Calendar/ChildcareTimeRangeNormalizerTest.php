<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use PHPUnit\Framework\TestCase;

final class ChildcareTimeRangeNormalizerTest extends TestCase
{
    private ChildcareTimeRangeNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ChildcareTimeRangeNormalizer();
    }

    /**
     * @test
     */
    public function it_returns_null_when_childcare_time_range_is_null(): void
    {
        $result = $this->normalizer->normalize(null);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_returns_null_when_childcare_time_range_has_no_start_or_end(): void
    {
        $childcareTimeRange = new TimeImmutableRange(null, null);
        $result = $this->normalizer->normalize($childcareTimeRange);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_normalizes_childcare_time_range_with_start_and_end(): void
    {
        $childcareTimeRange = new TimeImmutableRange(
            Time::fromString('08:00'),
            Time::fromString('18:00')
        );

        $result = $this->normalizer->normalize($childcareTimeRange);

        $this->assertSame(['start' => '08:00', 'end' => '18:00'], $result);
    }

    /**
     * @test
     */
    public function it_normalizes_childcare_time_range_with_only_start(): void
    {
        $childcareTimeRange = new TimeImmutableRange(Time::fromString('08:00'), null);

        $result = $this->normalizer->normalize($childcareTimeRange);

        $this->assertSame(['start' => '08:00'], $result);
    }

    /**
     * @test
     */
    public function it_normalizes_childcare_time_range_with_only_end(): void
    {
        $childcareTimeRange = new TimeImmutableRange(null, Time::fromString('18:00'));

        $result = $this->normalizer->normalize($childcareTimeRange);

        $this->assertSame(['end' => '18:00'], $result);
    }
}

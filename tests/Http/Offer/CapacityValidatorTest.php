<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use PHPUnit\Framework\TestCase;

final class CapacityValidatorTest extends TestCase
{
    private CapacityValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new CapacityValidator();
    }

    /**
     * @test
     */
    public function it_accepts_sub_events_without_capacity(): void
    {
        $errors = $this->validator->validate((object)[
            'calendarType' => 'periodic',
            'subEvent' => [
                (object)[
                    'startDate' => '2026-11-05T10:00:00+01:00',
                    'endDate' => '2026-11-05T12:00:00+01:00',
                ],
            ],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_accepts_data_without_sub_events(): void
    {
        $errors = $this->validator->validate((object)['calendarType' => 'permanent']);

        $this->assertEmpty($errors);
    }

    /**
     * @test
     */
    public function it_rejects_every_sub_event_with_capacity(): void
    {
        $errors = $this->validator->validate((object)[
            'calendarType' => 'periodic',
            'subEvent' => [
                (object)[
                    'startDate' => '2026-11-05T10:00:00+01:00',
                    'endDate' => '2026-11-05T12:00:00+01:00',
                    'capacity' => (object)['type' => 'Limited', 'maximum' => 30],
                ],
                (object)[
                    'startDate' => '2026-11-06T10:00:00+01:00',
                    'endDate' => '2026-11-06T12:00:00+01:00',
                ],
                (object)[
                    'startDate' => '2026-11-07T10:00:00+01:00',
                    'endDate' => '2026-11-07T12:00:00+01:00',
                    'capacity' => (object)[],
                ],
            ],
        ]);

        $this->assertEquals(
            [
                new SchemaError('/subEvent/0/capacity', 'capacity is only allowed when calendarType is single or multiple'),
                new SchemaError('/subEvent/2/capacity', 'capacity is only allowed when calendarType is single or multiple'),
            ],
            $errors
        );
    }
}
